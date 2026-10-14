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

/**
 * Save an uploaded image ($_FILES[$field]) into /assets/<subdir>/ and return its
 * public relative path (e.g. "assets/products/tecno-metal-tee-ab12.jpg"), or null
 * when no file was uploaded. Throws RuntimeException on a real upload error.
 */
function save_uploaded_image(string $field, string $subdir, string $baseName): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must be smaller than 5 MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG or WEBP image.');
    }
    $dir = dirname(__DIR__) . '/assets/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = slugify($baseName) . '-' . substr(md5(uniqid('', true)), 0, 6) . '.' . $extensions[$mime];
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return 'assets/' . $subdir . '/' . $filename;
}
