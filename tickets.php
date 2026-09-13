<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

// GET-driven search/filter/pagination. Every value is validated before it
// touches SQL: search text is bound as a parameter (never concatenated),
// and status/priority/category are checked against the fixed option lists
// so an unexpected value just means "no filter" rather than reaching the
// query at all.
$search = trim($_GET['search'] ?? '');
$status = validGetParam('status', array_keys(TICKET_STATUSES));
$priority = validGetParam('priority', array_keys(TICKET_PRIORITIES));
$category = validGetParam('category', array_keys(TICKET_CATEGORIES));
$page = currentPage();

$conditions = ['user_id = ?'];
$params = [$userId];

if ($search !== '') {
    $conditions[] = '(title LIKE ? OR description LIKE ? OR id = ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = ctype_digit($search) ? (int) $search : 0;
}
if ($status !== null) {
    $conditions[] = 'status = ?';
    $params[] = $status;
}
if ($priority !== null) {
    $conditions[] = 'priority = ?';
    $params[] = $priority;
}
if ($category !== null) {
    $conditions[] = 'category = ?';
    $params[] = $category;
}

$where = implode(' AND ', $conditions);

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM tickets WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / TICKETS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * TICKETS_PER_PAGE;

// LIMIT/OFFSET can't be bound as regular PDO params under some drivers when
// emulation is off, so they're cast to int and interpolated directly —
// safe here because both values are computed server-side, never taken
// verbatim from user input.
$sql = "SELECT id, title, status, priority, category, created_at, updated_at
        FROM tickets WHERE $where ORDER BY updated_at DESC LIMIT " . (int) TICKETS_PER_PAGE . " OFFSET " . (int) $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$hasFilters = $search !== '' || $status !== null || $priority !== null || $category !== null;

$pageTitle = 'My Tickets — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>My Tickets</h1>
        <p>Every ticket you've submitted, in one place.</p>
    </div>
    <a href="/ticket-create.php" class="btn btn--primary">New Ticket</a>
</div>

<form class="filter-bar" method="get" action="/tickets.php">
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
    <button type="submit" class="btn btn--secondary">Apply</button>
</form>

<?php if ($hasFilters): ?>
<div class="active-filters">
    <span class="result-count"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
    <a class="filter-chip" href="/tickets.php">Clear all filters &times;</a>
</div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <?php if ($hasFilters): ?>
    <div class="empty-state">
        <h3>No tickets matched your search</h3>
        <p>Try a different search term or clear your filters.</p>
        <a href="/tickets.php" class="btn btn--secondary">Clear filters</a>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>No tickets yet</h3>
        <p>When you run into an issue, create a ticket and we'll help you sort it out.</p>
        <a href="/ticket-create.php" class="btn btn--primary">Create your first ticket</a>
    </div>
    <?php endif; ?>
<?php else: ?>
<div class="table-wrap table-wrap--responsive">
    <table>
        <thead>
            <tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Category</th><th>Updated</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
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
<?php require __DIR__ . '/includes/footer.php'; ?>
