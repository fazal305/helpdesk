<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$db = getDB();

$search = trim($_GET['search'] ?? '');
$status = validGetParam('status', array_keys(TICKET_STATUSES));
$priority = validGetParam('priority', array_keys(TICKET_PRIORITIES));
$category = validGetParam('category', array_keys(TICKET_CATEGORIES));
$sort = validGetParam('sort', ['updated_desc', 'updated_asc', 'priority_desc', 'created_desc']) ?? 'updated_desc';
$page = currentPage();

$conditions = ['1=1'];
$params = [];

if ($search !== '') {
    $conditions[] = '(t.title LIKE ? OR t.description LIKE ? OR t.id = ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = ctype_digit($search) ? (int) $search : 0;
}
if ($status !== null) { $conditions[] = 't.status = ?'; $params[] = $status; }
if ($priority !== null) { $conditions[] = 't.priority = ?'; $params[] = $priority; }
if ($category !== null) { $conditions[] = 't.category = ?'; $params[] = $category; }

$where = implode(' AND ', $conditions);

$orderMap = [
    'updated_desc'  => 't.updated_at DESC',
    'updated_asc'   => 't.updated_at ASC',
    'priority_desc' => "FIELD(t.priority,'urgent','high','medium','low')",
    'created_desc'  => 't.created_at DESC',
];
$orderBy = $orderMap[$sort];

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM tickets t WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / TICKETS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * TICKETS_PER_PAGE;

$sql = "SELECT t.id, t.title, t.status, t.priority, t.category, t.created_at, t.updated_at, u.name AS requester_name
        FROM tickets t JOIN users u ON u.id = t.user_id
        WHERE $where ORDER BY $orderBy LIMIT " . (int) TICKETS_PER_PAGE . " OFFSET " . (int) $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$hasFilters = $search !== '' || $status !== null || $priority !== null || $category !== null;

$pageTitle = 'Manage Tickets — HelpDesk Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div><h1>All Tickets</h1><p>Search, filter, and manage every ticket in the system.</p></div>
</div>

<form class="filter-bar" method="get" action="/admin/tickets.php">
    <div class="form-group filter-bar__search">
        <label for="search">Search</label>
        <input type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="Title, description, or #ID">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (TICKET_STATUSES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
            <option value="">All priorities</option>
            <?php foreach (TICKET_PRIORITIES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $priority === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (TICKET_CATEGORIES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="updated_desc" <?= $sort === 'updated_desc' ? 'selected' : '' ?>>Recently updated</option>
            <option value="updated_asc" <?= $sort === 'updated_asc' ? 'selected' : '' ?>>Least recently updated</option>
            <option value="priority_desc" <?= $sort === 'priority_desc' ? 'selected' : '' ?>>Priority (highest first)</option>
            <option value="created_desc" <?= $sort === 'created_desc' ? 'selected' : '' ?>>Newest</option>
        </select>
    </div>
    <button type="submit" class="btn btn--secondary">Apply</button>
</form>

<?php if ($hasFilters): ?>
<div class="active-filters">
    <span class="result-count"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
    <a class="filter-chip" href="/admin/tickets.php">Clear all filters &times;</a>
</div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <div class="empty-state">
        <h3><?= $hasFilters ? 'No tickets matched your search' : 'No tickets yet' ?></h3>
        <p><?= $hasFilters ? 'Try a different search term or clear your filters.' : 'Tickets submitted by users will appear here.' ?></p>
        <?php if ($hasFilters): ?><a href="/admin/tickets.php" class="btn btn--secondary">Clear filters</a><?php endif; ?>
    </div>
<?php else: ?>
<div class="table-wrap table-wrap--responsive">
    <table>
        <thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Priority</th><th>Category</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td data-label="Ticket"><a class="table-title" href="/ticket-view.php?id=<?= (int) $t['id'] ?>">#<?= (int) $t['id'] ?> &mdash; <?= e($t['title']) ?></a></td>
                <td data-label="Requester"><?= e($t['requester_name']) ?></td>
                <td data-label="Status"><span class="badge badge--status-<?= e($t['status']) ?>"><?= e(statusLabel($t['status'])) ?></span></td>
                <td data-label="Priority"><span class="priority priority--<?= e($t['priority']) ?>"><?= e(priorityLabel($t['priority'])) ?></span></td>
                <td data-label="Category"><?= e(categoryLabel($t['category'])) ?></td>
                <td data-label="Updated"><?= e(timeAgo($t['updated_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?><a href="?<?= buildQuery(['page' => $page - 1]) ?>">&larr; Prev</a><?php else: ?><span class="disabled">&larr; Prev</span><?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?= $i ?></span><?php else: ?><a href="?<?= buildQuery(['page' => $i]) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><a href="?<?= buildQuery(['page' => $page + 1]) ?>">Next &rarr;</a><?php else: ?><span class="disabled">Next &rarr;</span><?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
