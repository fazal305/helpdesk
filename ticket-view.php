<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$ticketId = (int) ($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// findTicketForViewer() bakes the ownership check into the SQL WHERE clause
// for non-admins — a user who edits ?id=124 in the URL to try another
// person's ticket gets exactly the same "not found" response as a
// nonexistent ID, never a hint that ticket 124 exists but isn't theirs.
$ticket = findTicketForViewer($ticketId, currentUserId(), isAdmin());
if (!$ticket) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$isOwner = (int) $ticket['user_id'] === currentUserId();
$comments = getTicketComments($ticketId);
$history = getTicketHistory($ticketId);

$commentErrors = $_SESSION['comment_errors'] ?? [];
unset($_SESSION['comment_errors']);

$canEdit = isAdmin() || ($isOwner && $ticket['status'] === 'open');
$canDelete = isAdmin() || ($isOwner && $ticket['status'] === 'open');
$canReopen = $isOwner && !isAdmin() && $ticket['status'] === 'resolved';

$pageTitle = 'Ticket #' . $ticketId . ' — ' . $ticket['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="breadcrumbs"><a href="<?= isAdmin() ? '/admin/tickets.php' : '/tickets.php' ?>">Tickets</a> / #<?= $ticketId ?></div>

<div class="ticket-header">
    <div>
        <div class="ticket-id">Ticket #<?= $ticketId ?></div>
        <h1><?= e($ticket['title']) ?></h1>
        <div class="ticket-header__meta">
            <span class="badge badge--status-<?= e($ticket['status']) ?>"><?= e(statusLabel($ticket['status'])) ?></span>
            <span class="priority priority--<?= e($ticket['priority']) ?>"><?= e(priorityLabel($ticket['priority'])) ?></span>
            <span class="table-meta"><?= e(categoryLabel($ticket['category'])) ?></span>
        </div>
    </div>
    <div class="table-actions">
        <?php if ($canReopen): ?>
        <form action="/actions/ticket-status.php" method="post" style="margin:0;">
            <?= csrfField() ?>
            <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
            <input type="hidden" name="status" value="open">
            <button type="submit" class="btn btn--secondary btn--sm">Reopen ticket</button>
        </form>
        <?php endif; ?>
        <?php if ($canEdit): ?><a href="/ticket-edit.php?id=<?= $ticketId ?>" class="btn btn--secondary btn--sm">Edit</a><?php endif; ?>
        <?php if ($canDelete): ?>
        <form action="/actions/ticket-delete.php" method="post" data-confirm="Delete this ticket permanently? This cannot be undone." style="margin:0;">
            <?= csrfField() ?>
            <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
            <button type="submit" class="btn btn--danger btn--sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<dl class="ticket-info-grid">
    <div><dt>Requester</dt><dd><?= e($ticket['requester_name']) ?></dd></div>
    <div><dt>Created</dt><dd><?= e(formatDate($ticket['created_at'])) ?></dd></div>
    <div><dt>Last updated</dt><dd><?= e(timeAgo($ticket['updated_at'])) ?></dd></div>
    <?php if (isAdmin()): ?><div><dt>Requester email</dt><dd><?= e($ticket['requester_email']) ?></dd></div><?php endif; ?>
</dl>

<div class="ticket-description"><?= e($ticket['description']) ?></div>

<?php if (isAdmin()): ?>
<div class="section">
    <h2>Manage ticket</h2>
    <div class="card">
        <form action="/actions/ticket-status.php" method="post" class="form-row" style="align-items:flex-end;">
            <?= csrfField() ?>
            <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (TICKET_STATUSES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:0;">
                <button type="submit" class="btn btn--primary">Update status</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <h2>Conversation</h2>
    <div class="thread">
        <?php if (empty($comments)): ?>
        <p style="color:var(--color-text-faint);">No replies yet.</p>
        <?php else: foreach ($comments as $c): ?>
        <div class="comment <?= $c['author_role'] === 'admin' ? 'comment--staff' : '' ?>">
            <div class="comment__meta">
                <span class="comment__author"><?= e($c['author_name']) ?></span>
                <?php if ($c['author_role'] === 'admin'): ?><span class="comment__role">Staff</span><?php endif; ?>
                <span class="comment__time"><?= e(timeAgo($c['created_at'])) ?></span>
            </div>
            <div class="comment__body"><?= e($c['message']) ?></div>
            <?php if ((int) $c['user_id'] === currentUserId() || isAdmin()): ?>
            <form action="/actions/comment-delete.php" method="post" class="comment__delete" data-confirm="Delete this reply?">
                <?= csrfField() ?>
                <input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                <button type="submit" class="link-button">Delete</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
    <form class="form form--wide" action="/actions/comment-create.php" method="post" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
        <div class="form-group">
            <label for="message">Add a reply</label>
            <textarea id="message" name="message" rows="4" class="<?= isset($commentErrors['message']) ? 'field-error' : '' ?>" required></textarea>
            <?php if (isset($commentErrors['message'])): ?><div class="field-error-message"><?= e($commentErrors['message']) ?></div><?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Post reply</button>
        </div>
    </form>
    <?php else: ?>
    <p style="color:var(--color-text-faint);">This ticket is closed and no longer accepts replies.</p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>History</h2>
    <ul class="history-list">
        <?php foreach ($history as $h): ?>
        <li>
            <?= e(historyLabel($h)) ?>
            <time><?= e(formatDate($h['created_at'])) ?></time>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
