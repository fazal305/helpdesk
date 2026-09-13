<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tickets.php');
}

verifyCsrf();

$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

$ticket = findTicketForViewer($ticketId, currentUserId(), isAdmin());
if (!$ticket) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

if (!array_key_exists($newStatus, TICKET_STATUSES)) {
    setFlashMessage('error', 'Invalid status.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

$isOwner = (int) $ticket['user_id'] === currentUserId();

// Non-admins may only reopen their own resolved ticket. The browser never
// gets asked which transitions are legal — this is decided here, server-
// side, every time, regardless of what the client submits.
if (!isAdmin()) {
    if (!$isOwner || $ticket['status'] !== 'resolved' || $newStatus !== 'open') {
        http_response_code(403);
        setFlashMessage('error', 'You do not have permission to make that change.');
        redirect('/ticket-view.php?id=' . $ticketId);
    }
} elseif (!isValidStatusTransition($ticket['status'], $newStatus)) {
    setFlashMessage('error', 'That status change is not allowed from the current status.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

$db = getDB();
$db->beginTransaction();
try {
    $stmt = $db->prepare('UPDATE tickets SET status = ? WHERE id = ?');
    $stmt->execute([$newStatus, $ticketId]);
    recordHistory($ticketId, currentUserId(), 'status_changed', $ticket['status'], $newStatus);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

setFlashMessage('success', 'Ticket status updated.');
redirect('/ticket-view.php?id=' . $ticketId);
