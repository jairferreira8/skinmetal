<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$products = read_json('products');
$flash = '';
$categories = ['tshirts' => 'T-Shirts', 'hoodies' => 'Hoodies', 'posters' => 'Posters', 'albums' => 'Albums', 'limited' => 'Limited Editions'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $price = trim((string)($_POST['price'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'tshirts'));

        if ($name === '') {
            $flash = 'err:Name is required.';
        } else {
            $existingImage = '';
            foreach ($products as $p) {
                if ($p['id'] === ($id !== '' ? $id : slugify($name))) { $existingImage = $p['image'] ?? ''; break; }
            }
            try {
                $uploaded = save_uploaded_image('image', 'products', $name);
            } catch (RuntimeException $e) {
                $uploaded = null;
                $flash = 'err:' . $e->getMessage();
            }
            if (!isset($flash) || $flash === '') {
                $entry = [
                    'id' => $id !== '' ? $id : slugify($name),
                    'name' => $name,
                    'price' => $price,
                    'category' => $category,
                    'image' => $uploaded ?? $existingImage,
                ];
                $found = false;
                foreach ($products as $i => $p) {
                    if ($p['id'] === $entry['id']) {
                        $products[$i] = $entry;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $products[] = $entry;
                write_json('products', $products);
                $flash = 'ok:Saved "' . $name . '".';
            }
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $products = array_values(array_filter($products, fn($p) => $p['id'] !== $id));
        write_json('products', $products);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($products as $p) {
        if ($p['id'] === $_GET['edit']) { $editing = $p; break; }
    }
}

$page_title = 'Products';
$active = 'merch';
include __DIR__ . '/_header.php';
?>
<h1>Products</h1>
<p class="sub">Items shown on the Merch page.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Product' : 'Add a Product' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field" style="flex:2;">
        <label>Name</label>
        <input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Price</label>
        <input type="text" name="price" value="<?= h($editing['price'] ?? '') ?>" placeholder="$34.00">
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
      <label>Product Photo (JPG, PNG or WEBP, up to 5 MB)</label>
      <?php if (!empty($editing['image'])): ?>
        <img src="../<?= h($editing['image']) ?>" alt="" style="width:80px; height:80px; object-fit:cover; border-radius:4px; display:block; margin-bottom:8px;">
      <?php endif; ?>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Product' ?></button>
    <?php if ($editing): ?><a href="merch.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th></th></tr></thead>
  <tbody>
    <?php if (!$products): ?>
      <tr><td colspan="5" class="empty-state">No products yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?php if (!empty($p['image'])): ?><img src="../<?= h($p['image']) ?>" alt="" style="width:46px; height:46px; object-fit:cover; border-radius:4px;"><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
        <td><strong><?= h($p['name']) ?></strong></td>
        <td><?= h($categories[$p['category']] ?? $p['category']) ?></td>
        <td><?= h($p['price'] ?? '') ?></td>
        <td class="actions">
          <a href="?edit=<?= urlencode($p['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this product?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <button type="submit" class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
