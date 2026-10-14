<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$songs = read_json('songs');
$products = read_json('products');
$shows = read_json('shows');
$messages = read_json('messages');
$unread = count(array_filter($messages, fn($m) => empty($m['read'])));

$page_title = 'Dashboard';
$active = 'home';
include __DIR__ . '/_header.php';
?>
<h1>Dashboard</h1>
<p class="sub">Quick overview of what's live on the public site.</p>

<div class="row">
  <div class="card field">
    <label>Songs / Releases</label>
    <div style="font-size:1.8rem;"><?= count($songs) ?></div>
    <a href="music.php">Manage →</a>
  </div>
  <div class="card field">
    <label>Products</label>
    <div style="font-size:1.8rem;"><?= count($products) ?></div>
    <a href="merch.php">Manage →</a>
  </div>
  <div class="card field">
    <label>Upcoming Shows</label>
    <div style="font-size:1.8rem;"><?= count($shows) ?></div>
    <a href="live.php">Manage →</a>
  </div>
  <div class="card field">
    <label>Unread Messages</label>
    <div style="font-size:1.8rem; <?= $unread ? 'color:var(--accent);' : '' ?>"><?= $unread ?></div>
    <a href="messages.php">View →</a>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
