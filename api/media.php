<?php
declare(strict_types=1);
require_once __DIR__ . '/../admin/_helpers.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(read_json('media'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
