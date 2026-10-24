<?php
declare(strict_types=1);
require_once __DIR__ . '/../admin/_helpers.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$country = strtoupper(trim((string)($_GET['country'] ?? '')));
$weight = (float)($_GET['weight_kg'] ?? 0);

if ($country === '' || $weight <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'country and weight_kg are required']);
    exit;
}

echo json_encode(calculate_shipping($country, $weight), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
