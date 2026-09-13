<?php
// Included by every page after bootstrap.php. Expects $pageTitle and
// optionally $pageDescription to be set before inclusion.
$pageTitle = $pageTitle ?? 'HelpDesk';
$pageDescription = $pageDescription ?? 'HelpDesk is a support ticket management system for tracking, prioritizing, and resolving customer issues.';
$user = isLoggedIn() ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= isLoggedIn() ? '/dashboard.php' : '/index.php' ?>">
            <span class="brand__mark" aria-hidden="true">HD</span>
            <span class="brand__name">HelpDesk</span>
        </a>

        <?php if (isLoggedIn()): ?>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="/dashboard.php"<?= str_ends_with($_SERVER['SCRIPT_NAME'], '/dashboard.php') ? ' aria-current="page"' : '' ?>>Dashboard</a>
            <a href="/tickets.php"<?= str_ends_with($_SERVER['SCRIPT_NAME'], '/tickets.php') ? ' aria-current="page"' : '' ?>>My Tickets</a>
            <a href="/ticket-create.php" class="btn btn--sm btn--primary">New Ticket</a>
            <?php if (isAdmin()): ?>
            <a href="/admin/index.php">Admin</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <span class="user-menu__name"><?= e($user['name'] ?? '') ?></span>
            <form action="/logout.php" method="post" class="user-menu__logout">
                <?= csrfField() ?>
                <button type="submit" class="link-button">Log out</button>
            </form>
        </div>
        <?php else: ?>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="/login.php">Log in</a>
            <a href="/register.php" class="btn btn--sm btn--primary">Sign up</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main id="main-content" class="container page">
<?php $flashMessages = getFlashMessages(); if ($flashMessages): ?>
    <div class="flash-stack">
    <?php foreach ($flashMessages as $flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
