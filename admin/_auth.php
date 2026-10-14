<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_name(ADMIN_SESSION_NAME);
session_start();

function is_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
