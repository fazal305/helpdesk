<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

// Admins use the same edit form as regular users (ticket-edit.php already
// allows admins to edit any ticket regardless of status) — this file exists
// so the admin section has a stable link target, without duplicating the
// form/validation logic in two places.
$ticketId = (int) ($_GET['id'] ?? 0);
redirect('/ticket-edit.php?id=' . $ticketId);
