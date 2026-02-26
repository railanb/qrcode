<?php

declare(strict_types=1);

function get_codes(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, type, payload, source, uploaded_filename, preview_url, svg_url, created_at FROM qrcodes ORDER BY created_at DESC');
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function save_code(array $record): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO qrcodes (id, type, payload, source, uploaded_filename, preview_url, svg_url, created_at)
         VALUES (:id, :type, :payload, :source, :uploaded_filename, :preview_url, :svg_url, :created_at)'
    );

    $ok = $stmt->execute([
        ':id' => (string)($record['id'] ?? ''),
        ':type' => (string)($record['type'] ?? ''),
        ':payload' => (string)($record['payload'] ?? ''),
        ':source' => (string)($record['source'] ?? 'manual'),
        ':uploaded_filename' => isset($record['uploaded_filename']) && $record['uploaded_filename'] !== null
            ? (string)$record['uploaded_filename']
            : null,
        ':preview_url' => (string)($record['preview_url'] ?? ''),
        ':svg_url' => (string)($record['svg_url'] ?? ''),
        ':created_at' => (string)($record['created_at'] ?? ''),
    ]);

    if (!$ok) {
        throw new RuntimeException('Falha ao salvar o QRCode no banco.');
    }
}

function get_code_by_id(string $id): ?array
{
    if ($id === '') {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, type, payload, source, uploaded_filename, preview_url, svg_url, created_at
         FROM qrcodes WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function delete_code(string $id): void
{
    if ($id === '') {
        return;
    }

    $pdo = get_pdo();
    $toDelete = get_code_by_id($id);
    $stmt = $pdo->prepare('DELETE FROM qrcodes WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if (is_array($toDelete)) {
        delete_generated_assets($toDelete);
    }
}

function initialize_database(): void
{
    $server = get_server_pdo();
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $pdo = get_pdo();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS qrcodes (
            id VARCHAR(32) NOT NULL PRIMARY KEY,
            type VARCHAR(20) NOT NULL,
            payload TEXT NOT NULL,
            source VARCHAR(20) NOT NULL,
            uploaded_filename VARCHAR(255) NULL,
            preview_url TEXT NOT NULL,
            svg_url TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
            locked_until DATETIME NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    ensure_default_user_exists();
}

function get_user_by_username(string $username): ?array
{
    $username = trim($username);
    if ($username === '') {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, is_active, failed_login_count, locked_until, last_login_at, created_at, updated_at
         FROM users WHERE username = :username LIMIT 1'
    );
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function get_user_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, is_active, failed_login_count, locked_until, last_login_at, created_at, updated_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function get_user_lock_remaining_seconds(string $username): int
{
    $user = get_user_by_username($username);
    if (!is_array($user)) {
        return 0;
    }

    $lockedUntil = (string)($user['locked_until'] ?? '');
    if ($lockedUntil === '') {
        return 0;
    }

    $lockedAt = strtotime($lockedUntil);
    if ($lockedAt === false) {
        return 0;
    }

    $remaining = $lockedAt - time();
    return $remaining > 0 ? $remaining : 0;
}

function register_failed_login_attempt_for_user(int $userId, int $failedCount): void
{
    $pdo = get_pdo();
    $newCount = $failedCount + 1;

    if ($newCount >= AUTH_MAX_LOGIN_ATTEMPTS) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET failed_login_count = 0, locked_until = DATE_ADD(NOW(), INTERVAL :lock_seconds SECOND), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->bindValue(':lock_seconds', AUTH_LOGIN_LOCK_SECONDS, PDO::PARAM_INT);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return;
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_login_count = :failed_count, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->bindValue(':failed_count', $newCount, PDO::PARAM_INT);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

function register_successful_login_for_user(int $userId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW(), updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

function update_user_credentials(int $userId, string $newUsername, string $newPasswordHash): void
{
    if ($userId <= 0) {
        throw new RuntimeException('Usuario invalido.');
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET username = :username, password_hash = :password_hash, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->bindValue(':username', $newUsername, PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $newPasswordHash, PDO::PARAM_STR);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

function ensure_default_user_exists(): void
{
    $pdo = get_pdo();
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $username = trim((string)DEFAULT_ADMIN_USERNAME);
    $plainPassword = (string)DEFAULT_ADMIN_PASSWORD;
    if ($username === '' || $plainPassword === '') {
        throw new RuntimeException('Usuario inicial invalido.');
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Falha ao gerar hash da senha inicial.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, is_active, failed_login_count, locked_until, last_login_at, created_at, updated_at)
         VALUES (:username, :password_hash, 1, 0, NULL, NULL, NOW(), NOW())'
    );
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $passwordHash,
    ]);
}

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function get_server_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
