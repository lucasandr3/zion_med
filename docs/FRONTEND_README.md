# Frontend Angular (projeto separado)

Este repositório contém apenas o **backend** Laravel. O SPA fica em outro diretório (ex.: `gestgo_front` ou, por histórico, `zion_med_front`). Copie este arquivo para `README.md` na raiz do projeto Angular ou mantenha-o aqui como referência da equipe.

## Pré-requisitos

- Node.js LTS (compatível com a versão do Angular do projeto)
- npm, yarn ou pnpm (conforme o `package.json` do front)
- API Laravel em execução (ex.: `http://localhost:8000` ou host do Docker)

## Setup

```bash
cd gestgo_front
npm install
```

## Variáveis de ambiente

Defina a URL base da API (ex.: `environment.ts` / `environment.development.ts`):

| Variável / propriedade | Exemplo dev | Observação |
|------------------------|-------------|------------|
| API base URL | `http://localhost:8000` | Sem barra final |
| Prefixo da API | `/api/v1` | Alinhar com `routes/api.php` do Laravel |

No Laravel, configure `FRONTEND_URL` (ex.: `http://localhost:4200`) para links de e-mail (reset de senha, verificação). Ver `.env.example`.

## CORS (sem proxy no Angular)

O SPA chama a API diretamente (`environment.apiUrl`, ex.: `http://localhost:8000` ou `https://api.gestgo.com.br`). O CORS fica no Laravel em `config/cors.php`.

Origens padrão permitidas:

- `http://localhost:4200` e `http://127.0.0.1:4200` (dev com `ng serve`)
- `http://zion_med.test` (dev com Herd/Valet, se o front usar esse host)
- `https://app.gestgo.com.br` (produção)

Para adicionar domínios (Easy Panel, staging, etc.), defina no `.env` do backend:

```env
CORS_ALLOWED_ORIGINS=http://localhost:4200,http://zion_med.test,https://app.gestgo.com.br
```

Reinicie o backend após alterar o `.env`.

## Tema (UI)

A chave canônica do tema padrão é **`gestgo-blue`** (a API ainda aceita o legado `zion-blue` e normaliza para `gestgo-blue`). No Angular/CSS, a classe corporativa correspondente é **`theme-gestgo-blue`** — alinhar estilos e remapear qualquer referência antiga a `theme-zion-blue`.

## Build de produção

```bash
ng build --configuration production
```

Artefatos em `dist/<nome-do-projeto>/`. Sirva como site estático (Nginx, CDN, S3+CloudFront, etc.).

## Deploy (visão geral)

1. **Build** do Angular com a URL da API de produção nas `environment.prod`.
2. **Backend**: garantir `APP_URL`, CORS/Sanctum para o domínio do SPA, `FRONTEND_URL` com o domínio público do app.
3. **HTTPS** nos dois lados; cookies/tokens conforme política definida (documentar riscos aceitos).

## Testes E2E (Playwright / Cypress)

Manter credenciais apenas em arquivos locais ignorados (ex.: `.env.test.local`) ou variáveis de CI; não commitar senhas ou tokens reais em `example.spec.ts`.

## Documentação da API

- OpenAPI/Scramble: ver rota de documentação configurada no backend (`config/scramble.php`).
- Coleção Postman: `postman/Gestgo_API.postman_collection.json` e ambiente `postman/Gestgo_API.postman_environment.json`.
