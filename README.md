# qrcode

Sistema em PHP 7.4 para gerar e gerenciar QRCodes sem banco de dados.

## Requisitos
- PHP 7.4+
- Composer

## Login
- O sistema cria o armazenamento local automaticamente em `storage/`.
- No primeiro start, se nao existir usuario salvo, cria um usuario inicial.
- Usuario inicial padrao: `admin`
- Senha inicial padrao: `admin123`
- Pode alterar o usuario/senha iniciais por variaveis de ambiente:
  - `APP_AUTH_USER`
  - `APP_AUTH_PASS`
- Tambem pode alterar a pasta privada de dados por:
  - `APP_STORAGE_DIR`

## Rodar local
1. Instale dependencias:
```bash
composer install
```
2. Rode:
```bash
php -S localhost:8000 -t public
```

## Rodar com Docker
Subir app:
```bash
docker compose up --build
```

Acesse:
- `http://localhost:8000/?action=login`

Parar:
```bash
docker compose down
```

## Funcionalidades
- Login com usuario armazenado localmente (senha com hash)
- Gerar QRCode por tipo: `Texto`, `Pagina HTML / URL`, `PDF`, `Imagem`
- Para `PDF` e `Imagem`: aceita URL manual ou upload de arquivo local
- Salvar historico localmente
- Visualizar lista em `Meus Codigos`
- Exportar em `SVG` e abrir `PNG`
- Excluir codigos do historico
- Alterar usuario e senha da conta
- Bloqueio temporario apos repetidas tentativas de login

## Armazenamento
- Historico dos QRCodes: `storage/qrcodes.json`
- Usuarios/autenticacao: `storage/users.json`
- Arquivos enviados (PDF/Imagem): `public/uploads/`
- QR Codes gerados localmente: `public/generated/`
- Limite de upload: `5MB` por arquivo
- Extensoes aceitas:
  - PDF: `.pdf`
  - Imagem: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## Seguranca
- Senhas armazenadas com `password_hash()`
- Tokens CSRF para formularios
- Sessao com regeneracao de ID no login
- Timeout de sessao por inatividade
- Bloqueio temporario apos falhas sucessivas
- Arquivos de dados fora da pasta publica
- Escrita com `flock()` para reduzir risco de corrupcao

## Estrutura
- `public/index.php`: entrada e controle de telas/login
- `src/auth.php`: autenticacao e sessao
- `src/storage.php`: persistencia local em arquivos JSON privados
- `src/qr.php`: regra de geracao/validacao do payload
- `src/layout.php`: interface das telas
- `src/bootstrap.php`: bootstrap e inicializacao do armazenamento
- `public/assets/style.css`: estilo da aplicacao
- `storage/`: dados privados da aplicacao
- `public/uploads/`: arquivos enviados para gerar QRCode por upload
- `public/generated/`: PNG e SVG gerados localmente
