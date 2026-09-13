<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}

verifyCsrf();

$userId = (int) ($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';

$db = getDB();
$stmt = $db->prepare('SELECT id, role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$target = $stmt->fetch();
if (!$target) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

// An admin can't demote themselves through this form — doing so could leave
// the system with zero admins mid-session. Role changes to your own account
// are rejected here regardless of what the (disabled) form field submits.
if ($userId === currentUserId()) {
    $role = $target['role'];
}

$errors = [];
if ($name === '') {
    $errors['name'] = 'Name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors['name'] = 'Name must be 100 characters or fewer.';
}
if ($email === '') {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!in_array($role, ['user', 'admin'], true)) {
    $errors['role'] = 'Choose a valid role.';
}

if (empty($errors['email'])) {
    $dupStmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $dupStmt->execute([$email, $userId]);
    if ($dupStmt->fetch()) {
        $errors['email'] = 'Another account already uses this email.';
    }
}

if ($errors) {
    $_SESSION['user_edit_errors'] = $errors;
    redirect('/admin/user-edit.php?id=' . $userId);
}

$update = $db->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
$update->execute([$name, $email, $role, $userId]);

setFlashMessage('success', 'User updated.');
redirect('/admin/user-edit.php?id=' . $userId);
