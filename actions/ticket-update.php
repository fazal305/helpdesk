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
$canEdit = isAdmin() || ($isOwner && $ticket['status'] === 'open');
if (!$canEdit) {
    http_response_code(403);
    setFlashMessage('error', 'This ticket can no longer be edited.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = $_POST['category'] ?? '';
$priority = $_POST['priority'] ?? '';

$errors = [];
if ($title === '') {
    $errors['title'] = 'Title is required.';
} elseif (mb_strlen($title) > 150) {
    $errors['title'] = 'Title must be 150 characters or fewer.';
}
if ($description === '') {
    $errors['description'] = 'Description is required.';
} elseif (mb_strlen($description) < 10) {
    $errors['description'] = 'Please provide a bit more detail (at least 10 characters).';
}
if (!array_key_exists($category, TICKET_CATEGORIES)) {
    $errors['category'] = 'Choose a valid category.';
}
if (!array_key_exists($priority, TICKET_PRIORITIES)) {
    $errors['priority'] = 'Choose a valid priority.';
}

if ($errors) {
    $_SESSION['ticket_edit_errors'] = $errors;
    redirect('/ticket-edit.php?id=' . $ticketId);
}

$db = getDB();
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'UPDATE tickets SET title = ?, description = ?, category = ?, priority = ? WHERE id = ?'
    );
    $stmt->execute([$title, $description, $category, $priority, $ticketId]);

    if ($ticket['category'] !== $category) {
        recordHistory($ticketId, currentUserId(), 'category_changed', $ticket['category'], $category);
    }
    if ($ticket['priority'] !== $priority) {
        recordHistory($ticketId, currentUserId(), 'priority_changed', $ticket['priority'], $priority);
    }
    if ($ticket['title'] !== $title || $ticket['description'] !== $description) {
        recordHistory($ticketId, currentUserId(), 'updated', null, null);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

setFlashMessage('success', 'Ticket updated.');
redirect('/ticket-view.php?id=' . $ticketId);
