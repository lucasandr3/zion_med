# 07 — Sessão HttpOnly do SPA

O app (`app.gestgo.com.br` / `homolog.gestgo.com.br`) autentica com cookie de sessão **HttpOnly**. O JavaScript não lê esse cookie. O Bearer continua só para tokens de integração (`clinic:{id}-…`) e para o conector Business Hub.

Quem já estava logado entra de novo uma vez. O `gestgo_token` antigo no `localStorage` deixa de ser enviado e é apagado no próximo login.

## O que o código faz

1. `statefulApi()` no Sanctum: origens do SPA ganham sessão e CSRF.
2. `POST /api/v1/auth/login` regenera a sessão, apaga tokens `spa` e devolve `csrf_token` no JSON. Não devolve Bearer.
3. `GET /api/v1/auth/csrf` devolve o CSRF da sessão atual (sem regenerar).
4. O Angular guarda esse valor em `sessionStorage` (`gestgo_xsrf`) e envia `X-CSRF-TOKEN` em POST/PUT/PATCH/DELETE, com `withCredentials`.
5. Um marcador `gestgo_session=1` no `localStorage` só indica que a UI acha que há sessão. A prova é `GET /api/v1/me`.
6. `Access-Control-Allow-Credentials` só sai para origens do app. O site (`gestgo.com.br`) continua no CORS simples, sem cookie.
7. Token Sanctum com nome `spa` é recusado e apagado. Integração segue com Bearer.

O cookie de sessão fica **só no host da API** (`api.gestgo.com.br`). `SameSite=Lax` já é enviado no XHR same-site do app para a API. Não use `SESSION_DOMAIN=.gestgo.com.br`: produção e homolog compartilhariam o cookie.

## O que fazer no `.env` da API

Produção:

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=

SANCTUM_STATEFUL_DOMAINS=app.gestgo.com.br

CORS_ALLOWED_ORIGINS=https://app.gestgo.com.br,https://gestgo.com.br
```

Homolog (não reutilize o `SESSION_DOMAIN` da produção):

```env
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=

SANCTUM_STATEFUL_DOMAINS=homolog.gestgo.com.br

CORS_ALLOWED_ORIGINS=https://homolog.gestgo.com.br,https://site-homolog.gestgo.com.br
```

Não coloque `gestgo.com.br` em `SANCTUM_STATEFUL_DOMAINS`. O cadastro `/comece` é POST do site e não manda CSRF.

Se o TLS termina no Cloudflare e a API vê HTTP, mantenha `SESSION_SECURE_COOKIE=true`. Sem isso o navegador pode recusar o cookie Secure.

Depois:

```bash
cd /var/www/zion_med
php artisan config:clear
php artisan config:cache
```

Publique a API e o build do Angular juntos.

## Como validar

1. Login em `https://app.gestgo.com.br`. No DevTools → Application → Cookies, o cookie de sessão está em `api.gestgo.com.br`, com HttpOnly e Secure. Não há `gestgo_token` no `localStorage`.
2. Recarregar a página continua logado.
3. Logout encerra a sessão.
4. `POST /api/v1/comece` no site segue sem cookie e redireciona para a tela de login.
5. Um token de integração (Empresa → Integrações) ainda chama a API com `Authorization: Bearer`.
