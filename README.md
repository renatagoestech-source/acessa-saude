# Acessa+ Saúde — repositório Vercel

Este repositório contém a versão PHP do Acessa+ Saúde preparada para o runtime PHP comunitário recomendado pelo Vercel. O frontend continua em `index.php` e as chamadas existentes para `api.php?action=...` são encaminhadas por `api/api.php`.

## Importante antes do deploy

O Vercel não fornece um MySQL local persistente. Antes de publicar, crie um banco MySQL externo acessível pela internet e configure as variáveis `ACESSA_DB_*` no painel do Vercel. O arquivo `database.sql` contém o esquema inicial.

O armazenamento local do Vercel também não é persistente. Uploads de logo e anexos devem ser migrados para um serviço de arquivos persistente antes de uso em produção. Para demonstração, a interface e o banco funcionam com as configurações adequadas.

## Subir pelo GitHub

1. Crie um repositório vazio no GitHub.
2. Envie todos os arquivos desta pasta para esse repositório.
3. No Vercel, selecione **Add New Project** e importe o repositório.
4. Mantenha a raiz do projeto como a pasta que contém `vercel.json`.

> **Importante:** no Vercel, defina a raiz do projeto como a pasta que contém diretamente `vercel.json`, `index.php` e a pasta `api`. Não selecione uma pasta pai que contenha `acessa-saude-vercel` como subpasta.

5. Em **Project Settings → Environment Variables**, cadastre as variáveis do `.env.example` com os valores reais do seu MySQL.
6. Faça o deploy.

## Subir pelo terminal

Com Git e Vercel CLI instalados:

```bash
git init
git add .
git commit -m "Preparar projeto para Vercel"
vercel login
vercel link
vercel --prod
```

## Desenvolvimento local

O runtime PHP do Vercel exige PHP local para `vercel dev`. Como alternativa, use o servidor PHP embutido:

```bash
php -S 127.0.0.1:8000
```

## Modo demonstração

A aba de assinatura está em modo demonstração e não cria cobrança real no Asaas. Não configure `ASAAS_API_KEY` enquanto esse modo estiver sendo utilizado.

## Referências

- [Vercel — runtimes](https://vercel.com/docs/functions/runtimes)
- [Runtime PHP comunitário](https://github.com/vercel-community/php)
- [PHP no Vercel com Docker](https://vercel.com/kb/guide/deploy-php-on-vercel-with-docker)
