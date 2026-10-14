<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$release = read_json('release', []);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $track_count = trim((string)($_POST['track_count'] ?? ''));
    $release_date = trim((string)($_POST['release_date'] ?? ''));
    $description_pre = trim((string)($_POST['description_pre'] ?? ''));
    $description_post = trim((string)($_POST['description_post'] ?? ''));
    $listen_url = trim((string)($_POST['listen_url'] ?? ''));
    $trailer_url = trim((string)($_POST['trailer_url'] ?? ''));

    if ($name === '' || $release_date === '') {
        $flash = 'err:Release name and date are required.';
    } else {
        try {
            $uploadedCover = save_uploaded_image('cover_image', 'release', $name);
        } catch (RuntimeException $e) {
            $uploadedCover = null;
            $flash = 'err:' . $e->getMessage();
        }
        if ($flash === '') {
            $release = [
                'name' => $name,
                'track_count' => $track_count,
                'release_date' => $release_date,
                'description_pre' => $description_pre,
                'description_post' => $description_post,
                'cover_image' => $uploadedCover ?? ($release['cover_image'] ?? ''),
                'listen_url' => $listen_url,
                'trailer_url' => $trailer_url,
            ];
            write_json('release', $release);
            $flash = 'ok:Featured release updated.';
        }
    }
}

$page_title = 'Featured Release';
$active = 'release';
include __DIR__ . '/_header.php';
?>
<h1>Featured Release</h1>
<p class="sub">This is the release spotlighted on the Home page hero and the Music page's "Featured Release" section — the one you want fans to see first.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <div class="row">
    <div class="field" style="flex:2;">
      <label>Release Name</label>
      <input type="text" name="name" value="<?= h($release['name'] ?? '') ?>" required placeholder="Dark Side">
    </div>
    <div class="field">
      <label>Track Count</label>
      <input type="text" name="track_count" value="<?= h($release['track_count'] ?? '') ?>" placeholder="12">
    </div>
    <div class="field">
      <label>Release Date</label>
      <input type="date" name="release_date" value="<?= h($release['release_date'] ?? '') ?>" required>
    </div>
  </div>

  <div class="field">
    <label>Description — before release (shown while the countdown is running)</label>
    <textarea name="description_pre" rows="3"><?= h($release['description_pre'] ?? '') ?></textarea>
  </div>
  <div class="field">
    <label>Description — after release (shown once the release date has passed)</label>
    <textarea name="description_post" rows="3"><?= h($release['description_post'] ?? '') ?></textarea>
  </div>

  <div class="field">
    <label>Cover Art (JPG, PNG or WEBP, up to 5 MB)</label>
    <?php if (!empty($release['cover_image'])): ?>
      <img src="../<?= h($release['cover_image']) ?>" alt="" style="width:100px; height:100px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
    <?php endif; ?>
    <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
  </div>

  <div class="row">
    <div class="field">
      <label>Pre-Save / Listen link (Spotify, etc.)</label>
      <input type="url" name="listen_url" value="<?= h($release['listen_url'] ?? '') ?>" placeholder="https://open.spotify.com/album/...">
    </div>
    <div class="field">
      <label>Trailer / Video link</label>
      <input type="url" name="trailer_url" value="<?= h($release['trailer_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
    </div>
  </div>

  <button type="submit" class="btn">Save Featured Release</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
