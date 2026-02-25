<?php

declare(strict_types=1);

const MAX_UPLOAD_SIZE_BYTES = 5242880;

function generate_qr_urls(string $payload): array
{
    $encoded = rawurlencode($payload);

    return [
        'preview_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&format=png&data=' . $encoded,
        'svg_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&format=svg&data=' . $encoded,
    ];
}

function build_qr_payload(string $type, string $payload, array $file): array
{
    $type = strtolower(trim($type));

    if (($type === 'pdf' || $type === 'image') && has_uploaded_file($file)) {
        $upload = store_uploaded_file($type, $file);

        return [
            'payload' => $upload['url'],
            'source' => 'upload',
            'uploaded_filename' => $upload['original_name'],
        ];
    }

    $normalizedPayload = normalize_payload($type, $payload);
    if ($normalizedPayload === '') {
        throw new RuntimeException('O conteudo nao pode ser vazio para este tipo.');
    }

    return [
        'payload' => $normalizedPayload,
        'source' => 'manual',
        'uploaded_filename' => null,
    ];
}

function normalize_payload(string $type, string $payload): string
{
    $type = strtolower(trim($type));

    if ($type === 'text') {
        return $payload;
    }

    if (($type === 'pdf' || $type === 'image') && $payload === '') {
        throw new RuntimeException('Informe uma URL ou envie um arquivo para este tipo.');
    }

    if (!filter_var($payload, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Para este tipo, informe uma URL valida (http/https).');
    }

    if (!preg_match('/^https?:\/\//i', $payload)) {
        throw new RuntimeException('Use URL iniciando com http:// ou https://');
    }

    return $payload;
}

function generate_id(): string
{
    return bin2hex(random_bytes(8));
}

function has_uploaded_file(array $file): bool
{
    if ($file === []) {
        return false;
    }

    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    return $error !== UPLOAD_ERR_NO_FILE;
}

function store_uploaded_file(string $type, array $file): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $originalName = (string)($file['name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Upload invalido.');
    }

    if ($size <= 0 || $size > MAX_UPLOAD_SIZE_BYTES) {
        throw new RuntimeException('Arquivo excede o limite de 5MB.');
    }

    $validation = validate_upload_type($type, $tmpName, $originalName);
    $extension = $validation['extension'];

    $uploadDir = get_upload_dir();
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de uploads.');
    }

    $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Nao foi possivel salvar o arquivo enviado.');
    }

    return [
        'url' => build_upload_public_url($storedName),
        'stored_name' => $storedName,
        'original_name' => $originalName,
    ];
}

function validate_upload_type(string $type, string $tmpName, string $originalName): array
{
    $type = strtolower(trim($type));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = detect_mime_type($tmpName);

    if ($type === 'pdf') {
        if ($extension !== 'pdf' || $mime !== 'application/pdf') {
            throw new RuntimeException('Para tipo PDF, envie um arquivo .pdf valido.');
        }

        return ['extension' => 'pdf'];
    }

    if ($type === 'image') {
        $allowed = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) {
            throw new RuntimeException('Para tipo Imagem, envie JPG, PNG, GIF ou WEBP valido.');
        }

        return ['extension' => $extension];
    }

    throw new RuntimeException('Upload permitido apenas para PDF ou Imagem.');
}

function detect_mime_type(string $tmpName): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
    }

    $mime = mime_content_type($tmpName);
    return is_string($mime) ? $mime : '';
}

function get_upload_dir(): string
{
    return dirname(__DIR__) . '/public/uploads';
}

function build_upload_public_url(string $storedName): string
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

    return $scheme . '://' . $host . $basePath . '/uploads/' . rawurlencode($storedName);
}

function upload_error_message(int $error): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo maior que o limite configurado no PHP.',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite do formulario.',
        UPLOAD_ERR_PARTIAL => 'Upload parcial. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporaria ausente no servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Falha de escrita no disco.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensao do PHP.',
    ];

    return $messages[$error] ?? 'Falha no upload do arquivo.';
}
