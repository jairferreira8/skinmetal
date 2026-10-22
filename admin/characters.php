<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$characters = read_json('characters');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $link_label = trim((string)($_POST['link_label'] ?? ''));
        $link_url = trim((string)($_POST['link_url'] ?? ''));
        $position = (string)($_POST['image_position'] ?? 'center');
        if (!in_array($position, ['top', 'center', 'bottom'], true)) $position = 'center';

        if ($name === '') {
            $flash = 'err:Name is required.';
        } else {
            $entryId = $id !== '' ? $id : slugify($name);
            $existingPhoto = '';
            foreach ($characters as $c) {
                if ($c['id'] === $entryId) { $existingPhoto = $c['photo'] ?? ''; break; }
            }
            try {
                $uploaded = save_uploaded_image('photo', 'characters', $name);
            } catch (RuntimeException $e) {
                $uploaded = null;
                $flash = 'err:' . $e->getMessage();
            }
            if (!isset($flash) || $flash === '') {
                $entry = [
                    'id' => $entryId,
                    'name' => $name,
                    'role' => $role,
                    'photo' => $uploaded ?? $existingPhoto,
                    'image_position' => $position,
                    'link_label' => $link_label,
                    'link_url' => $link_url,
                ];
                $found = false;
                foreach ($characters as $i => $c) {
                    if ($c['id'] === $entry['id']) {
                        $characters[$i] = $entry;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $characters[] = $entry;
                write_json('characters', $characters);
                $flash = 'ok:Saved "' . $name . '".';
            }
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $characters = array_values(array_filter($characters, fn($c) => $c['id'] !== $id));
        write_json('characters', $characters);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($characters as $c) {
        if ($c['id'] === $_GET['edit']) { $editing = $c; break; }
    }
}

$page_title = 'Characters';
$active = 'characters';
include __DIR__ . '/_header.php';
?>
<h1>Characters</h1>
<p class="sub">The mythology characters shown on The Universe page. If no photo is uploaded and the link is a YouTube video, the video's thumbnail is used automatically.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Character' : 'Add a Character' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field" style="flex:2;">
        <label>Name</label>
        <input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>" required>
      </div>
      <div class="field" style="flex:2;">
        <label>Role / Title</label>
        <input type="text" name="role" value="<?= h($editing['role'] ?? '') ?>" placeholder="Warrior of the North">
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Photo (JPG, PNG or WEBP, up to 5 MB)</label>
        <?php if (!empty($editing['photo'])): ?>
          <img src="../<?= h($editing['photo']) ?>" alt="" style="width:80px; height:80px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
      </div>
      <div class="field">
        <label>Photo Position</label>
        <select name="image_position">
          <?php $pos = $editing['image_position'] ?? 'center'; ?>
          <option value="top" <?= $pos === 'top' ? 'selected' : '' ?>>Top</option>
          <option value="center" <?= $pos === 'center' ? 'selected' : '' ?>>Center</option>
          <option value="bottom" <?= $pos === 'bottom' ? 'selected' : '' ?>>Bottom</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Link Label (optional, e.g. "Watch the Saga")</label>
        <input type="text" name="link_label" value="<?= h($editing['link_label'] ?? '') ?>">
      </div>
      <div class="field" style="flex:2;">
        <label>Link URL (optional, YouTube link used as photo fallback too)</label>
        <input type="url" name="link_url" value="<?= h($editing['link_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
      </div>
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Character' ?></button>
    <?php if ($editing): ?><a href="characters.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th></th></tr></thead>
  <tbody>
    <?php if (!$characters): ?>
      <tr><td colspan="4" class="empty-state">No characters yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($characters as $c): ?>
      <tr>
        <td><?php if (!empty($c['photo'])): ?><img src="../<?= h($c['photo']) ?>" alt="" style="width:46px; height:46px; object-fit:cover; border-radius:4px;"><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td><strong><?= h($c['name']) ?></strong></td>
        <td><?= h($c['role'] ?? '') ?></td>
        <td class="actions">
          <a href="?edit=<?= urlencode($c['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this character?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($c['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
