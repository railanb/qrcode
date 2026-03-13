<?php

declare(strict_types=1);

function get_codes(): array
{
    $data = read_json_file(get_qrcodes_file_path(), ['codes' => []]);
    $codes = is_array($data['codes'] ?? null) ? $data['codes'] : [];

    usort($codes, static function (array $left, array $right): int {
        return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
    });

    return $codes;
}

function save_code(array $record): void
{
    $requiredKeys = ['id', 'type', 'payload', 'source', 'preview_url', 'svg_url', 'created_at'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $record)) {
            throw new RuntimeException('Registro de QRCode invalido.');
        }
    }

    with_locked_json_file(get_qrcodes_file_path(), ['codes' => []], static function (array $data) use ($record): array {
        $codes = is_array($data['codes'] ?? null) ? $data['codes'] : [];
        $codes[] = [
            'id' => (string)$record['id'],
            'type' => (string)$record['type'],
            'payload' => (string)$record['payload'],
            'source' => (string)$record['source'],
            'uploaded_filename' => isset($record['uploaded_filename']) && $record['uploaded_filename'] !== null
                ? (string)$record['uploaded_filename']
                : null,
            'preview_url' => (string)$record['preview_url'],
            'svg_url' => (string)$record['svg_url'],
            'created_at' => (string)$record['created_at'],
        ];

        $data['codes'] = $codes;
        return $data;
    });
}

function get_code_by_id(string $id): ?array
{
    if ($id === '') {
        return null;
    }

    foreach (get_codes() as $code) {
        if ((string)($code['id'] ?? '') === $id) {
            return $code;
        }
    }

    return null;
}

function delete_code(string $id): void
{
    if ($id === '') {
        return;
    }

    $deleted = null;

    with_locked_json_file(get_qrcodes_file_path(), ['codes' => []], static function (array $data) use ($id, &$deleted): array {
        $codes = is_array($data['codes'] ?? null) ? $data['codes'] : [];
        $remaining = [];

        foreach ($codes as $code) {
            if ((string)($code['id'] ?? '') === $id) {
                $deleted = $code;
                continue;
            }

            $remaining[] = $code;
        }

        $data['codes'] = $remaining;
        return $data;
    });

    if (is_array($deleted)) {
        delete_generated_assets($deleted);
    }
}

function initialize_database(): void
{
    ensure_private_directory(get_storage_dir());
    ensure_private_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []]);
    ensure_private_json_file(get_qrcodes_file_path(), ['codes' => []]);
    ensure_default_user_exists();
}

function get_user_by_username(string $username): ?array
{
    $username = trim($username);
    if ($username === '') {
        return null;
    }

    foreach (get_all_users() as $user) {
        if (hash_equals((string)($user['username'] ?? ''), $username)) {
            return $user;
        }
    }

    return null;
}

function get_user_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    foreach (get_all_users() as $user) {
        if ((int)($user['id'] ?? 0) === $id) {
            return $user;
        }
    }

    return null;
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
    with_locked_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []], static function (array $data) use ($userId, $failedCount): array {
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];
        $newCount = $failedCount + 1;

        foreach ($users as &$user) {
            if ((int)($user['id'] ?? 0) !== $userId) {
                continue;
            }

            if ($newCount >= AUTH_MAX_LOGIN_ATTEMPTS) {
                $user['failed_login_count'] = 0;
                $user['locked_until'] = date('Y-m-d H:i:s', time() + AUTH_LOGIN_LOCK_SECONDS);
            } else {
                $user['failed_login_count'] = $newCount;
            }

            $user['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
        unset($user);

        $data['users'] = $users;
        return $data;
    });
}

function register_successful_login_for_user(int $userId): void
{
    with_locked_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []], static function (array $data) use ($userId): array {
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];

        foreach ($users as &$user) {
            if ((int)($user['id'] ?? 0) !== $userId) {
                continue;
            }

            $user['failed_login_count'] = 0;
            $user['locked_until'] = null;
            $user['last_login_at'] = date('Y-m-d H:i:s');
            $user['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
        unset($user);

        $data['users'] = $users;
        return $data;
    });
}

