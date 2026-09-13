<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout is a state-changing action (it destroys the session) so it only
// accepts POST, guarded by the same CSRF check as any other mutating form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

verifyCsrf();

$_SESSION = [];
session_destroy();

// A fresh session is needed to carry the flash message past the redirect,
// since the old one was just destroyed.
session_start();
setFlashMessage('success', 'You have been logged out.');
redirect('/login.php');
