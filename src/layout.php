<?php
/** @var string $action */
/** @var array<string,string> $allowedTypes */
/** @var string|null $message */
/** @var string|null $error */
/** @var array|null $previewCode */
/** @var array $codes */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QRCode Studio Zanoello</title>
    <link rel="icon" href="/assets/favicon.ico?v=2" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="bg-shape bg-1"></div>
<div class="bg-shape bg-2"></div>

<main class="container">
    <header class="topbar">
        <div>
            <h1>QRCode Studio Zanoello</h1>
            <p>Gerador de QRCode com historico, upload de arquivos e exportacao em SVG.</p>
            <?php if (is_authenticated()): ?>
                <p class="session-user">Usuario logado: <strong><?= htmlspecialchars((string)($_SESSION['auth_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php endif; ?>
        </div>
        <?php if (is_authenticated()): ?>
            <nav>
                <a class="<?= $action === 'generate' ? 'active' : '' ?>" href="?action=generate">Gerar QRCode</a>
                <a class="<?= $action === 'my-codes' ? 'active' : '' ?>" href="?action=my-codes">Meus Codigos</a>
                <form method="post" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="nav-btn" name="do_logout" value="1">Sair</button>
                </form>
            </nav>
        <?php endif; ?>
    </header>

    <?php if ($message !== null): ?>
        <div class="alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($action === 'login'): ?>
        <section class="card panel">
            <form method="post" autocomplete="off">
                <h2>Login</h2>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>

                <button class="btn" type="submit" name="do_login" value="1">Entrar</button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (is_authenticated() && $action === 'generate'): ?>
        <section class="card panel">
            <form method="post" enctype="multipart/form-data">
                <h2>Novo QRCode</h2>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <label for="type">Tipo</label>
                <select name="type" id="type" required>
                    <?php foreach ($allowedTypes as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="payload">Conteudo</label>
                <textarea name="payload" id="payload" rows="5" placeholder="Texto livre ou URL (https://...)"></textarea>
                <small id="payload-hint">Para texto: informe o texto livre. Para links: informe URL valida.</small>

                <div id="upload-block" class="upload-block hidden">
                    <label for="uploaded_file">Enviar arquivo (opcional)</label>
                    <input type="file" name="uploaded_file" id="uploaded_file">
                    <small id="upload-hint">Para PDF/Imagem, voce pode enviar arquivo ou usar URL no campo acima.</small>
                </div>

                <button class="btn" type="submit" name="create_qr" value="1">Gerar e Salvar</button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (is_authenticated() && $action === 'preview'): ?>
        <section class="card panel preview">
            <h2>Preview</h2>
            <?php if ($previewCode !== null): ?>
                <img src="<?= htmlspecialchars((string)$previewCode['preview_url'], ENT_QUOTES, 'UTF-8') ?>" alt="QRCode gerado">
                <div class="actions">
                    <a class="btn ghost" href="<?= htmlspecialchars((string)$previewCode['preview_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir PNG</a>
                    <a class="btn ghost" href="<?= htmlspecialchars((string)$previewCode['svg_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Exportar SVG</a>
                    <a class="btn" href="?action=generate">Voltar</a>
                </div>
                <p><strong>Origem:</strong> <?= htmlspecialchars(((string)($previewCode['source'] ?? 'manual')) === 'upload' ? 'Upload' : 'URL/Texto', ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!empty($previewCode['uploaded_filename'])): ?>
                    <p><strong>Arquivo:</strong> <?= htmlspecialchars((string)$previewCode['uploaded_filename'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <pre><?= htmlspecialchars((string)$previewCode['payload'], ENT_QUOTES, 'UTF-8') ?></pre>
            <?php else: ?>
                <p class="muted">Nenhum QRCode para exibir.</p>
                <a class="btn" href="?action=generate">Voltar</a>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (is_authenticated() && $action === 'my-codes'): ?>
        <section class="card panel">
            <h2>Meus Codigos</h2>
            <?php if ($codes === []): ?>
                <p class="muted">Nenhum QRCode salvo ainda.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Origem</th>
                            <th>Conteudo</th>
                            <th>Criado em</th>
                            <th>Acoes</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($codes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($allowedTypes[$item['type']] ?? $item['type']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(((string)($item['source'] ?? 'manual')) === 'upload' ? 'Upload' : 'URL/Texto', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="content">
                                    <?php if (filter_var((string)$item['payload'], FILTER_VALIDATE_URL)): ?>
                                        <a href="<?= htmlspecialchars((string)$item['payload'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                            <?= htmlspecialchars((string)$item['payload'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$item['payload'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$item['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="actions inline">
                                        <a class="btn tiny" href="<?= htmlspecialchars((string)$item['preview_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">PNG</a>
                                        <a class="btn tiny" href="<?= htmlspecialchars((string)$item['svg_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">SVG</a>
                                        <a class="btn tiny" href="?action=preview&id=<?= rawurlencode((string)$item['id']) ?>">Preview</a>
                                        <form method="post" onsubmit="return confirm('Remover este QRCode?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn tiny danger">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<footer class="site-footer" aria-label="Rodape">
    <img src="/assets/logo.png" alt="Logo" class="footer-logo">
</footer>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var payloadEl = document.getElementById('payload');
    var payloadHintEl = document.getElementById('payload-hint');
    var uploadBlockEl = document.getElementById('upload-block');
    var uploadEl = document.getElementById('uploaded_file');
    var uploadHintEl = document.getElementById('upload-hint');

    if (!typeEl || !payloadEl || !payloadHintEl || !uploadBlockEl || !uploadEl || !uploadHintEl) {
        return;
    }

    function syncFields() {
        var type = typeEl.value;
        var uploadTypes = (type === 'pdf' || type === 'image');

        uploadBlockEl.classList.toggle('hidden', !uploadTypes);

        if (type === 'text') {
            payloadEl.placeholder = 'Digite o texto que sera codificado';
            payloadHintEl.textContent = 'Texto livre. Ex.: mensagem, codigo interno.';
            uploadEl.value = '';
            uploadEl.accept = '';
            return;
        }

        if (type === 'link') {
            payloadEl.placeholder = 'https://seu-site.com/pagina';
            payloadHintEl.textContent = 'Informe a URL da pagina HTML.';
            uploadEl.value = '';
            uploadEl.accept = '';
            return;
        }

        if (type === 'pdf') {
            payloadEl.placeholder = 'https://seu-site.com/arquivo.pdf';
            payloadHintEl.textContent = 'Para PDF, informe URL ou envie um arquivo.';
            uploadEl.accept = '.pdf,application/pdf';
            uploadHintEl.textContent = 'Tipos aceitos: PDF ate 5MB.';
            return;
        }

        payloadEl.placeholder = 'https://seu-site.com/imagem.png';
        payloadHintEl.textContent = 'Para imagem, informe URL ou envie um arquivo.';
        uploadEl.accept = '.jpg,.jpeg,.png,.gif,.webp,image/*';
        uploadHintEl.textContent = 'Tipos aceitos: JPG, PNG, GIF, WEBP ate 5MB.';
    }

    typeEl.addEventListener('change', syncFields);
    syncFields();
})();
</script>
</body>
</html>
