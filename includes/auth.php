<?php
// Authentication/authorization guards. Every protected page calls
// requireLogin() (and requireAdmin() where relevant) as its very first
// statement, before any output — this is what "Do not duplicate
// authentication checks in every file" means in practice: the check itself
// lives here once, each page just calls it.

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please log in to continue.');
        redirect('/login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        setFlashMessage('error', 'You do not have permission to view that page.');
        redirect('/dashboard.php');
    }
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

// Fetches the full current-user row when a page needs more than id/role
// (e.g. displaying the account name). Session only stores id + role to keep
// the session payload minimal.
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([currentUserId()]);
    $user = $stmt->fetch();
    return $user ?: null;
}
