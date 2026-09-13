<?php
// CSRF protection: every state-changing form carries a hidden token tied to
// the session. Without this, a malicious page on another site could submit
// a form to our actions/*.php on a logged-in user's behalf (their browser
// sends the session cookie automatically) and we'd have no way to tell that
// request apart from a real one — the token is the thing an attacker's page
// cannot know.

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        setFlashMessage('error', 'Your session expired or the form was submitted incorrectly. Please try again.');
        redirect(isLoggedIn() ? '/dashboard.php' : '/login.php');
    }
}
