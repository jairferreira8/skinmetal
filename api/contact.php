<?php
declare(strict_types=1);
require_once __DIR__ . '/../admin/_helpers.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(bool $ok, string $message = ''): void {
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Method not allowed.');
}

$raw = file_get_contents('php://input');
$input = [];
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}
if (!$input) $input = $_POST;

// Honeypot: a hidden field real visitors never fill in.
if (!empty($input['website'])) {
    respond(true); // silently pretend success to bots
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$company = trim((string)($input['company'] ?? ''));
$country = trim((string)($input['country'] ?? ''));
$subject = trim((string)($input['subject'] ?? 'General'));
$message = trim((string)($input['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(422);
    respond(false, 'Please fill in your name, email and message.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    respond(false, 'Please enter a valid email address.');
}
if (strlen($message) > 5000) {
    http_response_code(422);
    respond(false, 'Message is too long.');
}

$messages = read_json('messages');
$messages[] = [
    'id' => bin2hex(random_bytes(8)),
    'name' => mb_substr($name, 0, 200),
    'email' => mb_substr($email, 0, 200),
    'company' => mb_substr($company, 0, 200),
    'country' => mb_substr($country, 0, 100),
    'subject' => mb_substr($subject, 0, 100),
    'message' => mb_substr($message, 0, 5000),
    'received_at' => gmdate('Y-m-d H:i:s') . ' UTC',
    'read' => false,
];
write_json('messages', $messages);

respond(true, 'Thank you. Your message has been received by the SKINMETAL team.');
