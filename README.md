# Acessa+ Saúde — repositório Vercel

Este repositório contém a versão PHP do Acessa+ Saúde preparada para o runtime PHP comunitário recomendado pela Vercel. O frontend continua em `index.php`; os handlers em `api/index.php` e `api/api.php` encaminham as páginas e as chamadas existentes para `api.php?action=...`.

## Antes de publicar

A Vercel não fornece um MySQL local persistente. Crie um banco MySQL externo acessível pela internet, importe `database.sql` e configure as variáveis `ACESSA_DB_HOST`, `ACESSA_DB_PORT`, `ACESSA_DB_NAME`, `ACESSA_DB_USER` e `ACESSA_DB_PASS` no painel da Vercel.

O filesystem das funções não é persistente. Uploads de logos e anexos precisam ser migrados para armazenamento externo durável antes do uso em produção. As sessões PHP armazenadas localmente também podem se perder entre invocações/instâncias; para autenticação confiável em produção, use um armazenamento de sessão compartilhado ou uma estratégia persistente.

## Deploy pelo GitHub

1. Importe este repositório na Vercel.
2. Mantenha como **Root Directory** a pasta que contém diretamente `vercel.json`.
3. Selecione **Other** como Framework Preset e deixe **Build Command** e **Output Directory** vazios.
4. Configure as variáveis de ambiente do MySQL acima e publique.

O `vercel.json` declara o runtime `vercel-php@0.9.0` para `api/*.php`, encaminha `/api.php?action=...` para o backend e encaminha as demais rotas de página para o frontend.

## Desenvolvimento local

O runtime PHP da Vercel exige PHP instalado para `vercel dev`. Como alternativa, com PHP instalado, use o servidor PHP embutido:

```bash
php -S 127.0.0.1:8000
```

## Modo demonstração

A aba de assinatura está em modo demonstração e não cria cobrança real no Asaas. Não configure `ASAAS_API_KEY` enquanto esse modo estiver sendo utilizado.
