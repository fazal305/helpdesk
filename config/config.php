<?php
// Loads environment variables from .env and defines shared, app-wide constants.
// Kept separate from db.php so non-database config (env mode, shared option
// lists) doesn't force every file that needs a status label to also open a
// database connection.

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnv(__DIR__ . '/../.env');

define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Development shows PHP errors on screen; production logs them and shows a
// generic error page instead (see includes/functions.php::handleException()).
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// Ticket option lists live in exactly one place so every page that renders a
// <select> or validates a submitted value pulls from the same source.
define('TICKET_STATUSES', [
    'open'        => 'Open',
    'in_progress' => 'In Progress',
    'resolved'    => 'Resolved',
    'closed'      => 'Closed',
]);

define('TICKET_PRIORITIES', [
    'low'    => 'Low',
    'medium' => 'Medium',
    'high'   => 'High',
    'urgent' => 'Urgent',
]);

define('TICKET_CATEGORIES', [
    'technical_support' => 'Technical Support',
    'account_access'    => 'Account & Access',
    'billing'           => 'Billing',
    'hardware'          => 'Hardware',
    'software'          => 'Software',
    'network'           => 'Network',
    'other'             => 'Other',
]);

// Allowed status transitions, keyed by current status. Enforced in
// actions/ticket-update.php — the browser's <select> is a convenience, not
// the authority; the server rejects anything not listed here.
define('STATUS_TRANSITIONS', [
    'open'        => ['in_progress', 'resolved', 'closed'],
    'in_progress' => ['resolved', 'closed'],
    'resolved'    => ['open', 'closed'],
    'closed'      => [],
]);

define('TICKETS_PER_PAGE', 10);
