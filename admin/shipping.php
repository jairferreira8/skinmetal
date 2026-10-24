<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$defaults = [
    'domestic_currency' => 'BRL',
    'domestic_tiers' => [
        ['max_weight_kg' => 0.5, 'price' => 15],
        ['max_weight_kg' => 1, 'price' => 22],
        ['max_weight_kg' => 2, 'price' => 32],
        ['max_weight_kg' => 5, 'price' => 48],
        ['max_weight_kg' => 10, 'price' => 70],
    ],
    'international_currency' => 'USD',
    'international_zones' => [
        ['id' => 'south-america', 'name' => 'South America', 'price' => 25],
        ['id' => 'north-america', 'name' => 'North America', 'price' => 35],
        ['id' => 'europe', 'name' => 'Europe', 'price' => 40],
        ['id' => 'asia-oceania', 'name' => 'Asia & Oceania', 'price' => 45],
        ['id' => 'rest-of-world', 'name' => 'Rest of World', 'price' => 50],
    ],
];
$shipping = read_json('shipping', $defaults);
if (empty($shipping['domestic_tiers'])) $shipping['domestic_tiers'] = $defaults['domestic_tiers'];
if (empty($shipping['international_zones'])) $shipping['international_zones'] = $defaults['international_zones'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domesticTiers = [];
    foreach ($shipping['domestic_tiers'] as $i => $tier) {
        $maxWeight = (float)($_POST['domestic_max_' . $i] ?? $tier['max_weight_kg']);
        $price = (float)($_POST['domestic_price_' . $i] ?? $tier['price']);
        $domesticTiers[] = ['max_weight_kg' => $maxWeight, 'price' => $price];
    }
    $zones = [];
    foreach ($shipping['international_zones'] as $zone) {
        $price = (float)($_POST['zone_price_' . $zone['id']] ?? $zone['price']);
        $zones[] = ['id' => $zone['id'], 'name' => $zone['name'], 'price' => $price];
    }
    $shipping = [
        'domestic_currency' => 'BRL',
        'domestic_tiers' => $domesticTiers,
        'international_currency' => 'USD',
        'international_zones' => $zones,
    ];
    write_json('shipping', $shipping);
    $flash = 'ok:Shipping rates updated.';
}

$page_title = 'Shipping';
$active = 'shipping';
include __DIR__ . '/_header.php';
?>
<h1>Shipping</h1>
<p class="sub">Domestic (Brazil) rates by weight, calculated automatically at checkout, and flat international rates by region. These are not live carrier quotes — set prices based on your own research.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<form method="post" class="card">
  <h3 style="margin-top:0;">Domestic (Brazil) &mdash; price in BRL (R$) by weight tier</h3>
  <?php foreach ($shipping['domestic_tiers'] as $i => $tier): ?>
    <div class="row">
      <div class="field">
        <label>Up to Weight (kg)</label>
        <input type="number" step="0.01" min="0" name="domestic_max_<?= $i ?>" value="<?= h((string)$tier['max_weight_kg']) ?>">
      </div>
      <div class="field">
        <label>Price (R$)</label>
        <input type="number" step="0.01" min="0" name="domestic_price_<?= $i ?>" value="<?= h((string)$tier['price']) ?>">
      </div>
    </div>
  <?php endforeach; ?>

  <h3>International &mdash; flat price in USD ($) by region</h3>
  <?php foreach ($shipping['international_zones'] as $zone): ?>
    <div class="row">
      <div class="field" style="flex:2;">
        <label><?= h($zone['name']) ?></label>
        <input type="number" step="0.01" min="0" name="zone_price_<?= h($zone['id']) ?>" value="<?= h((string)$zone['price']) ?>">
      </div>
    </div>
  <?php endforeach; ?>

  <button type="submit" class="btn">Save Shipping Rates</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
