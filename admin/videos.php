<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$videos = read_json('videos');
$flash = '';
$categories = [
    'music-video' => 'Music Video', 'lyric' => 'Lyric Video', 'live' => 'Live',
    'short-film' => 'Short Film', 'metal-tales' => 'Metal Tales', 'bts' => 'Behind the Scenes',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $meta = trim((string)($_POST['meta'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'music-video'));
        $watch_url = trim((string)($_POST['watch_url'] ?? ''));

        if ($title === '') {
            $flash = 'err:Title is required.';
        } else {
            $entry = [
                'id' => $id !== '' ? $id : slugify($title),
                'title' => $title,
                'meta' => $meta,
                'category' => $category,
                'watch_url' => $watch_url,
            ];
            $found = false;
            foreach ($videos as $i => $v) {
                if ($v['id'] === $entry['id']) {
                    $videos[$i] = $entry;
                    $found = true;
                    break;
                }
            }
            if (!$found) $videos[] = $entry;
            write_json('videos', $videos);
            $flash = 'ok:Saved "' . $title . '".';
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $videos = array_values(array_filter($videos, fn($v) => $v['id'] !== $id));
        write_json('videos', $videos);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($videos as $v) {
        if ($v['id'] === $_GET['edit']) { $editing = $v; break; }
    }
}

$page_title = 'Videos';
$active = 'videos';
include __DIR__ . '/_header.php';
?>
<h1>Videos</h1>
<p class="sub">Shown on the Videos page. Paste a YouTube link and its thumbnail appears automatically on the public site — no image upload needed. The featured native video player at the top of the Videos page is managed separately (it's the uploaded file, not listed here).</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Video' : 'Add a Video' ?></h3>
  <form method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field" style="flex:2;">
        <label>Title</label>
        <input type="text" name="title" value="<?= h($editing['title'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Category</label>
        <select name="category">
          <?php foreach ($categories as $key => $label): ?>
            <option value="<?= h($key) ?>" <?= ($editing['category'] ?? '') === $key ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Caption (shown under the title)</label>
      <input type="text" name="meta" value="<?= h($editing['meta'] ?? '') ?>" placeholder="Official Music Video">
    </div>
    <div class="field">
      <label>YouTube (or other) link — leave blank for "coming soon"</label>
      <input type="url" name="watch_url" value="<?= h($editing['watch_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Video' ?></button>
    <?php if ($editing): ?><a href="videos.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Title</th><th>Category</th><th>Link</th><th></th></tr></thead>
  <tbody>
    <?php if (!$videos): ?>
      <tr><td colspan="4" class="empty-state">No videos yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($videos as $v): ?>
      <tr>
        <td><strong><?= h($v['title']) ?></strong><br><span style="color:var(--muted);"><?= h($v['meta'] ?? '') ?></span></td>
        <td><?= h($categories[$v['category']] ?? $v['category']) ?></td>
        <td><?php if (!empty($v['watch_url'])): ?><span class="pill">Linked</span><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td class="actions">
          <a href="?edit=<?= urlencode($v['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this video?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($v['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
