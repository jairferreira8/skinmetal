<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$shows = read_json('shows');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $date = trim((string)($_POST['date'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $venue = trim((string)($_POST['venue'] ?? ''));
        $ticket_url = trim((string)($_POST['ticket_url'] ?? ''));

        if ($venue === '' || $date === '') {
            $flash = 'err:Date and venue are required.';
        } else {
            $entry = [
                'id' => $id !== '' ? $id : slugify($venue . '-' . $date),
                'date' => $date,
                'city' => $city,
                'venue' => $venue,
                'ticket_url' => $ticket_url,
            ];
            $found = false;
            foreach ($shows as $i => $s) {
                if ($s['id'] === $entry['id']) {
                    $shows[$i] = $entry;
                    $found = true;
                    break;
                }
            }
            if (!$found) $shows[] = $entry;
            usort($shows, fn($a, $b) => strcmp($a['date'], $b['date']));
            write_json('shows', $shows);
            $flash = 'ok:Saved show at "' . $venue . '".';
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $shows = array_values(array_filter($shows, fn($s) => $s['id'] !== $id));
        write_json('shows', $shows);
        $flash = 'ok:Removed.';
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($shows as $s) {
        if ($s['id'] === $_GET['edit']) { $editing = $s; break; }
    }
}

$page_title = 'Shows';
$active = 'live';
include __DIR__ . '/_header.php';
?>
<h1>Shows</h1>
<p class="sub">Upcoming shows for the Live page. Leave this list empty and nothing shows there except the "no shows announced yet" message.</p>

<?php if ($flash): [$kind, $text] = explode(':', $flash, 2); ?>
  <div class="flash <?= $kind === 'ok' ? 'ok' : 'err' ?>"><?= h($text) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;"><?= $editing ? 'Edit Show' : 'Add a Show' ?></h3>
  <form method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <div class="row">
      <div class="field">
        <label>Date (e.g. Mar 14, 2027)</label>
        <input type="text" name="date" value="<?= h($editing['date'] ?? '') ?>" required placeholder="Mar 14, 2027">
      </div>
      <div class="field">
        <label>City</label>
        <input type="text" name="city" value="<?= h($editing['city'] ?? '') ?>" placeholder="São Paulo, Brazil">
      </div>
    </div>
    <div class="field">
      <label>Venue</label>
      <input type="text" name="venue" value="<?= h($editing['venue'] ?? '') ?>" required placeholder="Carioca Club">
    </div>
    <div class="field">
      <label>Ticket link — leave blank to show "More Info Soon"</label>
      <input type="url" name="ticket_url" value="<?= h($editing['ticket_url'] ?? '') ?>" placeholder="https://...">
    </div>
    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Show' ?></button>
    <?php if ($editing): ?><a href="live.php" class="btn secondary">Cancel</a><?php endif; ?>
  </form>
</div>

<table>
  <thead><tr><th>Date</th><th>Venue</th><th>City</th><th></th></tr></thead>
  <tbody>
    <?php if (!$shows): ?>
      <tr><td colspan="4" class="empty-state">No shows scheduled. The Live page will show a "no shows yet" message.</td></tr>
    <?php endif; ?>
    <?php foreach ($shows as $s): ?>
      <tr>
        <td><?= h($s['date']) ?></td>
        <td><strong><?= h($s['venue']) ?></strong></td>
        <td><?= h($s['city'] ?? '') ?></td>
        <td class="actions">
          <a href="?edit=<?= urlencode($s['id']) ?>" class="btn secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Remove this show?');">
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
