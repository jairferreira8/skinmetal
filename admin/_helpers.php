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

/**
 * Save an uploaded file ($_FILES[$field]) into /assets/<subdir>/ and return its
 * public relative path plus original filename, or null when no file was uploaded.
 * Accepts images, PDFs and ZIP archives. Throws RuntimeException on a real error.
 */
function save_uploaded_file(string $field, string $subdir, string $baseName): ?array {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > 25 * 1024 * 1024) {
        throw new RuntimeException('File must be smaller than 25 MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $extensions = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/zip' => 'zip', 'application/x-zip-compressed' => 'zip',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG, WEBP, PDF or ZIP file.');
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
    return ['url' => 'assets/' . $subdir . '/' . $filename, 'name' => $file['name']];
}

/**
 * Fixed country (ISO 3166-1 alpha-2) -> shipping zone id mapping. Zone prices
 * and names live in data/shipping.json (admin-editable); this list is static.
 * Any country not listed here falls back to "rest-of-world". "BR" is handled
 * separately as domestic, not an international zone.
 */
const COUNTRY_ZONE_MAP = [
    'AR' => 'south-america', 'UY' => 'south-america', 'PY' => 'south-america', 'CL' => 'south-america',
    'BO' => 'south-america', 'PE' => 'south-america', 'CO' => 'south-america', 'VE' => 'south-america',
    'EC' => 'south-america', 'GY' => 'south-america', 'SR' => 'south-america',
    'US' => 'north-america', 'CA' => 'north-america', 'MX' => 'north-america',
    'GT' => 'north-america', 'BZ' => 'north-america', 'SV' => 'north-america', 'HN' => 'north-america',
    'NI' => 'north-america', 'CR' => 'north-america', 'PA' => 'north-america', 'CU' => 'north-america',
    'DO' => 'north-america', 'HT' => 'north-america', 'JM' => 'north-america', 'TT' => 'north-america',
    'PT' => 'europe', 'ES' => 'europe', 'FR' => 'europe', 'DE' => 'europe', 'IT' => 'europe',
    'GB' => 'europe', 'NL' => 'europe', 'BE' => 'europe', 'IE' => 'europe', 'CH' => 'europe',
    'AT' => 'europe', 'SE' => 'europe', 'NO' => 'europe', 'DK' => 'europe', 'FI' => 'europe',
    'PL' => 'europe', 'GR' => 'europe', 'CZ' => 'europe', 'HU' => 'europe', 'RO' => 'europe',
    'JP' => 'asia-oceania', 'CN' => 'asia-oceania', 'KR' => 'asia-oceania', 'IN' => 'asia-oceania',
    'AU' => 'asia-oceania', 'NZ' => 'asia-oceania', 'SG' => 'asia-oceania', 'TH' => 'asia-oceania',
    'ID' => 'asia-oceania', 'PH' => 'asia-oceania', 'VN' => 'asia-oceania', 'MY' => 'asia-oceania',
    'AE' => 'asia-oceania', 'SA' => 'asia-oceania', 'IL' => 'asia-oceania', 'TR' => 'asia-oceania',
];

function country_zone_id(string $countryCode): string {
    $code = strtoupper(trim($countryCode));
    if ($code === 'BR') return 'domestic';
    return COUNTRY_ZONE_MAP[$code] ?? 'rest-of-world';
}

/**
 * Compute shipping cost for a given country and total cart weight.
 * Returns ['zone' => id, 'zone_name' => string, 'price' => float, 'currency' => string].
 */
function calculate_shipping(string $countryCode, float $totalWeightKg): array {
    $shipping = read_json('shipping');
    $zoneId = country_zone_id($countryCode);

    if ($zoneId === 'domestic') {
        $tiers = $shipping['domestic_tiers'] ?? [];
        foreach ($tiers as $tier) {
            if ($totalWeightKg <= (float)$tier['max_weight_kg']) {
                return ['zone' => 'domestic', 'zone_name' => 'Brazil', 'price' => (float)$tier['price'], 'currency' => $shipping['domestic_currency'] ?? 'BRL'];
            }
        }
        $last = end($tiers);
        return ['zone' => 'domestic', 'zone_name' => 'Brazil', 'price' => $last ? (float)$last['price'] : 0, 'currency' => $shipping['domestic_currency'] ?? 'BRL'];
    }

    foreach ($shipping['international_zones'] ?? [] as $zone) {
        if ($zone['id'] === $zoneId) {
            return ['zone' => $zoneId, 'zone_name' => $zone['name'], 'price' => (float)$zone['price'], 'currency' => $shipping['international_currency'] ?? 'USD'];
        }
    }
    foreach ($shipping['international_zones'] ?? [] as $zone) {
        if ($zone['id'] === 'rest-of-world') {
            return ['zone' => 'rest-of-world', 'zone_name' => $zone['name'], 'price' => (float)$zone['price'], 'currency' => $shipping['international_currency'] ?? 'USD'];
        }
    }
    return ['zone' => 'rest-of-world', 'zone_name' => 'Rest of World', 'price' => 0, 'currency' => $shipping['international_currency'] ?? 'USD'];
}
