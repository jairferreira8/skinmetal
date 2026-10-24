<?php
declare(strict_types=1);
require_once __DIR__ . '/../../admin/_helpers.php';
require_once __DIR__ . '/../../admin/_payment.php';

// PayPal calls this URL for order/capture events. Full webhook signature
// verification (PAYPAL-TRANSMISSION-ID / PAYPAL-CERT-URL headers against a
// stored webhook id via /v1/notifications/verify-webhook-signature) needs a
// real sandbox app to test against, so it is not implemented here — as a
// mitigation, we never trust the webhook body's own status field. Instead we
// re-fetch the order straight from PayPal's API with our own credentials and
// only act on what that authenticated call reports.
// TODO: once a real PayPal app exists, add signature verification before
// processing the event, using a `paypal_webhook_id` stored in Payment Settings.
http_response_code(200);

$raw = file_get_contents('php://input');
$event = json_decode($raw, true);
if (!is_array($event)) exit;

$eventType = (string)($event['event_type'] ?? '');
if (!in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
    exit;
}

$paypalOrderId = (string)($event['resource']['id'] ?? '');
// A capture event's resource is the capture itself; the order id lives under
// supplementary_data.related_ids.order_id instead.
if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
    $paypalOrderId = (string)($event['resource']['supplementary_data']['related_ids']['order_id'] ?? $paypalOrderId);
}
if ($paypalOrderId === '') exit;

$order = paypal_get_order($paypalOrderId);
if (!$order) exit;

$status = (string)($order['status'] ?? '');
$referenceId = (string)($order['purchase_units'][0]['reference_id'] ?? '');
if ($referenceId === '') exit;

if ($status === 'COMPLETED') {
    mark_order_status($referenceId, 'paid', $paypalOrderId);
} elseif ($status === 'VOIDED') {
    mark_order_status($referenceId, 'cancelled', $paypalOrderId);
}
