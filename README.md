# qrcode

Sistema em PHP 7.4 para gerar e gerenciar QRCodes.

## Requisitos
- PHP 7.4+
- Conexao com internet (usa API publica goQR via `api.qrserver.com`)

## Como rodar
```bash
php -S localhost:8000 -t public
```

Acesse:
- `http://localhost:8000/?action=generate`
- `http://localhost:8000/?action=my-codes`

## Funcionalidades
- Gerar QRCode por tipo: `Texto`, `Pagina HTML / URL`, `PDF`, `Imagem`
- Para `PDF` e `Imagem`: aceita URL manual ou upload de arquivo local
- Salvar historico em `storage/codes.json`
- Visualizar lista em `Meus Codes`
- Exportar em `SVG` e abrir `PNG`
- Excluir codigos do historico

## Armazenamento
- Historico dos QRCodes: `storage/codes.json`
- Arquivos enviados (PDF/Imagem): `public/uploads/`
- Limite de upload: `5MB` por arquivo
- Extensoes aceitas:
  - PDF: `.pdf`
  - Imagem: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## Estrutura
- `public/index.php`: entrada e controle simples de telas
- `src/qr.php`: regra de geracao/validacao do payload
- `src/storage.php`: persistencia em JSON
- `src/layout.php`: interface das telas
- `public/assets/style.css`: estilo da aplicacao
- `public/uploads/`: arquivos enviados para gerar QRCode por upload
