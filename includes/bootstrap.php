<?php
// Single entry point every page/action requires first. Centralizes session
// setup and the require chain so no page has to remember the right order
// (config before db, db before auth, etc.) or repeat it.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// A distinct session name (rather than the PHP default PHPSESSID) keeps
// this app's session cookie from colliding with any other local PHP
// project on the same "localhost" host — cookies are shared across ports
// on the same domain, but PHP's session storage is keyed only by the
// cookie value, so a same-named cookie from another app would otherwise
// resolve to that app's session data here.
session_name('helpdesk_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/tickets.php';

set_exception_handler('handleException');
