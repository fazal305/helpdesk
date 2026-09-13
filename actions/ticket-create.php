<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/ticket-create.php');
}

verifyCsrf();

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

// Category/priority come from a <select>, but nothing stops a request from
// posting an arbitrary value directly — validate against the same allow-list
// the dropdown was built from before it ever reaches SQL.
if (!array_key_exists($category, TICKET_CATEGORIES)) {
    $errors['category'] = 'Choose a valid category.';
}
if (!array_key_exists($priority, TICKET_PRIORITIES)) {
    $errors['priority'] = 'Choose a valid priority.';
}

if ($errors) {
    $_SESSION['ticket_create_errors'] = $errors;
    $_SESSION['ticket_create_old'] = compact('title', 'description', 'category', 'priority');
    redirect('/ticket-create.php');
}

$db = getDB();
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO tickets (user_id, title, description, category, priority, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([currentUserId(), $title, $description, $category, $priority, 'open']);
    $ticketId = (int) $db->lastInsertId();

    recordHistory($ticketId, currentUserId(), 'created', null, null);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

setFlashMessage('success', 'Ticket created successfully.');
redirect('/ticket-view.php?id=' . $ticketId);
