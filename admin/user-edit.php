<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$userId = (int) ($_GET['id'] ?? 0);
$stmt = getDB()->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

$ticketCountStmt = getDB()->prepare('SELECT COUNT(*) AS c FROM tickets WHERE user_id = ?');
$ticketCountStmt->execute([$userId]);
$ticketCount = (int) $ticketCountStmt->fetch()['c'];

$errors = $_SESSION['user_edit_errors'] ?? [];
unset($_SESSION['user_edit_errors']);

$isSelf = $userId === currentUserId();

$pageTitle = 'Edit User — HelpDesk Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumbs"><a href="/admin/users.php">Users</a> / <?= e($user['name']) ?></div>
<h1>Edit user</h1>

<form class="form" action="/actions/user-update.php" method="post" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="user_id" value="<?= $userId ?>">
    <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= e($user['name']) ?>"
               class="<?= isset($errors['name']) ? 'field-error' : '' ?>" required>
        <?php if (isset($errors['name'])): ?><div class="field-error-message"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($user['email']) ?>"
               class="<?= isset($errors['email']) ? 'field-error' : '' ?>" required>
        <?php if (isset($errors['email'])): ?><div class="field-error-message"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <?php if ($isSelf): ?>
        <div class="form-hint">You cannot change your own role.</div>
        <input type="hidden" name="role" value="<?= e($user['role']) ?>">
        <?php endif; ?>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save changes</button>
        <a href="/admin/users.php" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<div class="section">
    <h2>Danger zone</h2>
    <div class="card">
        <?php if ($isSelf): ?>
        <p>You cannot delete your own account while logged in.</p>
        <?php elseif ($ticketCount > 0): ?>
        <p>This user has <?= $ticketCount ?> ticket<?= $ticketCount === 1 ? '' : 's' ?> on file and cannot be deleted. Reassign or resolve their tickets first.</p>
        <?php else: ?>
        <p>Permanently remove this user's account. This cannot be undone.</p>
        <form action="/actions/user-delete.php" method="post" data-confirm="Delete this user account permanently?">
            <?= csrfField() ?>
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <button type="submit" class="btn btn--danger">Delete user</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
