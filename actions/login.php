<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

verifyCsrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = getDB()->prepare('SELECT id, password, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Deliberately the same error message whether the email doesn't exist or
// the password is wrong — telling an attacker "no account with that email"
// would let them enumerate registered addresses.
if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_errors'] = ['general' => 'Incorrect email or password.'];
    $_SESSION['login_old'] = ['email' => $email];
    redirect('/login.php');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_role'] = $user['role'];

setFlashMessage('success', 'Logged in successfully.');
redirect('/dashboard.php');
