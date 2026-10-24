<?php
declare(strict_types=1);
require_once __DIR__ . '/../../admin/_helpers.php';
require_once __DIR__ . '/../../admin/_payment.php';

// PayPal redirects the customer here (GET) after they approve the payment.
// `token` is PayPal's order id; our own order id is passed through as `order`.
$paypalOrderId = (string)($_GET['token'] ?? '');
$orderId = (string)($_GET['order'] ?? '');

if ($paypalOrderId === '' || $orderId === '') {
    header('Location: ../../checkout.html');
    exit;
}

$status = paypal_capture_order($paypalOrderId);

if ($status === 'COMPLETED') {
    mark_order_status($orderId, 'paid', $paypalOrderId);
    header('Location: ../../checkout-success.html?order=' . urlencode($orderId));
    exit;
}

// Capture failed or is still pending — send the customer back rather than
// silently showing success.
header('Location: ../../checkout.html?payment_issue=1');
exit;
