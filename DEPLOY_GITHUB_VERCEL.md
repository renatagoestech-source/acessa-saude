# Publicar pelo GitHub e Vercel

## Estrutura do projeto

Selecione como raiz do projeto a pasta que contém diretamente:

```text
vercel.json
index.php
api/
  index.php   # encaminha as páginas para o frontend PHP
  api.php     # encaminha /api.php?action=... para o backend
script.js
style.css
config.php
database.sql
```

Não selecione uma pasta externa que contenha o projeto como subpasta.

## Configuração na Vercel

1. Abra a Vercel e selecione **Add New Project**.
2. Importe este repositório do GitHub.
3. Use **Other** como Framework Preset.
4. Defina **Root Directory** como a pasta que contém `vercel.json` (normalmente `./`).
5. Deixe **Build Command** e **Output Directory** vazios.
6. Configure as variáveis de ambiente descritas abaixo.
7. Clique em **Deploy**.

O `vercel.json` usa o runtime PHP comunitário `vercel-php@0.9.0`, configura os arquivos `api/*.php` como funções e encaminha `/api.php?action=...` para o handler `api/api.php`. As demais rotas de página são encaminhadas para `api/index.php`; CSS, JavaScript, imagens e fontes continuam sendo servidos como arquivos estáticos.

## Banco de dados

A Vercel não fornece um MySQL local persistente. Crie um banco MySQL externo acessível pela aplicação e importe nele `database.sql`. Em **Project Settings → Environment Variables**, cadastre os seguintes nomes, com os valores fornecidos pelo seu provedor de banco:

- `ACESSA_DB_HOST`
- `ACESSA_DB_PORT`
- `ACESSA_DB_NAME`
- `ACESSA_DB_USER`
- `ACESSA_DB_PASS`

Depois de adicionar ou alterar variáveis, gere um novo deploy para que as funções recebam os valores.

## Limitações de armazenamento

O sistema grava anexos e logos em `uploads/`. O filesystem das funções da Vercel não é persistente; portanto, esses uploads não devem ser usados em produção sem migrá-los para armazenamento externo durável. Sessões PHP no armazenamento local temporário também não são duráveis entre instâncias/funções serverless; para uso confiável em produção, configure sessões compartilhadas ou migre a autenticação para um armazenamento persistente.

A assinatura permanece em modo demonstração e não gera cobrança real.
