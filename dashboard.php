<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$user = currentUser();

$countStmt = $db->prepare(
    'SELECT status, COUNT(*) AS total FROM tickets WHERE user_id = ? GROUP BY status'
);
$countStmt->execute([$userId]);
$counts = array_fill_keys(array_keys(TICKET_STATUSES), 0);
foreach ($countStmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}

$recentStmt = $db->prepare(
    'SELECT id, title, status, priority, category, updated_at
     FROM tickets WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5'
);
$recentStmt->execute([$userId]);
$recentTickets = $recentStmt->fetchAll();

$pageTitle = 'Dashboard — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?></h1>
        <p>Here's where things stand with your support requests.</p>
    </div>
    <a href="/ticket-create.php" class="btn btn--primary">New Ticket</a>
</div>

<div class="stat-grid">
    <?php foreach (TICKET_STATUSES as $key => $label): ?>
    <div class="stat-card">
        <div class="stat-card__value"><?= $counts[$key] ?></div>
        <div class="stat-card__label"><?= e($label) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <div class="section__title">
        <h2>Recent tickets</h2>
        <a href="/tickets.php">View all &rarr;</a>
    </div>

    <?php if (empty($recentTickets)): ?>
    <div class="empty-state">
        <h3>No tickets yet</h3>
        <p>When you run into an issue, create a ticket and we'll help you sort it out.</p>
        <a href="/ticket-create.php" class="btn btn--primary">Create your first ticket</a>
    </div>
    <?php else: ?>
    <div class="table-wrap table-wrap--responsive">
        <table>
            <thead>
                <tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Category</th><th>Updated</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentTickets as $t): ?>
                <tr>
                    <td data-label="Ticket"><a class="table-title" href="/ticket-view.php?id=<?= (int) $t['id'] ?>">#<?= (int) $t['id'] ?> &mdash; <?= e($t['title']) ?></a></td>
                    <td data-label="Status"><span class="badge badge--status-<?= e($t['status']) ?>"><?= e(statusLabel($t['status'])) ?></span></td>
                    <td data-label="Priority"><span class="priority priority--<?= e($t['priority']) ?>"><?= e(priorityLabel($t['priority'])) ?></span></td>
                    <td data-label="Category"><?= e(categoryLabel($t['category'])) ?></td>
                    <td data-label="Updated"><?= e(timeAgo($t['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
