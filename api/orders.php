<?php
declare(strict_types=1);
require_once __DIR__ . '/../admin/_helpers.php';
require_once __DIR__ . '/../admin/_payment.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit;
}

function symbolFor(string $currency): string {
    return $currency === 'BRL' ? 'R$' : '$';
}

$customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];
$requestedItems = is_array($body['items'] ?? null) ? $body['items'] : [];
$paymentMethod = (string)($body['payment_method'] ?? '');

$name = trim((string)($customer['name'] ?? ''));
$email = trim((string)($customer['email'] ?? ''));
$address = trim((string)($customer['address'] ?? ''));
$city = trim((string)($customer['city'] ?? ''));
$country = strtoupper(trim((string)($customer['country'] ?? '')));
$postalCode = trim((string)($customer['postal_code'] ?? ''));

if ($name === '' || $email === '' || $address === '' || $city === '' || $country === '' || $postalCode === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please fill in all shipping fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if (!in_array($paymentMethod, ['mercadopago', 'whatsapp'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please choose a payment method.']);
    exit;
}
// Mercado Pago is domestic-only; international orders go through WhatsApp.
if ($paymentMethod === 'mercadopago' && $country !== 'BR') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Mercado Pago is only available for orders shipping within Brazil.']);
    exit;
}
if ($paymentMethod === 'whatsapp' && $country === 'BR') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please use Mercado Pago for orders shipping within Brazil.']);
    exit;
}
if (!$requestedItems) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Your cart is empty.']);
    exit;
}

// Never trust client-submitted prices/weights — re-price every line against
// the current catalog so a tampered cart can't under-charge.
$catalog = read_json('products');
$catalogById = [];
foreach ($catalog as $p) {
    $catalogById[$p['id']] = $p;
}

// Domestic orders are priced in BRL (Mercado Pago), international in USD
// (handed off to WhatsApp) — each product carries both prices.
$priceField = $country === 'BR' ? 'price_brl' : 'price_usd';

$orderItems = [];
$totalWeight = 0.0;
foreach ($requestedItems as $reqItem) {
    $id = (string)($reqItem['id'] ?? '');
    $qty = max(1, (int)($reqItem['qty'] ?? 1));
    if (!isset($catalogById[$id])) continue;
    $product = $catalogById[$id];
    $priceAmount = (float)($product[$priceField] ?? 0);
    $weight = (float)($product['weight_kg'] ?? 0.3);
    $orderItems[] = [
        'id' => $id,
        'name' => $product['name'],
        'qty' => $qty,
        'price_amount' => $priceAmount,
        'line_total' => round($priceAmount * $qty, 2),
    ];
    $totalWeight += $weight * $qty;
}

if (!$orderItems) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'None of the items in your cart could be found.']);
    exit;
}

$shipping = calculate_shipping($country, $totalWeight);
$currency = $country === 'BR' ? 'BRL' : 'USD';
$subtotal = round(array_sum(array_column($orderItems, 'line_total')), 2);
$total = round($subtotal + $shipping['price'], 2);

$order = [
    'id' => bin2hex(random_bytes(8)),
    'created_at' => gmdate('c'),
    'status' => 'pending_payment',
    'customer' => [
        'name' => $name, 'email' => $email, 'address' => $address,
        'city' => $city, 'country' => $country, 'postal_code' => $postalCode,
    ],
    'items' => $orderItems,
    'shipping' => $shipping,
    'totals' => ['subtotal' => $subtotal, 'shipping' => $shipping['price'], 'total' => $total, 'currency' => $currency],
    'payment' => ['method' => $paymentMethod, 'status' => 'pending', 'provider_ref' => ''],
    'tracking_code' => '',
];

$orders = read_json('orders');
$orders[] = $order;
write_json('orders', $orders);

// Domestic orders get a real Mercado Pago payment redirect once credentials
// are configured in Payment Settings; otherwise fall back to a confirmation
// message — the order is recorded either way. International orders always
// hand off to WhatsApp with the order details pre-filled.
$redirectUrl = null;
if ($paymentMethod === 'mercadopago') {
    $redirectUrl = mercadopago_create_preference($order);
} elseif ($paymentMethod === 'whatsapp') {
    $links = read_json('links');
    $waNumber = preg_replace('/\D+/', '', (string)($links['contact_whatsapp_number'] ?? ''));
    if ($waNumber !== '') {
        $lines = [];
        $lines[] = 'Hi! I just placed an international order on skinmetal.band (#' . substr($order['id'], 0, 8) . ').';
        foreach ($orderItems as $it) {
            $lines[] = '- ' . $it['qty'] . 'x ' . $it['name'] . ' (' . $symbolFor($currency) . number_format($it['price_amount'], 2) . ' each)';
        }
        $lines[] = 'Shipping (' . $shipping['zone_name'] . '): ' . $symbolFor($currency) . number_format($shipping['price'], 2);
        $lines[] = 'Total: ' . $symbolFor($currency) . number_format($total, 2);
        $lines[] = 'Ship to: ' . $name . ', ' . $address . ', ' . $city . ', ' . $country . ' ' . $postalCode;
        $redirectUrl = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode(implode("\n", $lines));
    }
}

if ($redirectUrl) {
    echo json_encode(['ok' => true, 'order_id' => $order['id'], 'redirect_url' => $redirectUrl]);
} else {
    echo json_encode([
        'ok' => true,
        'order_id' => $order['id'],
        'message' => 'Thank you! Your order has been received (#' . substr($order['id'], 0, 8) . '). We will contact you shortly to confirm payment.',
    ]);
}
