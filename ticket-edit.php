<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = findTicketForViewer($ticketId, currentUserId(), isAdmin());
if (!$ticket) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$isOwner = (int) $ticket['user_id'] === currentUserId();
$canEdit = isAdmin() || ($isOwner && $ticket['status'] === 'open');
if (!$canEdit) {
    http_response_code(403);
    setFlashMessage('error', 'This ticket can no longer be edited.');
    redirect('/ticket-view.php?id=' . $ticketId);
}

$errors = $_SESSION['ticket_edit_errors'] ?? [];
unset($_SESSION['ticket_edit_errors']);

$pageTitle = 'Edit Ticket #' . $ticketId . ' — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="breadcrumbs"><a href="/ticket-view.php?id=<?= $ticketId ?>">Ticket #<?= $ticketId ?></a> / Edit</div>
<h1>Edit ticket</h1>

<form class="form form--wide" action="/actions/ticket-update.php" method="post" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($ticket['title']) ?>"
               class="<?= isset($errors['title']) ? 'field-error' : '' ?>" maxlength="150" required>
        <?php if (isset($errors['title'])): ?><div class="field-error-message"><?= e($errors['title']) ?></div><?php endif; ?>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <?php foreach (TICKET_CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $ticket['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <?php foreach (TICKET_PRIORITIES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $ticket['priority'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="8"
                  class="<?= isset($errors['description']) ? 'field-error' : '' ?>" required><?= e($ticket['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="field-error-message"><?= e($errors['description']) ?></div><?php endif; ?>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save changes</button>
        <a href="/ticket-view.php?id=<?= $ticketId ?>" class="btn btn--secondary">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
