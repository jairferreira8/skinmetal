<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$links = read_json('links', [
    'spotify' => '', 'apple_music' => '', 'youtube_music' => '', 'deezer' => '',
    'instagram' => '', 'youtube' => '', 'tiktok' => '',
    'contact_email' => '', 'contact_whatsapp_number' => '', 'contact_whatsapp_display' => '',
]);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ([
        'spotify', 'apple_music', 'youtube_music', 'deezer', 'instagram', 'youtube', 'tiktok',
        'contact_email', 'contact_whatsapp_number', 'contact_whatsapp_display',
    ] as $key) {
        $links[$key] = trim((string)($_POST[$key] ?? ''));
    }
    write_json('links', $links);
    $flash = 'Links updated.';
}

$page_title = 'Streaming Links';
$active = 'links';
include __DIR__ . '/_header.php';
?>
<h1>Streaming &amp; Social Links</h1>
<p class="sub">Leave any field blank to hide that button on the public site. Fill it in and it appears automatically.</p>

<?php if ($flash): ?><div class="flash ok"><?= h($flash) ?></div><?php endif; ?>

<form method="post" class="card">
  <h3 style="margin-top:0;">Streaming</h3>
  <div class="row">
    <div class="field"><label>Spotify</label><input type="url" name="spotify" value="<?= h($links['spotify']) ?>" placeholder="https://open.spotify.com/..."></div>
    <div class="field"><label>Apple Music</label><input type="url" name="apple_music" value="<?= h($links['apple_music']) ?>" placeholder="https://music.apple.com/..."></div>
  </div>
  <div class="row">
    <div class="field"><label>YouTube Music</label><input type="url" name="youtube_music" value="<?= h($links['youtube_music']) ?>" placeholder="https://music.youtube.com/..."></div>
    <div class="field"><label>Deezer</label><input type="url" name="deezer" value="<?= h($links['deezer']) ?>" placeholder="https://www.deezer.com/..."></div>
  </div>
  <h3>Social</h3>
  <div class="row">
    <div class="field"><label>Instagram</label><input type="url" name="instagram" value="<?= h($links['instagram']) ?>"></div>
    <div class="field"><label>YouTube</label><input type="url" name="youtube" value="<?= h($links['youtube']) ?>"></div>
    <div class="field"><label>TikTok</label><input type="url" name="tiktok" value="<?= h($links['tiktok']) ?>"></div>
  </div>
  <h3>Contact Page</h3>
  <div class="row">
    <div class="field"><label>Contact Email</label><input type="email" name="contact_email" value="<?= h($links['contact_email'] ?? '') ?>" placeholder="band@example.com"></div>
  </div>
  <div class="row">
    <div class="field"><label>WhatsApp Number (digits only, with country code, for the link)</label><input type="text" name="contact_whatsapp_number" value="<?= h($links['contact_whatsapp_number'] ?? '') ?>" placeholder="5511975458085"></div>
    <div class="field"><label>WhatsApp Display Text</label><input type="text" name="contact_whatsapp_display" value="<?= h($links['contact_whatsapp_display'] ?? '') ?>" placeholder="+55 11 97545-8085"></div>
  </div>
  <button type="submit" class="btn">Save Links</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
