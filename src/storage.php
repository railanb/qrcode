<?php

declare(strict_types=1);

function get_codes(): array
{
    if (!is_file(STORAGE_FILE)) {
        return [];
    }

    $raw = file_get_contents(STORAGE_FILE);
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    usort($decoded, static function (array $a, array $b): int {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });

    return $decoded;
}

function save_code(array $record): void
{
    $codes = get_codes();
    $codes[] = $record;
    write_codes($codes);
}

function delete_code(string $id): void
{
    $codes = get_codes();

    $filtered = array_values(array_filter($codes, static function (array $item) use ($id): bool {
        return (string)($item['id'] ?? '') !== $id;
    }));

    write_codes($filtered);
}

function write_codes(array $codes): void
{
    $json = json_encode($codes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Falha ao serializar os codigos.');
    }

    if (file_put_contents(STORAGE_FILE, $json) === false) {
        throw new RuntimeException('Falha ao gravar o storage de codigos.');
    }
}
