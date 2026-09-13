<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$errors = $_SESSION['ticket_create_errors'] ?? [];
$old = $_SESSION['ticket_create_old'] ?? [];
unset($_SESSION['ticket_create_errors'], $_SESSION['ticket_create_old']);

$pageTitle = 'New Ticket — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="breadcrumbs"><a href="/tickets.php">My Tickets</a> / New Ticket</div>
<h1>Create a support ticket</h1>
<p style="color:var(--color-text-muted); margin-bottom: var(--space-6);">Describe the issue and we'll route it to the right person.</p>

<form class="form form--wide" action="/actions/ticket-create.php" method="post" novalidate>
    <?= csrfField() ?>
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>"
               class="<?= isset($errors['title']) ? 'field-error' : '' ?>" maxlength="150" required>
        <?php if (isset($errors['title'])): ?><div class="field-error-message"><?= e($errors['title']) ?></div><?php endif; ?>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="<?= isset($errors['category']) ? 'field-error' : '' ?>">
                <?php foreach (TICKET_CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($old['category'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority" class="<?= isset($errors['priority']) ? 'field-error' : '' ?>">
                <?php foreach (TICKET_PRIORITIES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($old['priority'] ?? 'medium') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="8"
                  class="<?= isset($errors['description']) ? 'field-error' : '' ?>" required><?= e($old['description'] ?? '') ?></textarea>
        <div class="form-hint">Include what you were doing, what happened, and any error messages.</div>
        <?php if (isset($errors['description'])): ?><div class="field-error-message"><?= e($errors['description']) ?></div><?php endif; ?>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Submit ticket</button>
        <a href="/tickets.php" class="btn btn--secondary">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
