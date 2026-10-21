<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$songs = read_json('songs');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $type = trim((string)($_POST['type'] ?? 'Single'));
        $meta = trim((string)($_POST['meta'] ?? ''));
        $listen_url = trim((string)($_POST['listen_url'] ?? ''));
        $watch_url = trim((string)($_POST['watch_url'] ?? ''));

        if ($title === '') {
            $flash = 'err:Title is required.';
        } else {
            $entryId = $id !== '' ? $id : slugify($title);
            $existingImage = '';
            foreach ($songs as $s) {
                if ($s['id'] === $entryId) { $existingImage = $s['image'] ?? ''; break; }
            }
            try {
                $uploaded = save_uploaded_image('image', 'songs', $title);
            } catch (RuntimeException $e) {
                $uploaded = null;
                $flash = 'err:' . $e->getMessage();
            }
            $position = (string)($_POST['image_position'] ?? 'center');
            if (!in_array($position, ['top', 'center', 'bottom'], true)) $position = 'center';
            if (!isset($flash) || $flash === '') {
                $entry = [
                    'id' => $entryId,
                    'title' => $title,
                    'type' => $type,
                    'meta' => $meta !== '' ? $meta : $type,
                    'listen_url' => $listen_url,
                    'watch_url' => $watch_url,
                    'image' => $uploaded ?? $existingImage,
                    'image_position' => $position,
                ];
                $found = false;
                foreach ($songs as $i => $s) {
                    if ($s['id'] === $entry['id']) {
                        $songs[$i] = $entry;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $songs[] = $entry;
                write_json('songs', $songs);
                $flash = 'ok:Saved "' . $title . '".';
            }
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $songs = array_values(array_filter($songs, fn($s) => $s['id'] !== $id));
        write_json('songs', $songs);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($songs as $s) {
        if ($s['id'] === $_GET['edit']) { $editing = $s; break; }
    }
}

$page_title = 'Music';
$active = 'music';
include __DIR__ . '/_header.php';
?>
<h1>Music</h1>
<p class="sub">Songs and releases shown on the Music page. The "Watch" link can point to videos.html, a specific YouTube video, or a Universe page anchor.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Release' : 'Add a Release' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field" style="flex:2;">
        <label>Title</label>
        <input type="text" name="title" value="<?= h($editing['title'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Type</label>
        <select name="type">
          <?php foreach (['Single', 'Album', 'Live Album'] as $t): ?>
            <option value="<?= h($t) ?>" <?= ($editing['type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Meta / Caption (shown under the title, e.g. "Album · 11 Songs")</label>
      <input type="text" name="meta" value="<?= h($editing['meta'] ?? '') ?>" placeholder="Single">
    </div>
    <div class="row">
      <div class="field">
        <label>Listen link (Spotify / YouTube / etc.) — leave blank to hide</label>
        <input type="url" name="listen_url" value="<?= h($editing['listen_url'] ?? '') ?>" placeholder="https://...">
      </div>
      <div class="field">
        <label>Watch link</label>
        <input type="text" name="watch_url" value="<?= h($editing['watch_url'] ?? 'videos.html') ?>" placeholder="videos.html">
      </div>
    </div>
    <div class="field">
      <label>Cover Image (JPG, PNG or WEBP, up to 5 MB) — optional, leave empty to auto-use the YouTube thumbnail when the link above is a YouTube video</label>
      <?php if (!empty($editing['image'])): ?>
        <img src="../<?= h($editing['image']) ?>" alt="" style="width:80px; height:80px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
      <?php endif; ?>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <div class="field">
      <label>Cover Position (adjust if the photo looks cropped oddly)</label>
      <select name="image_position">
        <?php $pos = $editing['image_position'] ?? 'center'; ?>
        <option value="top" <?= $pos === 'top' ? 'selected' : '' ?>>Top</option>
        <option value="center" <?= $pos === 'center' ? 'selected' : '' ?>>Center</option>
        <option value="bottom" <?= $pos === 'bottom' ? 'selected' : '' ?>>Bottom</option>
      </select>
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Release' ?></button>
    <?php if ($editing): ?><a href="music.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Cover</th><th>Title</th><th>Type</th><th>Links</th><th></th></tr></thead>
  <tbody>
    <?php if (!$songs): ?>
      <tr><td colspan="5" class="empty-state">No releases yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($songs as $s): ?>
      <tr>
        <td><?php if (!empty($s['image'])): ?><img src="../<?= h($s['image']) ?>" alt="" style="width:46px; height:46px; object-fit:cover; border-radius:4px;"><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td><strong><?= h($s['title']) ?></strong><br><span style="color:var(--muted);"><?= h($s['meta'] ?? '') ?></span></td>
        <td><?= h($s['type'] ?? '') ?></td>
        <td>
          <?php if (!empty($s['listen_url'])): ?><span class="pill">Listen</span><?php endif; ?>
          <?php if (!empty($s['watch_url'])): ?><span class="pill">Watch</span><?php endif; ?>
        </td>
        <td class="actions">
          <a href="?edit=<?= urlencode($s['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this release?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($s['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
