<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$about = read_json('about', ['photo' => '', 'image_position' => 'center']);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = (string)($_POST['image_position'] ?? 'center');
    if (!in_array($position, ['top', 'center', 'bottom'], true)) $position = 'center';
    try {
        $uploaded = save_uploaded_image('photo', 'about', 'band-photo');
    } catch (RuntimeException $e) {
        $uploaded = null;
        $flash = 'err:' . $e->getMessage();
    }
    if ($flash === '') {
        $about = [
            'photo' => $uploaded ?? ($about['photo'] ?? ''),
            'image_position' => $position,
        ];
        write_json('about', $about);
        $flash = 'ok:About page photo updated.';
    }
}

$page_title = 'About Page';
$active = 'about';
include __DIR__ . '/_header.php';
?>
<h1>About Page</h1>
<p class="sub">The main band photo shown at the top of the About page.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <div class="field">
    <label>Band Photo (JPG, PNG or WEBP, up to 5 MB — a portrait photo around 4:5 works best)</label>
    <?php if (!empty($about['photo'])): ?>
      <img src="../<?= h($about['photo']) ?>" alt="" style="width:140px; height:175px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
    <?php endif; ?>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
  </div>
  <div class="field">
    <label>Photo Position (adjust if the photo looks cropped oddly)</label>
    <select name="image_position">
      <?php $pos = $about['image_position'] ?? 'center'; ?>
      <option value="top" <?= $pos === 'top' ? 'selected' : '' ?>>Top</option>
      <option value="center" <?= $pos === 'center' ? 'selected' : '' ?>>Center</option>
      <option value="bottom" <?= $pos === 'bottom' ? 'selected' : '' ?>>Bottom</option>
    </select>
  </div>
  <button type="submit" class="btn">Save Photo</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
