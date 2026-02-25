<?php
/** @var string $action */
/** @var array<string,string> $allowedTypes */
/** @var string|null $message */
/** @var string|null $error */
/** @var array|null $generated */
/** @var array $codes */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QRCode Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="bg-shape bg-1"></div>
<div class="bg-shape bg-2"></div>

<main class="container">
    <header class="topbar">
        <div>
            <h1>QRCode Studio</h1>
            <p>Gerador de QRCode com historico, upload de arquivos e exportacao em SVG.</p>
        </div>
        <nav>
            <a class="<?= $action === 'generate' ? 'active' : '' ?>" href="?action=generate">Gerar QRCode</a>
            <a class="<?= $action === 'my-codes' ? 'active' : '' ?>" href="?action=my-codes">Meus Codes</a>
        </nav>
    </header>

    <?php if ($message !== null): ?>
        <div class="alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($action === 'generate'): ?>
        <section class="card grid">
            <form method="post" class="panel" enctype="multipart/form-data">
                <h2>Novo QRCode</h2>
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

            <aside class="panel preview">
                <h2>Preview</h2>
                <?php if ($generated !== null): ?>
                    <img src="<?= htmlspecialchars((string)$generated['preview_url'], ENT_QUOTES, 'UTF-8') ?>" alt="QRCode gerado">
                    <div class="actions">
                        <a class="btn ghost" href="<?= htmlspecialchars((string)$generated['preview_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir PNG</a>
                        <a class="btn ghost" href="<?= htmlspecialchars((string)$generated['svg_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Exportar SVG</a>
                    </div>
                    <p><strong>Origem:</strong> <?= htmlspecialchars(((string)($generated['source'] ?? 'manual')) === 'upload' ? 'Upload' : 'URL/Texto', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($generated['uploaded_filename'])): ?>
                        <p><strong>Arquivo:</strong> <?= htmlspecialchars((string)$generated['uploaded_filename'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <pre><?= htmlspecialchars((string)$generated['payload'], ENT_QUOTES, 'UTF-8') ?></pre>
                <?php else: ?>
                    <p class="muted">O QRCode gerado aparece aqui.</p>
                <?php endif; ?>
            </aside>
        </section>
    <?php endif; ?>

    <?php if ($action === 'my-codes'): ?>
        <section class="card panel">
            <h2>Meus Codes</h2>
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
                                        <form method="post" onsubmit="return confirm('Remover este QRCode?');">
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
            payloadHintEl.textContent = 'Texto livre. Ex.: mensagem, chave PIX, codigo interno.';
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
