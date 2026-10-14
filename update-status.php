<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// TODO 10: read and validate the ID and the new status from the POST body
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = trim($_POST['status'] ?? '');

if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Invalid ticket ID.');
}

if (!in_array($status, allowedStatuses(), true)) {
    http_response_code(400);
    exit('Invalid status.');
}

$tickets = loadTickets();

if (!updateTicketStatus($tickets, $id, $status)) {
    http_response_code(404);
    exit('Ticket not found.');
}

if (!saveTickets($tickets)) {
    http_response_code(500);
    exit('Could not save the change.');
}

header('Location: detail.php?id=' . $id);
exit;
