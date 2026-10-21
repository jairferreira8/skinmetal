<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$members = read_json('members');
$flash = '';
$CARD_SLOTS = 6;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $position = (string)($_POST['image_position'] ?? 'center');
        if (!in_array($position, ['top', 'center', 'bottom'], true)) $position = 'center';

        if ($name === '') {
            $flash = 'err:Name is required.';
        } else {
            $entryId = $id !== '' ? $id : slugify($name);
            $existingPhoto = '';
            foreach ($members as $m) {
                if ($m['id'] === $entryId) { $existingPhoto = $m['photo'] ?? ''; break; }
            }
            try {
                $uploaded = save_uploaded_image('photo', 'members', $name);
            } catch (RuntimeException $e) {
                $uploaded = null;
                $flash = 'err:' . $e->getMessage();
            }
            if (!isset($flash) || $flash === '') {
                $cards = [];
                for ($i = 1; $i <= $CARD_SLOTS; $i++) {
                    $label = trim((string)($_POST['card_label_' . $i] ?? ''));
                    $text = trim((string)($_POST['card_text_' . $i] ?? ''));
                    if ($label !== '' && $text !== '') {
                        $cards[] = ['label' => $label, 'text' => $text];
                    }
                }
                $entry = [
                    'id' => $entryId,
                    'name' => $name,
                    'role' => $role,
                    'photo' => $uploaded ?? $existingPhoto,
                    'image_position' => $position,
                    'bio' => $bio,
                    'cards' => $cards,
                ];
                $found = false;
                foreach ($members as $i => $m) {
                    if ($m['id'] === $entry['id']) {
                        $members[$i] = $entry;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $members[] = $entry;
                write_json('members', $members);
                $flash = 'ok:Saved "' . $name . '".';
            }
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $members = array_values(array_filter($members, fn($m) => $m['id'] !== $id));
        write_json('members', $members);
        $flash = 'ok:Removed.';
    } elseif ($action === 'move') {
        $id = (string)($_POST['id'] ?? '');
        $dir = (string)($_POST['dir'] ?? '');
        $idx = null;
        foreach ($members as $i => $m) { if ($m['id'] === $id) { $idx = $i; break; } }
        if ($idx !== null) {
            $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($swap >= 0 && $swap < count($members)) {
                [$members[$idx], $members[$swap]] = [$members[$swap], $members[$idx]];
                write_json('members', $members);
            }
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($members as $m) {
        if ($m['id'] === $_GET['edit']) { $editing = $m; break; }
    }
}
$editCards = $editing['cards'] ?? [];

$page_title = 'Band Members';
$active = 'members';
include __DIR__ . '/_header.php';
?>
<h1>Band Members</h1>
<p class="sub">The "Meet the Band" lineup shown on the About page — photo, bio and quick-fact cards for each member.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Member' : 'Add a Member' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field" style="flex:2;">
        <label>Name</label>
        <input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Role</label>
        <input type="text" name="role" value="<?= h($editing['role'] ?? '') ?>" placeholder="Drummer (Virtual)">
      </div>
    </div>
    <div class="field">
      <label>Bio (shown as paragraphs on the About page — leave a blank line between paragraphs)</label>
      <textarea name="bio" rows="6"><?= h($editing['bio'] ?? '') ?></textarea>
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
        <label>Photo Position (adjust if the photo looks cropped oddly)</label>
        <select name="image_position">
          <?php $pos = $editing['image_position'] ?? 'center'; ?>
          <option value="top" <?= $pos === 'top' ? 'selected' : '' ?>>Top</option>
          <option value="center" <?= $pos === 'center' ? 'selected' : '' ?>>Center</option>
          <option value="bottom" <?= $pos === 'bottom' ? 'selected' : '' ?>>Bottom</option>
        </select>
      </div>
    </div>
    <h4 style="margin:18px 0 8px;">Quick-Fact Cards (optional — e.g. "Favorite Bands", "Inspirations")</h4>
    <?php for ($i = 1; $i <= $CARD_SLOTS; $i++): $c = $editCards[$i - 1] ?? ['label' => '', 'text' => '']; ?>
      <div class="row">
        <div class="field">
          <label>Card <?= $i ?> Label</label>
          <input type="text" name="card_label_<?= $i ?>" value="<?= h($c['label']) ?>" placeholder="Favorite Bands">
        </div>
        <div class="field" style="flex:2;">
          <label>Card <?= $i ?> Text</label>
          <input type="text" name="card_text_<?= $i ?>" value="<?= h($c['text']) ?>" placeholder="Iron Maiden, Slayer, Dream Theater">
        </div>
      </div>
    <?php endfor; ?>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Member' ?></button>
    <?php if ($editing): ?><a href="members.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Cards</th><th></th></tr></thead>
  <tbody>
    <?php if (!$members): ?>
      <tr><td colspan="5" class="empty-state">No members yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($members as $m): ?>
      <tr>
        <td><?php if (!empty($m['photo'])): ?><img src="../<?= h($m['photo']) ?>" alt="" style="width:46px; height:46px; object-fit:cover; border-radius:4px;"><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td><strong><?= h($m['name']) ?></strong></td>
        <td><?= h($m['role'] ?? '') ?></td>
        <td><?= count($m['cards'] ?? []) ?></td>
        <td class="actions">
          <form method="post" style="display:inline;"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><input type="hidden" name="dir" value="up"><button type="submit" class="btn secondary">↑</button></form>
          <form method="post" style="display:inline;"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><input type="hidden" name="dir" value="down"><button type="submit" class="btn secondary">↓</button></form>
          <a href="?edit=<?= urlencode($m['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this member?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($m['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
