<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$messages = read_json('messages');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (string)($_POST['id'] ?? '');

    if ($action === 'delete') {
        $messages = array_values(array_filter($messages, fn($m) => $m['id'] !== $id));
        write_json('messages', $messages);
        $flash = 'Deleted.';
    } elseif ($action === 'mark_read') {
        foreach ($messages as &$m) {
            if ($m['id'] === $id) { $m['read'] = true; break; }
        }
        unset($m);
        write_json('messages', $messages);
    }
}

// Newest first
usort($messages, fn($a, $b) => ($b['received_at'] ?? '') <=> ($a['received_at'] ?? ''));

$page_title = 'Messages';
$active = 'messages';
include __DIR__ . '/_header.php';
?>
<h1>Contact Messages</h1>
<p class="sub">Submissions from the site's Contact form.</p>

<?php if ($flash): ?><div class="flash ok"><?= h($flash) ?></div><?php endif; ?>

<table>
  <thead><tr><th>Received</th><th>From</th><th>Subject</th><th>Message</th><th></th></tr></thead>
  <tbody>
    <?php if (!$messages): ?>
      <tr><td colspan="5" class="empty-state">No messages yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($messages as $m): ?>
      <tr>
        <td style="white-space:nowrap;">
          <?= h($m['received_at'] ?? '') ?><br>
          <span class="pill <?= empty($m['read']) ? 'unread' : '' ?>"><?= empty($m['read']) ? 'Unread' : 'Read' ?></span>
        </td>
        <td>
          <strong><?= h($m['name'] ?? '') ?></strong><br>
          <a href="mailto:<?= h($m['email'] ?? '') ?>"><?= h($m['email'] ?? '') ?></a><br>
          <?php if (!empty($m['company'])): ?><span style="color:var(--muted);"><?= h($m['company']) ?></span><br><?php endif; ?>
          <?php if (!empty($m['country'])): ?><span style="color:var(--muted);"><?= h($m['country']) ?></span><?php endif; ?>
        </td>
        <td><?= h($m['subject'] ?? '') ?></td>
        <td class="msg-body"><?= h($m['message'] ?? '') ?></td>
        <td class="actions">
          <?php if (empty($m['read'])): ?>
          <form method="post">
            <input type="hidden" name="action" value="mark_read">
            <input type="hidden" name="id" value="<?= h($m['id']) ?>">
            <button type="submit" class="btn secondary">Mark Read</button>
          </form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('Delete this message?');">
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
