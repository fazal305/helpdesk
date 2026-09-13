<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$pageTitle = 'HelpDesk — Support Ticket Management';
$pageDescription = 'Track, prioritize, and resolve support requests in one place. HelpDesk is a lightweight ticketing system for support teams and the people they help.';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Support requests, handled clearly.</h1>
    <p>HelpDesk gives your team and your customers one shared place to raise issues, track progress, and see them through to resolution.</p>
    <div class="hero-actions">
        <a href="/register.php" class="btn btn--primary">Create an account</a>
        <a href="/login.php" class="btn btn--secondary">Log in</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
