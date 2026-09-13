<?php
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/includes/bootstrap.php';
}
http_response_code(404);
$pageTitle = 'Page not found — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="empty-state">
    <h3>Page not found</h3>
    <p>The page you're looking for doesn't exist, or you don't have access to it.</p>
    <a href="<?= isLoggedIn() ? '/dashboard.php' : '/index.php' ?>" class="btn btn--primary">Go back</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
