<?php

const DATA_FILE = __DIR__ . '/../data/tickets.json';
const UPLOAD_DIR = __DIR__ . '/../uploads/';

function allowedCategories(): array
{
    return ['technical', 'billing', 'account', 'other'];
}

function allowedStatuses(): array
{
    return ['Open', 'In Progress', 'Resolved'];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// mbstring is not installed everywhere, so these fall back to the plain functions.
function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function loadTickets(): array
{
    if (!file_exists(DATA_FILE)) {
        return [];
    }

    $json = file_get_contents(DATA_FILE);
    $tickets = json_decode($json, true);

    return is_array($tickets) ? $tickets : [];
}

function saveTickets(array $tickets): bool
{
    // TODO 1: turn the array into pretty JSON and write it to the file.
    $json = json_encode(array_values($tickets), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    // LOCK_EX stops two requests from writing the file at the same moment.
    return file_put_contents(DATA_FILE, $json . "\n", LOCK_EX) !== false;
}

function findTicketById(array $tickets, int $id): ?array
{
    foreach ($tickets as $ticket) {
        if ((int)$ticket['id'] === $id) {
            return $ticket;
        }
    }

    return null;
}

function nextTicketId(array $tickets): int
{
    // TODO 2: one more than the biggest ID that exists (1 if there are no tickets).
    $max = 0;
    foreach ($tickets as $ticket) {
        $max = max($max, (int)$ticket['id']);
    }

    return $max + 1;
}

function filterTickets(
    array $tickets,
    string $query = '',
    string $status = '',
    string $category = ''
): array {
    // TODO 3: keep only the tickets that match every filter that was filled in.
    $query = lower(trim($query));
    $result = [];

    foreach ($tickets as $ticket) {
        if ($status !== '' && $ticket['status'] !== $status) {
            continue;
        }
        if ($category !== '' && $ticket['category'] !== $category) {
            continue;
        }
        if ($query !== '') {
            $text = lower($ticket['name'] . ' ' . $ticket['email'] . ' ' . $ticket['description']);
            if (!str_contains($text, $query)) {
                continue;
            }
        }
        $result[] = $ticket;
    }

    return $result;
}

function updateTicketStatus(array &$tickets, int $id, string $newStatus): bool
{
    // TODO 4: find the ticket by ID and change its status.
    if (!in_array($newStatus, allowedStatuses(), true)) {
        return false;
    }

    // The & makes $ticket the real array item, so the change is kept.
    foreach ($tickets as &$ticket) {
        if ((int)$ticket['id'] === $id) {
            $ticket['status'] = $newStatus;
            return true;
        }
    }

    return false;
}
