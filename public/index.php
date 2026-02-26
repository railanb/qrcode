<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$action = (string)($_GET['action'] ?? (is_authenticated() ? 'generate' : 'login'));
$message = null;
$error = null;
$previewCode = null;
$codes = [];

if (!is_authenticated() && $action !== 'login') {
    header('Location: ?action=login');
    exit;
}

if (is_authenticated() && !enforce_session_timeout()) {
    header('Location: ?action=login&timeout=1');
    exit;
}

if ($action === 'login' && isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    $message = 'Sessao encerrada por inatividade. Faca login novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (isset($_POST['do_logout'])) {
        if (!validate_csrf_token($csrfToken)) {
            $error = 'Token de seguranca invalido.';
        } else {
            logout_user();
            header('Location: ?action=login');
            exit;
        }
    }

    if (($action === 'login') && isset($_POST['do_login'])) {
        if (!validate_csrf_token($csrfToken)) {
            $error = 'Token de seguranca invalido.';
        } else {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $lockRemaining = get_login_lock_remaining_seconds_for_username($username);
            if ($lockRemaining > 0) {
                $error = 'Muitas tentativas. Tente novamente em ' . $lockRemaining . ' segundos.';
            } else {
                if (attempt_login($username, $password)) {
                    header('Location: ?action=generate');
                    exit;
                }

                $lockRemainingAfter = get_login_lock_remaining_seconds_for_username($username);
                if ($lockRemainingAfter > 0) {
                    $error = 'Muitas tentativas. Tente novamente em ' . $lockRemainingAfter . ' segundos.';
                } else {
                    $error = 'Usuario ou senha invalidos.';
                }
            }
        }
    }

    if (($action === 'generate') && isset($_POST['create_qr'])) {
        if (!validate_csrf_token($csrfToken)) {
            $error = 'Token de seguranca invalido.';
        } else {
            $type = trim((string)($_POST['type'] ?? 'text'));
            $payloadInput = trim((string)($_POST['payload'] ?? ''));
            $uploadedFile = $_FILES['uploaded_file'] ?? [];

            try {
                if (!in_array($type, ['text', 'link', 'pdf', 'image'], true)) {
                    throw new RuntimeException('Tipo de QRCode invalido.');
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
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                save_code($record);
                header('Location: ?action=preview&id=' . rawurlencode((string)$record['id']));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }

    if (($action === 'my-codes') && isset($_POST['delete_id'])) {
        if (!validate_csrf_token($csrfToken)) {
            $error = 'Token de seguranca invalido.';
        } else {
            $deleteId = trim((string)$_POST['delete_id']);

            try {
                delete_code($deleteId);
                $message = 'QRCode removido.';
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }

    if (($action === 'account') && isset($_POST['save_account'])) {
        if (!validate_csrf_token($csrfToken)) {
            $error = 'Token de seguranca invalido.';
        } else {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newUsername = trim((string)($_POST['new_username'] ?? ''));
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            try {
                change_authenticated_user_credentials($currentPassword, $newUsername, $newPassword, $confirmPassword);
                $message = 'Dados da conta atualizados com sucesso.';
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

if (is_authenticated() && $action === 'preview') {
    $previewId = trim((string)($_GET['id'] ?? ''));
    if ($previewId === '') {
        $error = 'QRCode nao encontrado.';
    } else {
        try {
            $previewCode = get_code_by_id($previewId);
            if ($previewCode === null) {
                $error = 'QRCode nao encontrado.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

if (is_authenticated()) {
    try {
        $codes = get_codes();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$allowedTypes = [
    'text' => 'Texto',
    'link' => 'Pagina HTML / URL',
    'pdf' => 'PDF (URL ou Upload)',
    'image' => 'Imagem (URL ou Upload)',
];

include __DIR__ . '/../src/layout.php';
