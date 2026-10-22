<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$media = read_json('media', [
    'press_kit_url' => '', 'press_kit_name' => '',
    'photos_url' => '', 'photos_name' => '',
    'logos_url' => '', 'logos_name' => '',
]);
$flash = '';
$fields = ['press_kit' => 'Press Kit', 'photos' => 'High-Res Photos', 'logos' => 'Logos'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    foreach ($fields as $key => $label) {
        try {
            $uploaded = save_uploaded_file($key, 'media', $label);
            if ($uploaded) {
                $media[$key . '_url'] = $uploaded['url'];
                $media[$key . '_name'] = $uploaded['name'];
            }
        } catch (RuntimeException $e) {
            $errors[] = $label . ': ' . $e->getMessage();
        }
    }
    if ($errors) {
        $flash = 'err:' . implode(' / ', $errors);
    } else {
        write_json('media', $media);
        $flash = 'ok:Media Center files updated.';
    }
}

$page_title = 'Media Center';
$active = 'media';
include __DIR__ . '/_header.php';
?>
<h1>Media Center</h1>
<p class="sub">The downloadable files behind the three "Download" buttons on the Media &amp; Press page. Leave a field empty to keep the current file.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <?php foreach ($fields as $key => $label): ?>
    <div class="field">
      <label><?= h($label) ?> (JPG, PNG, WEBP, PDF or ZIP, up to 25 MB)</label>
      <?php if (!empty($media[$key . '_url'])): ?>
        <p style="margin:0 0 8px; color:var(--muted);">Current file: <a href="../<?= h($media[$key . '_url']) ?>" target="_blank" rel="noopener"><?= h($media[$key . '_name'] ?: basename($media[$key . '_url'])) ?></a></p>
      <?php endif; ?>
      <input type="file" name="<?= h($key) ?>" accept=".jpg,.jpeg,.png,.webp,.pdf,.zip">
    </div>
  <?php endforeach; ?>
  <button type="submit" class="btn">Save Files</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
