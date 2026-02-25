<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$action = $_GET['action'] ?? 'generate';
$message = null;
$error = null;
$generated = null;
$previewCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($action === 'generate') && isset($_POST['create_qr'])) {
        $type = trim((string)($_POST['type'] ?? 'text'));
        $payloadInput = trim((string)($_POST['payload'] ?? ''));
        $uploadedFile = $_FILES['uploaded_file'] ?? [];

        try {
            if (!in_array($type, ['text', 'link', 'pdf', 'image'], true)) {
                throw new RuntimeException('Tipo de QRCode inválido.');
            }

            $payloadData = build_qr_payload($type, $payloadInput, is_array($uploadedFile) ? $uploadedFile : []);
            $normalizedPayload = $payloadData['payload'];

            $result = generate_qr_urls($normalizedPayload);
            $record = [
                'id' => generate_id(),
                'type' => $type,
                'payload' => $normalizedPayload,
                'source' => $payloadData['source'],
                'uploaded_filename' => $payloadData['uploaded_filename'],
                'preview_url' => $result['preview_url'],
                'svg_url' => $result['svg_url'],
                'created_at' => date('c'),
            ];

            save_code($record);
            header('Location: ?action=preview&id=' . rawurlencode((string)$record['id']));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    if (($action === 'my-codes') && isset($_POST['delete_id'])) {
        $deleteId = trim((string)$_POST['delete_id']);
        delete_code($deleteId);
        $message = 'QRCode removido.';
    }
}

if ($action === 'preview') {
    $previewId = trim((string)($_GET['id'] ?? ''));
    if ($previewId === '') {
        $error = 'QRCode nao encontrado.';
    } else {
        $previewCode = get_code_by_id($previewId);
        if ($previewCode === null) {
            $error = 'QRCode nao encontrado.';
        }
    }
}

$codes = get_codes();

$allowedTypes = [
    'text' => 'Texto',
    'link' => 'Página HTML / URL',
    'pdf' => 'PDF (URL ou Upload)',
    'image' => 'Imagem (URL ou Upload)',
];

include __DIR__ . '/../src/layout.php';
