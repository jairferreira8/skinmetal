<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_name(ADMIN_SESSION_NAME);
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect password.';
}

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login — SKINMETAL</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-wrap">
  <h1 style="text-align:center; margin-bottom:24px;">SKIN<span style="color:var(--accent)">METAL</span> Admin</h1>
  <div class="card">
    <?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autofocus>
      </div>
      <button type="submit" class="btn" style="width:100%;">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
