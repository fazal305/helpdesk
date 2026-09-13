<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$db = getDB();

// Every number here comes from a real query against the current data —
// no placeholder/fabricated stats.
$statusCounts = array_fill_keys(array_keys(TICKET_STATUSES), 0);
foreach ($db->query('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status')->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$urgentCount = (int) $db->query("SELECT COUNT(*) AS c FROM tickets WHERE priority = 'urgent' AND status NOT IN ('resolved','closed')")->fetch()['c'];
$totalUsers = (int) $db->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$totalTickets = array_sum($statusCounts);

$recentStmt = $db->query(
    'SELECT t.id, t.title, t.status, t.priority, t.updated_at, u.name AS requester_name
     FROM tickets t JOIN users u ON u.id = t.user_id
     ORDER BY t.updated_at DESC LIMIT 8'
);
$recentTickets = $recentStmt->fetchAll();

$pageTitle = 'Admin Dashboard — HelpDesk';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Operational overview across all tickets.</p>
    </div>
    <a href="/admin/tickets.php" class="btn btn--primary">Manage Tickets</a>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-card__value"><?= $totalTickets ?></div><div class="stat-card__label">Total tickets</div></div>
    <?php foreach (TICKET_STATUSES as $key => $label): ?>
    <div class="stat-card"><div class="stat-card__value"><?= $statusCounts[$key] ?></div><div class="stat-card__label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
    <div class="stat-card"><div class="stat-card__value"><?= $urgentCount ?></div><div class="stat-card__label">Urgent &amp; open</div></div>
    <div class="stat-card"><div class="stat-card__value"><?= $totalUsers ?></div><div class="stat-card__label">Total users</div></div>
</div>

<div class="section">
    <div class="section__title">
        <h2>Recently updated</h2>
        <a href="/admin/tickets.php">View all &rarr;</a>
    </div>
    <?php if (empty($recentTickets)): ?>
    <div class="empty-state"><h3>No tickets yet</h3><p>Tickets submitted by users will appear here.</p></div>
    <?php else: ?>
    <div class="table-wrap table-wrap--responsive">
        <table>
            <thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Priority</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($recentTickets as $t): ?>
                <tr>
                    <td data-label="Ticket"><a class="table-title" href="/ticket-view.php?id=<?= (int) $t['id'] ?>">#<?= (int) $t['id'] ?> &mdash; <?= e($t['title']) ?></a></td>
                    <td data-label="Requester"><?= e($t['requester_name']) ?></td>
                    <td data-label="Status"><span class="badge badge--status-<?= e($t['status']) ?>"><?= e(statusLabel($t['status'])) ?></span></td>
                    <td data-label="Priority"><span class="priority priority--<?= e($t['priority']) ?>"><?= e(priorityLabel($t['priority'])) ?></span></td>
                    <td data-label="Updated"><?= e(timeAgo($t['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
