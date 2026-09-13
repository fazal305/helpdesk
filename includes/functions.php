<?php
// General-purpose helpers used across the app: output escaping, redirects,
// formatting, and small validation utilities. Kept as plain functions
// (no framework/DI container) so the code stays easy to trace file-to-file.

// Escapes for safe HTML output. Every place user-generated content (ticket
// title, description, comment text, names) is printed goes through this —
// otherwise a title like "<script>..." would execute in another user's
// browser (stored XSS).
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function formatDate(string $datetime): string
{
    $ts = strtotime($datetime);
    return date('M j, Y g:i A', $ts);
}

// A short "2 hours ago" style label for recent timestamps, falling back to
// an absolute date once it's more than a week old (relative time stops
// being useful past that).
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return $mins . ' minute' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('M j, Y', strtotime($datetime));
}

function statusLabel(string $status): string
{
    return TICKET_STATUSES[$status] ?? ucfirst($status);
}

function priorityLabel(string $priority): string
{
    return TICKET_PRIORITIES[$priority] ?? ucfirst($priority);
}

function categoryLabel(string $category): string
{
    return TICKET_CATEGORIES[$category] ?? ucfirst($category);
}

// Reads a GET parameter and validates it against a fixed allow-list,
// returning null if it's absent or not one of the allowed values. Used for
// status/priority/category filters so a crafted query string like
// ?status=DROP+TABLE never reaches the SQL layer as anything but "no filter".
function validGetParam(string $key, array $allowed): ?string
{
    $value = $_GET[$key] ?? '';
    return in_array($value, $allowed, true) ? $value : null;
}

function currentPage(): int
{
    $page = (int) ($_GET['page'] ?? 1);
    return $page > 0 ? $page : 1;
}

// Rebuilds the current query string with one or more parameters overridden,
// used to build pagination links that preserve active search/filters.
function buildQuery(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return http_build_query($params);
}

function handleException(Throwable $e): void
{
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (APP_ENV === 'development') {
        throw $e;
    }

    http_response_code(500);
    require __DIR__ . '/../500.php';
    exit;
}
