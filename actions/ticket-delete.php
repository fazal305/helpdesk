<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tickets.php');
}

verifyCsrf();

$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$ticket = findTicketForViewer($ticketId, currentUserId(), isAdmin());
if (!$ticket) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

$isOwner = (int) $ticket['user_id'] === currentUserId();
$canDelete = isAdmin() || ($isOwner && $ticket['status'] === 'open');
if (!$canDelete) {
    http_response_code(403);
    setFlashMessage('error', 'This ticket can no longer be deleted.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

// Comments and ticket_history rows cascade with the ticket (see
// database/schema.sql) — deleting the ticket here is sufficient.
$stmt = getDB()->prepare('DELETE FROM tickets WHERE id = ?');
$stmt->execute([$ticketId]);

setFlashMessage('success', 'Ticket deleted.');
redirect(isAdmin() ? '/admin/tickets.php' : '/tickets.php');
