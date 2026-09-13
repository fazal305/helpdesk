<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tickets.php');
}

verifyCsrf();

$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// Re-check ownership/access the same way the view page does — a comment
// form is itself a write path, so it needs the same IDOR guard, not just
// the page that renders the form.
$ticket = findTicketForViewer($ticketId, currentUserId(), isAdmin());
if (!$ticket) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

if ($ticket['status'] === 'closed') {
    setFlashMessage('error', 'This ticket is closed and no longer accepts replies.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

if ($message === '') {
    $_SESSION['comment_errors'] = ['message' => 'Reply cannot be empty.'];
    redirect('/ticket-view.php?id=' . $ticketId);
}
if (mb_strlen($message) > 2000) {
    $_SESSION['comment_errors'] = ['message' => 'Reply must be 2000 characters or fewer.'];
    redirect('/ticket-view.php?id=' . $ticketId);
}

$db = getDB();
$db->beginTransaction();
try {
    $stmt = $db->prepare('INSERT INTO comments (ticket_id, user_id, message) VALUES (?, ?, ?)');
    $stmt->execute([$ticketId, currentUserId(), $message]);
    recordHistory($ticketId, currentUserId(), 'comment_added', null, null);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

setFlashMessage('success', 'Reply added.');
redirect('/ticket-view.php?id=' . $ticketId);
