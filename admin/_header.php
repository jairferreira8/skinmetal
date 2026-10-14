<?php
/** Expects $page_title and $active to be set before include. */
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($page_title ?? 'Admin') ?> — SKINMETAL Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-header">
  <div class="brand">SKIN<span>METAL</span> — Admin</div>
  <a href="logout.php" class="btn secondary">Log Out</a>
</div>
<div class="admin-nav">
  <a href="index.php" class="<?= ($active ?? '') === 'home' ? 'active' : '' ?>">Dashboard</a>
  <a href="music.php" class="<?= ($active ?? '') === 'music' ? 'active' : '' ?>">Music</a>
  <a href="merch.php" class="<?= ($active ?? '') === 'merch' ? 'active' : '' ?>">Products</a>
  <a href="live.php" class="<?= ($active ?? '') === 'live' ? 'active' : '' ?>">Shows</a>
  <a href="links.php" class="<?= ($active ?? '') === 'links' ? 'active' : '' ?>">Streaming Links</a>
  <a href="messages.php" class="<?= ($active ?? '') === 'messages' ? 'active' : '' ?>">Messages</a>
</div>
<div class="admin-main">
