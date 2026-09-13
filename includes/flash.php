<?php
// One-time flash messages stored in the session: set before a redirect, read
// (and cleared) on the very next request. This is what lets an action file
// redirect back to a page with "Ticket created successfully." showing once.

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
