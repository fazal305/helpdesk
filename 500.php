<?php
// Rendered by includes/functions.php::handleException() for uncaught errors
// in production. Deliberately shows no query text, file paths, or stack
// trace — those go to error_log() instead, never to the browser.
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/includes/bootstrap.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Something went wrong — HelpDesk</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container page">
    <div class="empty-state">
        <h3>Something went wrong</h3>
        <p>An unexpected error occurred. Please try again, and contact support if the problem continues.</p>
        <a href="/dashboard.php" class="btn btn--primary">Go to dashboard</a>
    </div>
</main>
</body>
</html>
