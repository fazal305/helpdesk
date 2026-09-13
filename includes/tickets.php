<?php
// Ticket-specific helpers shared between user-facing and admin pages/actions.

// Fetches a single ticket, enforcing ownership in the query itself for
// non-admins. Returns null if the ticket doesn't exist OR belongs to
// someone else — the caller can't tell those two cases apart, which is the
// point: revealing "that ticket exists but isn't yours" is still an IDOR
// leak (it confirms ticket IDs belong to other real users).
function findTicketForViewer(int $ticketId, int $viewerId, bool $viewerIsAdmin): ?array
{
    $db = getDB();

    if ($viewerIsAdmin) {
        $stmt = $db->prepare(
            'SELECT t.*, u.name AS requester_name, u.email AS requester_email
             FROM tickets t JOIN users u ON u.id = t.user_id
             WHERE t.id = ?'
        );
        $stmt->execute([$ticketId]);
    } else {
        $stmt = $db->prepare(
            'SELECT t.*, u.name AS requester_name, u.email AS requester_email
             FROM tickets t JOIN users u ON u.id = t.user_id
             WHERE t.id = ? AND t.user_id = ?'
        );
        $stmt->execute([$ticketId, $viewerId]);
    }

    $ticket = $stmt->fetch();
    return $ticket ?: null;
}

function isValidStatusTransition(string $from, string $to): bool
{
    if ($from === $to) {
        return false;
    }
    return in_array($to, STATUS_TRANSITIONS[$from] ?? [], true);
}

// Every ticket mutation (status/priority/category change, comment added,
// ticket edited) writes one row here so the ticket detail page can show a
// real audit trail instead of just a "last updated" timestamp.
function recordHistory(int $ticketId, ?int $userId, string $action, ?string $oldValue, ?string $newValue): void
{
    $stmt = getDB()->prepare(
        'INSERT INTO ticket_history (ticket_id, user_id, action, old_value, new_value) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$ticketId, $userId, $action, $oldValue, $newValue]);
}

function getTicketHistory(int $ticketId): array
{
    $stmt = getDB()->prepare(
        'SELECT h.*, u.name AS actor_name
         FROM ticket_history h LEFT JOIN users u ON u.id = h.user_id
         WHERE h.ticket_id = ? ORDER BY h.created_at ASC'
    );
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

function getTicketComments(int $ticketId): array
{
    $stmt = getDB()->prepare(
        'SELECT c.*, u.name AS author_name, u.role AS author_role
         FROM comments c JOIN users u ON u.id = c.user_id
         WHERE c.ticket_id = ? ORDER BY c.created_at ASC'
    );
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

// Human-readable label for a history action + its old/new values, used on
// the ticket detail page's history list.
function historyLabel(array $entry): string
{
    $actor = $entry['actor_name'] ?? 'A former user';

    switch ($entry['action']) {
        case 'created':
            return "$actor created this ticket.";
        case 'status_changed':
            return "$actor changed status from " . statusLabel($entry['old_value']) . ' to ' . statusLabel($entry['new_value']) . '.';
        case 'priority_changed':
            return "$actor changed priority from " . priorityLabel($entry['old_value']) . ' to ' . priorityLabel($entry['new_value']) . '.';
        case 'category_changed':
            return "$actor changed category from " . categoryLabel($entry['old_value']) . ' to ' . categoryLabel($entry['new_value']) . '.';
        case 'updated':
            return "$actor updated the ticket details.";
        case 'comment_added':
            return "$actor added a reply.";
        default:
            return "$actor performed an action.";
    }
}
