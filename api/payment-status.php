<?php
declare(strict_types=1);
require_once __DIR__ . '/../admin/_helpers.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Only exposes whether credentials are configured — never the credentials
// themselves. data/payment.json holds secrets and is never read_json'd by
// any other public api/*.php endpoint.
$payment = read_json('payment', []);
echo json_encode([
    'mercadopago' => !empty($payment['mercadopago_access_token']),
    'paypal' => !empty($payment['paypal_client_id']) && !empty($payment['paypal_secret']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
