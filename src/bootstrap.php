<?php

declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');

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
$appStorageDir = getenv('APP_STORAGE_DIR');

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
if (!defined('APP_STORAGE_DIR')) {
    define(
        'APP_STORAGE_DIR',
        ($appStorageDir !== false && $appStorageDir !== '')
            ? $appStorageDir
            : dirname(__DIR__) . '/storage'
    );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';

initialize_database();
