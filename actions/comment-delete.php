<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tickets.php');
}

verifyCsrf();

$commentId = (int) ($_POST['comment_id'] ?? 0);
$ticketId = (int) ($_POST['ticket_id'] ?? 0);

$db = getDB();
$stmt = $db->prepare('SELECT id, user_id, ticket_id FROM comments WHERE id = ? AND ticket_id = ?');
$stmt->execute([$commentId, $ticketId]);
$comment = $stmt->fetch();

if (!$comment) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

// A user may only delete their own comment; staff may moderate any comment.
if ((int) $comment['user_id'] !== currentUserId() && !isAdmin()) {
    http_response_code(403);
    setFlashMessage('error', 'You do not have permission to delete that reply.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

$del = $db->prepare('DELETE FROM comments WHERE id = ?');
$del->execute([$commentId]);

setFlashMessage('success', 'Reply deleted.');
redirect('/ticket-view.php?id=' . $ticketId);
