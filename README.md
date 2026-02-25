# qrcode

Sistema em PHP 7.4 para gerar e gerenciar QRCodes.

## Requisitos
- PHP 7.4+
- Composer

## Como rodar
```bash
composer install
php -S localhost:8000 -t public
```

Acesse:
- `http://localhost:8000/?action=generate`
- `http://localhost:8000/?action=my-codes`

## Rodar com Docker (PHP 7.4)
Com Docker Compose:
```bash
docker compose up --build
```

Acesse:
- `http://localhost:8000/?action=generate`
- `http://localhost:8000/?action=my-codes`

Parar containers:
```bash
docker compose down
```

Observações:
- `storage/`, `public/uploads/` e `public/generated/` estão mapeados como volume (persistem no host).
- A imagem usa `php:7.4-cli` e executa servidor embutido em `0.0.0.0:8000`.

## Funcionalidades
- Gerar QRCode por tipo: `Texto`, `Página HTML / URL`, `PDF`, `Imagem`
- Para `PDF` e `Imagem`: aceita URL manual ou upload de arquivo local
- Salvar histórico em `storage/codes.json`
- Visualizar lista em `Meus Códigos`
- Exportar em `SVG` e abrir `PNG`
- Excluir códigos do histórico

## Armazenamento
- Histórico dos QRCodes: `storage/codes.json`
- Arquivos enviados (PDF/Imagem): `public/uploads/`
- QR Codes gerados localmente: `public/generated/`
- Limite de upload: `5MB` por arquivo
- Extensões aceitas:
  - PDF: `.pdf`
  - Imagem: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## Estrutura
- `public/index.php`: entrada e controle simples de telas
- `src/qr.php`: regra de geração/validação do payload
- `src/storage.php`: persistência em JSON
- `src/layout.php`: interface das telas
- `public/assets/style.css`: estilo da aplicacao
- `public/uploads/`: arquivos enviados para gerar QRCode por upload
- `public/generated/`: PNG e SVG gerados localmente (sem API externa)
