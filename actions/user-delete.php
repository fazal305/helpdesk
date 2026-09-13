<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}

verifyCsrf();

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId === currentUserId()) {
    setFlashMessage('error', 'You cannot delete your own account while logged in.');
    redirect('/admin/user-edit.php?id=' . $userId);
}

$db = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

$ticketCountStmt = $db->prepare('SELECT COUNT(*) AS c FROM tickets WHERE user_id = ?');
$ticketCountStmt->execute([$userId]);
if ((int) $ticketCountStmt->fetch()['c'] > 0) {
    // tickets.user_id is ON DELETE RESTRICT (see database/schema.sql) so the
    // database would reject this anyway — checked here first to show a
    // clear message instead of a raw constraint-violation error.
    setFlashMessage('error', 'This user has tickets on file and cannot be deleted.');
    redirect('/admin/user-edit.php?id=' . $userId);
}

$del = $db->prepare('DELETE FROM users WHERE id = ?');
$del->execute([$userId]);

setFlashMessage('success', 'User deleted.');
redirect('/admin/users.php');
