<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

function site_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'skinmetal.band';
    return $scheme . '://' . $host;
}

function http_json_post(string $url, array $body, array $headers): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode >= 300) return null;
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function http_json_get(string $url, array $headers): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode >= 300) return null;
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

/** Update the matching order's status/payment fields. Returns true if found. */
function mark_order_status(string $orderId, string $status, string $providerRef = ''): bool {
    $orders = read_json('orders');
    $found = false;
    foreach ($orders as &$o) {
        if ($o['id'] === $orderId) {
            $o['status'] = $status;
            $o['payment']['status'] = $status === 'paid' ? 'approved' : $o['payment']['status'];
            if ($providerRef !== '') $o['payment']['provider_ref'] = $providerRef;
            $found = true;
            break;
        }
    }
    unset($o);
    if ($found) write_json('orders', $orders);
    return $found;
}

function find_order(string $orderId): ?array {
    foreach (read_json('orders') as $o) {
        if ($o['id'] === $orderId) return $o;
    }
    return null;
}

/* ---- Mercado Pago (Checkout Pro) ---- */

function mercadopago_create_preference(array $order): ?string {
    $payment = read_json('payment');
    $token = $payment['mercadopago_access_token'] ?? '';
    if (!$token) return null;

    $currency = $order['totals']['currency'] ?? 'BRL';
    $items = [];
    foreach ($order['items'] as $item) {
        $items[] = [
            'title' => $item['name'],
            'quantity' => (int)$item['qty'],
            'unit_price' => (float)$item['price_amount'],
            'currency_id' => $currency,
        ];
    }
    if (!empty($order['shipping']['price'])) {
        $items[] = [
            'title' => 'Shipping (' . ($order['shipping']['zone_name'] ?? '') . ')',
            'quantity' => 1,
            'unit_price' => (float)$order['shipping']['price'],
            'currency_id' => $currency,
        ];
    }

    $siteUrl = site_base_url();
    $body = [
        'items' => $items,
        'payer' => ['name' => $order['customer']['name'] ?? '', 'email' => $order['customer']['email'] ?? ''],
        'external_reference' => $order['id'],
        'back_urls' => [
            'success' => $siteUrl . '/checkout-success.html?order=' . urlencode($order['id']),
            'failure' => $siteUrl . '/checkout.html',
            'pending' => $siteUrl . '/checkout-success.html?order=' . urlencode($order['id']),
        ],
        'auto_return' => 'approved',
        'notification_url' => $siteUrl . '/api/webhooks/mercadopago.php',
    ];

    $data = http_json_post('https://api.mercadopago.com/checkout/preferences', $body, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    return $data['init_point'] ?? null;
}

/** Look up a Mercado Pago payment by id (used by the webhook, never trusts the webhook body directly). */
function mercadopago_get_payment(string $paymentId): ?array {
    $payment = read_json('payment');
    $token = $payment['mercadopago_access_token'] ?? '';
    if (!$token) return null;
    return http_json_get('https://api.mercadopago.com/v1/payments/' . urlencode($paymentId), [
        'Authorization: Bearer ' . $token,
    ]);
}

/* ---- PayPal (Orders v2, redirect-based) ---- */

function paypal_api_base(): string {
    $payment = read_json('payment');
    return ($payment['paypal_mode'] ?? 'sandbox') === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
}

function paypal_get_access_token(): ?string {
    $payment = read_json('payment');
    $clientId = $payment['paypal_client_id'] ?? '';
    $secret = $payment['paypal_secret'] ?? '';
    if (!$clientId || !$secret) return null;

    $ch = curl_init(paypal_api_base() . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $clientId . ':' . $secret,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode >= 300) return null;
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function paypal_create_order(array $order): ?string {
    $token = paypal_get_access_token();
    if (!$token) return null;

    $currency = $order['totals']['currency'] ?? 'USD';
    $total = number_format((float)$order['totals']['total'], 2, '.', '');
    $siteUrl = site_base_url();

    $body = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $order['id'],
            'amount' => ['currency_code' => $currency, 'value' => $total],
        ]],
        'application_context' => [
            'brand_name' => 'SKINMETAL',
            'return_url' => $siteUrl . '/api/checkout/paypal-capture.php?order=' . urlencode($order['id']),
            'cancel_url' => $siteUrl . '/checkout.html',
        ],
    ];

    $data = http_json_post(paypal_api_base() . '/v2/checkout/orders', $body, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    if (!$data) return null;

    if (!empty($data['id'])) {
        mark_order_status($order['id'], $order['status'] ?? 'pending_payment', $data['id']);
    }

    foreach ($data['links'] ?? [] as $link) {
        if (($link['rel'] ?? '') === 'approve') return $link['href'];
    }
    return null;
}

/** Fetches a PayPal order by id straight from PayPal's API (used by the webhook, never trusts the webhook body directly). */
function paypal_get_order(string $paypalOrderId): ?array {
    $token = paypal_get_access_token();
    if (!$token) return null;
    return http_json_get(paypal_api_base() . '/v2/checkout/orders/' . urlencode($paypalOrderId), [
        'Authorization: Bearer ' . $token,
    ]);
}

/** Captures a PayPal order after the customer approves it. Returns the capture status string, or null on failure. */
function paypal_capture_order(string $paypalOrderId): ?string {
    $token = paypal_get_access_token();
    if (!$token) return null;
    $data = http_json_post(paypal_api_base() . '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture', [], [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    return $data['status'] ?? null;
}
