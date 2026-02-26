<?php

declare(strict_types=1);

function get_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }

    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_authenticated(): bool
{
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function enforce_session_timeout(): bool
{
    if (!is_authenticated()) {
        return true;
    }

    $lastActivity = (int)($_SESSION['last_activity_at'] ?? 0);
    $now = time();
    if ($lastActivity > 0 && ($now - $lastActivity) > AUTH_SESSION_TIMEOUT_SECONDS) {
        logout_user();
        return false;
    }

    $_SESSION['last_activity_at'] = $now;
    return true;
}

function get_login_lock_remaining_seconds_for_username(string $username): int
{
    return get_user_lock_remaining_seconds($username);
}

function attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $user = get_user_by_username($username);
    if (!is_array($user)) {
        return false;
    }

    if ((int)($user['is_active'] ?? 0) !== 1) {
        return false;
    }

    if (get_user_lock_remaining_seconds($username) > 0) {
        return false;
    }

    $hash = (string)($user['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        register_failed_login_attempt_for_user((int)$user['id'], (int)($user['failed_login_count'] ?? 0));
        return false;
    }

    register_successful_login_for_user((int)$user['id']);
    session_regenerate_id(true);
    $_SESSION['authenticated'] = true;
    $_SESSION['auth_user'] = (string)$user['username'];
    $_SESSION['last_activity_at'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, (string)$params['path'], (string)$params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }

    session_destroy();
}
