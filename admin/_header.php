<?php
/** Expects $page_title and $active to be set before include. */
require_once __DIR__ . '/_icons.php';
$nav = [
    'home' => ['index.php', 'dashboard', 'Dashboard'],
    'release' => ['release.php', 'release', 'Featured Release'],
    'music' => ['music.php', 'music', 'Music'],
    'videos' => ['videos.php', 'videos', 'Videos'],
    'merch' => ['merch.php', 'products', 'Products'],
    'live' => ['live.php', 'shows', 'Shows'],
    'members' => ['members.php', 'members', 'Band Members'],
    'characters' => ['characters.php', 'characters', 'Characters'],
    'gallery' => ['gallery.php', 'gallery', 'Gallery'],
    'about' => ['about.php', 'about', 'About Page'],
    'media' => ['media.php', 'media', 'Media Center'],
    'links' => ['links.php', 'links', 'Streaming Links'],
    'messages' => ['messages.php', 'messages', 'Messages'],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($page_title ?? 'Admin') ?> — SKINMETAL Admin</title>
<link rel="stylesheet" href="admin.css?v=20261015g">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="sidebar-brand notranslate" translate="no">SKIN<span>METAL</span></div>
    <nav class="sidebar-nav">
      <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
        <a href="<?= h($href) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>"><?= admin_icon($icon) ?><span class="label-text"><?= h($label) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php" class="btn secondary"><?= admin_icon('logout') ?> <span class="label-text">Log Out</span></a>
    </div>
  </aside>
  <div class="admin-content">
    <div class="admin-main">
