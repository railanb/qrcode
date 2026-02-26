<?php

declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appAuthUser = getenv('APP_AUTH_USER');
$appAuthPass = getenv('APP_AUTH_PASS');
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASSWORD');

if (!defined('DEFAULT_ADMIN_USERNAME')) {
    define('DEFAULT_ADMIN_USERNAME', ($appAuthUser !== false && $appAuthUser !== '') ? $appAuthUser : 'admin');
}
if (!defined('DEFAULT_ADMIN_PASSWORD')) {
    define('DEFAULT_ADMIN_PASSWORD', ($appAuthPass !== false && $appAuthPass !== '') ? $appAuthPass : 'admin123');
}
if (!defined('AUTH_SESSION_TIMEOUT_SECONDS')) {
    define('AUTH_SESSION_TIMEOUT_SECONDS', 1800);
}
if (!defined('AUTH_MAX_LOGIN_ATTEMPTS')) {
    define('AUTH_MAX_LOGIN_ATTEMPTS', 5);
}
if (!defined('AUTH_LOGIN_LOCK_SECONDS')) {
    define('AUTH_LOGIN_LOCK_SECONDS', 300);
}
if (!defined('DB_HOST')) {
    define('DB_HOST', ($dbHost !== false && $dbHost !== '') ? $dbHost : '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', ($dbPort !== false && $dbPort !== '') ? $dbPort : '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', ($dbName !== false && $dbName !== '') ? $dbName : 'qrcode');
}
if (!defined('DB_USER')) {
    define('DB_USER', ($dbUser !== false && $dbUser !== '') ? $dbUser : 'qrcode');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', ($dbPassword !== false) ? $dbPassword : 'qrcode');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';

initialize_database();
