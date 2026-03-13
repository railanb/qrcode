<?php

declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

$configFile = dirname(dirname(__DIR__)) . '/config/app.php';
$config = [];
if (is_file($configFile)) {
    $loadedConfig = require $configFile;
    if (is_array($loadedConfig)) {
        $config = $loadedConfig;
    }
}

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
$configuredAuthUser = $config['auth']['initial_username'] ?? null;
$configuredAuthPass = $config['auth']['initial_password'] ?? null;
$configuredStorageDir = $config['storage']['dir'] ?? null;

if (!defined('DEFAULT_ADMIN_USERNAME')) {
    define(
        'DEFAULT_ADMIN_USERNAME',
        ($appAuthUser !== false && $appAuthUser !== '')
            ? $appAuthUser
            : (is_string($configuredAuthUser) && $configuredAuthUser !== '' ? $configuredAuthUser : 'admin')
    );
}
if (!defined('DEFAULT_ADMIN_PASSWORD')) {
    define(
        'DEFAULT_ADMIN_PASSWORD',
        ($appAuthPass !== false && $appAuthPass !== '')
            ? $appAuthPass
            : (is_string($configuredAuthPass) && $configuredAuthPass !== '' ? $configuredAuthPass : 'admin123')
    );
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
            : (is_string($configuredStorageDir) && $configuredStorageDir !== ''
                ? $configuredStorageDir
                : dirname(dirname(__DIR__)) . '/storage')
    );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';

initialize_database();
