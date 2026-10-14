<?php
declare(strict_types=1);

define('DATA_DIR', dirname(__DIR__) . '/data');

function data_path(string $name): string {
    $safe = preg_replace('/[^a-z_]/', '', $name);
    return DATA_DIR . '/' . $safe . '.json';
}

function read_json(string $name, $default = []) {
    $path = data_path($name);
    if (!file_exists($path)) {
        return $default;
    }
    $fh = fopen($path, 'r');
    if (!$fh) return $default;
    flock($fh, LOCK_SH);
    $contents = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode($contents, true);
    return $data === null ? $default : $data;
}

function write_json(string $name, $data): bool {
    $path = data_path($name);
    $fh = fopen($path, 'c+');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : substr(md5(uniqid('', true)), 0, 8);
}

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}
