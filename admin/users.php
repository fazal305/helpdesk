<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$db = getDB();
$search = trim($_GET['search'] ?? '');
$page = currentPage();

$conditions = ['1=1'];
$params = [];
if ($search !== '') {
    $conditions[] = '(name LIKE ? OR email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$where = implode(' AND ', $conditions);

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM users WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / TICKETS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * TICKETS_PER_PAGE;

$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at,
        (SELECT COUNT(*) FROM tickets t WHERE t.user_id = u.id) AS ticket_count
        FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT " . (int) TICKETS_PER_PAGE . " OFFSET " . (int) $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users — HelpDesk Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Users</h1><p>Everyone with an account on HelpDesk.</p></div></div>

<form class="filter-bar" method="get" action="/admin/users.php">
    <div class="form-group filter-bar__search">
        <label for="search">Search</label>
        <input type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="Name or email">
    </div>
    <button type="submit" class="btn btn--secondary">Apply</button>
</form>

<?php if ($search !== ''): ?>
<div class="active-filters">
    <span class="result-count"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
    <a class="filter-chip" href="/admin/users.php">Clear &times;</a>
</div>
<?php endif; ?>

<?php if (empty($users)): ?>
<div class="empty-state"><h3>No users found</h3><p>Try a different search term.</p></div>
<?php else: ?>
<div class="table-wrap table-wrap--responsive">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Tickets</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td data-label="Name"><?= e($u['name']) ?></td>
                <td data-label="Email"><?= e($u['email']) ?></td>
                <td data-label="Role"><span class="badge badge--status-<?= $u['role'] === 'admin' ? 'in_progress' : 'closed' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                <td data-label="Tickets"><?= (int) $u['ticket_count'] ?></td>
                <td data-label="Joined"><?= e(formatDate($u['created_at'])) ?></td>
                <td data-label="Actions"><a href="/admin/user-edit.php?id=<?= (int) $u['id'] ?>">Edit</a></td>
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
