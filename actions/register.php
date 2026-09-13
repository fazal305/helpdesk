<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register.php');
}

verifyCsrf();

// $_POST holds the submitted form fields. trim() first so "  " doesn't pass
// a required-field check, then validate server-side — the browser's
// `required` attribute is a UX nicety, not something we trust.
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

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
} elseif (mb_strlen($email) > 150) {
    $errors['email'] = 'Email must be 150 characters or fewer.';
}

if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (mb_strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if ($passwordConfirm === '' || $passwordConfirm !== $password) {
    $errors['password_confirm'] = 'Passwords do not match.';
}

// Only check for a duplicate email once the format itself is valid — no
// point querying the database for "not-an-email".
if (empty($errors['email'])) {
    $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'An account with this email already exists.';
    }
}

if ($errors) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = ['name' => $name, 'email' => $email];
    redirect('/register.php');
}

// password_hash() applies bcrypt with a random salt baked into the output
// string — password_verify() at login time re-derives the hash from the
// stored salt and compares. The plaintext password is never stored.
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = getDB()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
$stmt->execute([$name, $email, $hash, 'user']);
$userId = (int) getDB()->lastInsertId();

// Auto-login after registration. session_regenerate_id(true) issues a new
// session ID (and destroys the old session data server-side) so a session
// ID seen before authentication can't be reused after — this closes the
// session-fixation gap where an attacker sets a victim's session ID before
// they log in.
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['user_role'] = 'user';

setFlashMessage('success', 'Welcome to HelpDesk! Your account has been created.');
redirect('/dashboard.php');
