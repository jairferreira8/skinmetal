<?php
declare(strict_types=1);
require_once __DIR__ . '/../../admin/_helpers.php';
require_once __DIR__ . '/../../admin/_payment.php';

// Mercado Pago calls this URL (configured as notification_url on the
// preference) with either ?type=payment&data.id=X or the older
// ?topic=payment&id=X. We never trust the notification body/query for the
// actual payment status — we always re-fetch the payment from Mercado
// Pago's API using our stored access token before marking anything paid.
http_response_code(200); // acknowledge immediately; Mercado Pago retries on non-2xx

// PHP turns "data.id" in the query string into the literal key "data.id"
// (dots are not nesting syntax outside of "data[id]"), so read it as-is.
$paymentId = (string)($_GET['data.id'] ?? $_GET['id'] ?? '');
$type = (string)($_GET['type'] ?? $_GET['topic'] ?? '');

if ($paymentId === '' || ($type !== '' && $type !== 'payment')) {
    exit;
}

$payment = mercadopago_get_payment($paymentId);
if (!$payment) exit;

$orderId = (string)($payment['external_reference'] ?? '');
$status = (string)($payment['status'] ?? '');
if ($orderId === '') exit;

if ($status === 'approved') {
    mark_order_status($orderId, 'paid', $paymentId);
} elseif (in_array($status, ['rejected', 'cancelled'], true)) {
    mark_order_status($orderId, 'cancelled', $paymentId);
}
