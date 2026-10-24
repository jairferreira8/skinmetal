<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$payment = read_json('payment', [
    'mercadopago_access_token' => '', 'mercadopago_public_key' => '',
    'paypal_client_id' => '', 'paypal_secret' => '', 'paypal_mode' => 'sandbox',
]);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leave a secret field blank to keep the currently stored value — the
    // actual secret is never re-printed into the page source, only a
    // "configured" placeholder.
    foreach (['mercadopago_access_token', 'mercadopago_public_key', 'paypal_client_id', 'paypal_secret'] as $key) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value !== '') $payment[$key] = $value;
    }
    $mode = (string)($_POST['paypal_mode'] ?? 'sandbox');
    $payment['paypal_mode'] = in_array($mode, ['sandbox', 'live'], true) ? $mode : 'sandbox';
    write_json('payment', $payment);
    $flash = 'ok:Payment settings updated.';
}

$page_title = 'Payment Settings';
$active = 'payment';
include __DIR__ . '/_header.php';
?>
<h1>Payment Settings</h1>
<p class="sub">API credentials for Mercado Pago and PayPal, used to accept real payments at checkout. These values are never shown back on this page or sent to visitors &mdash; only whether each is configured.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<form method="post" class="card">
  <h3 style="margin-top:0;">Mercado Pago</h3>
  <p class="sub">From your Mercado Pago account: Your business &rarr; Developers &rarr; Your integrations &rarr; Credentials. Use the Production credentials to accept real payments.</p>
  <div class="row">
    <div class="field">
      <label>Access Token <?= !empty($payment['mercadopago_access_token']) ? '(configured &mdash; leave blank to keep it)' : '' ?></label>
      <input type="password" name="mercadopago_access_token" autocomplete="off" placeholder="<?= !empty($payment['mercadopago_access_token']) ? '••••••••••••' : 'APP_USR-...' ?>">
    </div>
    <div class="field">
      <label>Public Key <?= !empty($payment['mercadopago_public_key']) ? '(configured &mdash; leave blank to keep it)' : '' ?></label>
      <input type="password" name="mercadopago_public_key" autocomplete="off" placeholder="<?= !empty($payment['mercadopago_public_key']) ? '••••••••••••' : 'APP_USR-...' ?>">
    </div>
  </div>

  <h3>PayPal</h3>
  <p class="sub">From your PayPal Business account: Developer Dashboard &rarr; Apps &amp; Credentials.</p>
  <div class="row">
    <div class="field">
      <label>Client ID <?= !empty($payment['paypal_client_id']) ? '(configured &mdash; leave blank to keep it)' : '' ?></label>
      <input type="password" name="paypal_client_id" autocomplete="off" placeholder="<?= !empty($payment['paypal_client_id']) ? '••••••••••••' : 'A...'; ?>">
    </div>
    <div class="field">
      <label>Secret <?= !empty($payment['paypal_secret']) ? '(configured &mdash; leave blank to keep it)' : '' ?></label>
      <input type="password" name="paypal_secret" autocomplete="off" placeholder="<?= !empty($payment['paypal_secret']) ? '••••••••••••' : 'E...'; ?>">
    </div>
  </div>
  <div class="field">
    <label>Mode</label>
    <select name="paypal_mode">
      <option value="sandbox" <?= ($payment['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (testing, no real money)</option>
      <option value="live" <?= ($payment['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live (real payments)</option>
    </select>
  </div>

  <button type="submit" class="btn">Save Payment Settings</button>
</form>

<div class="card">
  <h3 style="margin-top:0;">Status</h3>
  <p>Mercado Pago: <strong><?= !empty($payment['mercadopago_access_token']) ? 'Configured' : 'Not configured — checkout will show "being set up" for this method' ?></strong></p>
  <p>PayPal: <strong><?= (!empty($payment['paypal_client_id']) && !empty($payment['paypal_secret'])) ? 'Configured (' . h($payment['paypal_mode']) . ')' : 'Not configured — checkout will show "being set up" for this method' ?></strong></p>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
