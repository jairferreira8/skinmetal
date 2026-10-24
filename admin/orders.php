<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$orders = read_json('orders');
$flash = '';
$statusLabels = ['pending_payment' => 'Pending Payment', 'paid' => 'Paid', 'shipped' => 'Shipped', 'cancelled' => 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (string)($_POST['id'] ?? '');

    if ($action === 'update') {
        $status = (string)($_POST['status'] ?? '');
        $tracking = trim((string)($_POST['tracking_code'] ?? ''));
        if (!isset($statusLabels[$status])) $status = 'pending_payment';
        foreach ($orders as &$o) {
            if ($o['id'] === $id) {
                $o['status'] = $status;
                $o['tracking_code'] = $tracking;
                break;
            }
        }
        unset($o);
        write_json('orders', $orders);
        $flash = 'ok:Order updated.';
    } elseif ($action === 'delete') {
        $orders = array_values(array_filter($orders, fn($o) => $o['id'] !== $id));
        write_json('orders', $orders);
        $flash = 'ok:Order removed.';
    }
}

usort($orders, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

$page_title = 'Orders';
$active = 'orders';
include __DIR__ . '/_header.php';
?>
<h1>Orders</h1>
<p class="sub">Merch orders placed at checkout. Payment status updates automatically once Mercado Pago/PayPal are configured and a payment is confirmed; you can also update it manually here.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<table>
  <thead><tr><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status / Tracking</th><th></th></tr></thead>
  <tbody>
    <?php if (!$orders): ?>
      <tr><td colspan="7" class="empty-state">No orders yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($orders as $o): ?>
      <?php
        $totals = $o['totals'] ?? ['subtotal' => 0, 'shipping' => 0, 'total' => 0, 'currency' => 'USD'];
        $symbol = ($totals['currency'] ?? 'USD') === 'BRL' ? 'R$' : '$';
        $itemsSummary = implode(', ', array_map(fn($it) => $it['qty'] . 'x ' . $it['name'], $o['items'] ?? []));
      ?>
      <tr>
        <td style="white-space:nowrap;">
          <?= h(substr((string)($o['created_at'] ?? ''), 0, 10)) ?><br>
          <span style="color:var(--muted); font-size:0.78rem;">#<?= h(substr($o['id'] ?? '', 0, 8)) ?></span>
        </td>
        <td>
          <strong><?= h($o['customer']['name'] ?? '') ?></strong><br>
          <a href="mailto:<?= h($o['customer']['email'] ?? '') ?>"><?= h($o['customer']['email'] ?? '') ?></a><br>
          <span style="color:var(--muted);"><?= h($o['customer']['address'] ?? '') ?>, <?= h($o['customer']['city'] ?? '') ?>, <?= h($o['customer']['country'] ?? '') ?> <?= h($o['customer']['postal_code'] ?? '') ?></span>
        </td>
        <td style="max-width:220px;"><?= h($itemsSummary) ?></td>
        <td>
          <?= h($symbol . number_format((float)($totals['total'] ?? 0), 2)) ?><br>
          <span style="color:var(--muted); font-size:0.78rem;">incl. <?= h($symbol . number_format((float)($totals['shipping'] ?? 0), 2)) ?> shipping (<?= h($o['shipping']['zone_name'] ?? '') ?>)</span>
        </td>
        <td><?= h(ucfirst($o['payment']['method'] ?? '')) ?></td>
        <td class="actions">
          <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= h($o['id']) ?>">
            <select name="status" style="margin-bottom:6px;">
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= h($key) ?>" <?= ($o['status'] ?? '') === $key ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="tracking_code" value="<?= h($o['tracking_code'] ?? '') ?>" placeholder="Tracking code" style="margin-bottom:6px;">
            <button type="submit" class="btn secondary">Save</button>
          </form>
        </td>
        <td class="actions">
          <form method="post" onsubmit="return confirm('Remove this order?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($o['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
