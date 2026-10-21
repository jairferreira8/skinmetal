<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$items = read_json('gallery');
$flash = '';
$sections = ['live' => 'Live Page — On Stage', 'about' => 'About Page — The Chemistry of SKINMETAL'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $caption = trim((string)($_POST['caption'] ?? ''));
        $section = (string)($_POST['section'] ?? 'live');
        if (!isset($sections[$section])) $section = 'live';
        $position = (string)($_POST['image_position'] ?? 'center');
        if (!in_array($position, ['top', 'center', 'bottom'], true)) $position = 'center';

        $entryId = $id !== '' ? $id : slugify($caption !== '' ? $caption : uniqid('photo', false));
        $existingImage = '';
        foreach ($items as $it) {
            if ($it['id'] === $entryId) { $existingImage = $it['image'] ?? ''; break; }
        }
        try {
            $uploaded = save_uploaded_image('image', 'gallery', $caption !== '' ? $caption : $entryId);
        } catch (RuntimeException $e) {
            $uploaded = null;
            $flash = 'err:' . $e->getMessage();
        }
        if ((!isset($flash) || $flash === '') && !$uploaded && !$existingImage) {
            $flash = 'err:Please choose a photo to upload.';
        }
        if (!isset($flash) || $flash === '') {
            $entry = [
                'id' => $entryId,
                'image' => $uploaded ?? $existingImage,
                'caption' => $caption,
                'section' => $section,
                'image_position' => $position,
            ];
            $found = false;
            foreach ($items as $i => $it) {
                if ($it['id'] === $entry['id']) {
                    $items[$i] = $entry;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] = $entry;
            write_json('gallery', $items);
            $flash = 'ok:Saved.';
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $items = array_values(array_filter($items, fn($it) => $it['id'] !== $id));
        write_json('gallery', $items);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($items as $it) {
        if ($it['id'] === $_GET['edit']) { $editing = $it; break; }
    }
}

$page_title = 'Gallery';
$active = 'gallery';
include __DIR__ . '/_header.php';
?>
<h1>Gallery</h1>
<p class="sub">Photos shown in the "On Stage" gallery on the Live page and "The Chemistry of SKINMETAL" gallery on the About page.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Photo' : 'Add a Photo' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field">
        <label>Shown On</label>
        <select name="section">
          <?php foreach ($sections as $key => $label): ?>
            <option value="<?= h($key) ?>" <?= ($editing['section'] ?? 'live') === $key ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:2;">
        <label>Caption (optional, used as the photo's alt text)</label>
        <input type="text" name="caption" value="<?= h($editing['caption'] ?? '') ?>" placeholder="SKINMETAL live">
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Photo (JPG, PNG or WEBP, up to 5 MB)</label>
        <?php if (!empty($editing['image'])): ?>
          <img src="../<?= h($editing['image']) ?>" alt="" style="width:80px; height:80px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" <?= $editing ? '' : 'required' ?>>
      </div>
      <div class="field">
        <label>Photo Position (adjust if the photo looks cropped oddly)</label>
        <select name="image_position">
          <?php $pos = $editing['image_position'] ?? 'center'; ?>
          <option value="top" <?= $pos === 'top' ? 'selected' : '' ?>>Top</option>
          <option value="center" <?= $pos === 'center' ? 'selected' : '' ?>>Center</option>
          <option value="bottom" <?= $pos === 'bottom' ? 'selected' : '' ?>>Bottom</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Photo' ?></button>
    <?php if ($editing): ?><a href="gallery.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Photo</th><th>Shown On</th><th>Caption</th><th></th></tr></thead>
  <tbody>
    <?php if (!$items): ?>
      <tr><td colspan="4" class="empty-state">No photos yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?php if (!empty($it['image'])): ?><img src="../<?= h($it['image']) ?>" alt="" style="width:46px; height:46px; object-fit:cover; border-radius:4px;"><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td><?= h($sections[$it['section'] ?? 'live'] ?? $it['section'] ?? '') ?></td>
        <td><?= h($it['caption'] ?? '') ?></td>
        <td class="actions">
          <a href="?edit=<?= urlencode($it['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this photo?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
