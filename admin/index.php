<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$songs = read_json('songs');
$products = read_json('products');
$shows = read_json('shows');
$videos = read_json('videos');
$messages = read_json('messages');
$unread = count(array_filter($messages, fn($m) => empty($m['read'])));

$page_title = 'Dashboard';
$active = 'home';
include __DIR__ . '/_header.php';

$counts = [
    'Songs' => count($songs),
    'Products' => count($products),
    'Videos' => count($videos),
    'Shows' => count($shows),
];
$maxCount = max(1, ...array_values($counts));
?>
<h1>Dashboard</h1>
<p class="sub">Quick overview of what's live on the public site.</p>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon"><?= admin_icon('music') ?></div>
    <div class="stat-value"><?= count($songs) ?></div>
    <div class="stat-label">Songs / Releases</div>
    <a href="music.php">Manage →</a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= admin_icon('products') ?></div>
    <div class="stat-value"><?= count($products) ?></div>
    <div class="stat-label">Products</div>
    <a href="merch.php">Manage →</a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= admin_icon('videos') ?></div>
    <div class="stat-value"><?= count($videos) ?></div>
    <div class="stat-label">Videos</div>
    <a href="videos.php">Manage →</a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= admin_icon('shows') ?></div>
    <div class="stat-value"><?= count($shows) ?></div>
    <div class="stat-label">Upcoming Shows</div>
    <a href="live.php">Manage →</a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= admin_icon('messages') ?></div>
    <div class="stat-value" style="<?= $unread ? 'color:var(--accent);' : '' ?>"><?= $unread ?></div>
    <div class="stat-label">Unread Messages</div>
    <a href="messages.php">View →</a>
  </div>
</div>

<div class="card chart-card">
  <p class="section-title">Content on the Site</p>
  <?php foreach ($counts as $label => $value): ?>
    <div class="bar-row">
      <div class="bar-label"><?= h($label) ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= intdiv($value * 100, $maxCount) ?>%"></div></div>
      <div class="bar-value"><?= $value ?></div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($unread > 0): ?>
<div class="card">
  <p class="section-title">Latest Messages</p>
  <table>
    <thead><tr><th>From</th><th>Subject</th><th></th></tr></thead>
    <tbody>
      <?php
      $sorted = $messages;
      usort($sorted, fn($a, $b) => ($b['received_at'] ?? '') <=> ($a['received_at'] ?? ''));
      $preview = array_slice(array_filter($sorted, fn($m) => empty($m['read'])), 0, 5);
      ?>
      <?php foreach ($preview as $m): ?>
        <tr>
          <td><strong><?= h($m['name'] ?? '') ?></strong></td>
          <td><?= h($m['subject'] ?? '') ?></td>
          <td><a href="messages.php">View →</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/_footer.php'; ?>
