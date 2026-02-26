# qrcode

Sistema em PHP 7.4 para gerar e gerenciar QRCodes.

## Requisitos
- PHP 7.4+
- Composer
- MySQL 8+

## Login (usuario no banco)
- O sistema cria a tabela `users` automaticamente.
- No primeiro start, se `users` estiver vazia, cria um usuario inicial.
- Usuario inicial padrao: `admin`
- Senha inicial padrao: `admin123`
- Pode alterar o usuario/senha iniciais por variaveis de ambiente:
  - `APP_AUTH_USER`
  - `APP_AUTH_PASS`

## Rodar local (sem Docker)
1. Instale dependencias:
```bash
composer install
```
2. Configure variaveis de ambiente do MySQL (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
3. Rode:
```bash
php -S localhost:8000 -t public
```

## Rodar com Docker
Subir app + banco + Adminer:
```bash
docker compose up --build
```

Acesse:
- `http://localhost:8000/?action=login`
- `http://localhost:8080` (Adminer)

Parar:
```bash
docker compose down
```

## Funcionalidades
- Login com usuario armazenado no MySQL (senha com hash)
- Gerar QRCode por tipo: `Texto`, `Pagina HTML / URL`, `PDF`, `Imagem`
- Para `PDF` e `Imagem`: aceita URL manual ou upload de arquivo local
- Salvar historico em MySQL
- Visualizar lista em `Meus Codigos`
- Exportar em `SVG` e abrir `PNG`
- Excluir codigos do historico

## Armazenamento
- Historico dos QRCodes: tabela `qrcodes` no MySQL
- Usuarios/autenticacao: tabela `users` no MySQL
- Arquivos enviados (PDF/Imagem): `public/uploads/`
- QR Codes gerados localmente: `public/generated/`
- Limite de upload: `5MB` por arquivo
- Extensoes aceitas:
  - PDF: `.pdf`
  - Imagem: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## Acessar banco pelo navegador (Adminer)
- URL: `http://localhost:8080`
- Sistema: `MySQL`
- Servidor: `db`
- Usuario: `qrcode_user`
- Senha: `qrcode_pass`
- Base: `qrcode`

## Estrutura
- `public/index.php`: entrada e controle de telas/login
- `src/auth.php`: autenticacao e sessao
- `src/storage.php`: persistencia MySQL (PDO)
- `src/qr.php`: regra de geracao/validacao do payload
- `src/layout.php`: interface das telas
- `src/bootstrap.php`: bootstrap e inicializacao da base
- `public/assets/style.css`: estilo da aplicacao
- `public/uploads/`: arquivos enviados para gerar QRCode por upload
- `public/generated/`: PNG e SVG gerados localmente