function update_user_credentials(int $userId, string $newUsername, string $newPasswordHash): void
{
    if ($userId <= 0) {
        throw new RuntimeException('Usuario invalido.');
    }

    with_locked_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []], static function (array $data) use ($userId, $newUsername, $newPasswordHash): array {
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];

        foreach ($users as &$user) {
            if ((int)($user['id'] ?? 0) !== $userId) {
                continue;
            }

            $user['username'] = $newUsername;
            $user['password_hash'] = $newPasswordHash;
            $user['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
        unset($user);

        $data['users'] = $users;
        return $data;
    });
}

function ensure_default_user_exists(): void
{
    with_locked_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []], static function (array $data): array {
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];
        if ($users !== []) {
            return $data;
        }

        $username = trim((string)DEFAULT_ADMIN_USERNAME);
        $plainPassword = (string)DEFAULT_ADMIN_PASSWORD;
        if ($username === '' || $plainPassword === '') {
            throw new RuntimeException('Usuario inicial invalido.');
        }
        validate_username_or_fail($username);
        if (strlen($plainPassword) < 8) {
            throw new RuntimeException('A senha inicial deve ter ao menos 8 caracteres.');
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Falha ao gerar hash da senha inicial.');
        }

        $now = date('Y-m-d H:i:s');
        $nextId = max(1, (int)($data['next_id'] ?? 1));

        $users[] = [
            'id' => $nextId,
            'username' => $username,
            'password_hash' => $passwordHash,
            'is_active' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $data['users'] = $users;
        $data['next_id'] = $nextId + 1;

        return $data;
    });
}

function get_storage_dir(): string
{
    return defined('APP_STORAGE_DIR') ? (string)APP_STORAGE_DIR : dirname(__DIR__) . '/storage';
}

function get_users_file_path(): string
{
    return get_storage_dir() . DIRECTORY_SEPARATOR . 'users.json';
}

function get_qrcodes_file_path(): string
{
    return get_storage_dir() . DIRECTORY_SEPARATOR . 'qrcodes.json';
}

function get_all_users(): array
{
    $data = read_json_file(get_users_file_path(), ['next_id' => 1, 'users' => []]);
    return is_array($data['users'] ?? null) ? $data['users'] : [];
}

function ensure_private_directory(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Nao foi possivel criar o diretorio privado de armazenamento.');
    }

    @chmod($dir, 0700);
}

function ensure_private_json_file(string $path, array $defaultData): void
{
    if (is_file($path)) {
        @chmod($path, 0600);
        return;
    }

    write_json_file($path, $defaultData);
}

function read_json_file(string $path, array $defaultData): array
{
    if (!is_file($path)) {
        write_json_file($path, $defaultData);
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Falha ao abrir o armazenamento local.');
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            throw new RuntimeException('Falha ao bloquear o armazenamento local.');
        }

        $contents = stream_get_contents($handle);
        if ($contents === false || trim($contents) === '') {
            return $defaultData;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Arquivo de armazenamento corrompido.');
        }

        return $decoded;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function with_locked_json_file(string $path, array $defaultData, callable $callback): array
{
    ensure_private_directory(dirname($path));

    $handle = fopen($path, 'c+b');
    if ($handle === false) {
        throw new RuntimeException('Falha ao abrir o armazenamento local.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Falha ao bloquear o armazenamento local.');
        }

        $contents = stream_get_contents($handle);
        $data = $defaultData;

        if ($contents !== false && trim($contents) !== '') {
            $decoded = json_decode($contents, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Arquivo de armazenamento corrompido.');
            }
            $data = $decoded;
        }

        $updatedData = $callback($data);
        if (!is_array($updatedData)) {
            throw new RuntimeException('Falha ao atualizar o armazenamento local.');
        }

        ftruncate($handle, 0);
        rewind($handle);

        $json = json_encode($updatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Falha ao serializar o armazenamento local.');
        }

        if (fwrite($handle, $json) === false) {
            throw new RuntimeException('Falha ao gravar o armazenamento local.');
        }

        fflush($handle);
        @chmod($path, 0600);

        return $updatedData;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function write_json_file(string $path, array $data): void
{
    with_locked_json_file($path, $data, static function () use ($data): array {
        return $data;
    });
}
