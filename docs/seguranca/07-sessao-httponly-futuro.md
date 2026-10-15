# 07 — Migração futura: token em cookie HttpOnly

## Situação atual

O app Angular guarda `gestgo_token` (Sanctum plain text) em `localStorage`.  
Qualquer XSS no origin do app rouba a sessão.

## Meta

Usar cookie de sessão **HttpOnly + Secure + SameSite=Lax/Strict** (Sanctum SPA stateful), sem Bearer no JS.

## Passos de alto nível (quando for priorizar)

1. **API**
   - Habilitar Sanctum SPA: `SANCTUM_STATEFUL_DOMAINS` com `app.gestgo.com.br`
   - `SESSION_DOMAIN=.gestgo.com.br` (se app e api em subdomínios)
   - `supports_credentials => true` no CORS **somente** para origens do app
   - Expor `GET /sanctum/csrf-cookie` e login via cookie/CSRF
2. **Front**
   - `withCredentials: true` no `HttpClient`
   - Remover `localStorage` do token; manter só dados de UI não sensíveis
   - Tratar CSRF (`X-XSRF-TOKEN`)
3. **Nginx / Cloudflare**
   - Não strippar cookies `Set-Cookie`
   - SameSite/Secure corretos
4. **Testes**
   - Login, logout, troca de clínica, refresh de página, aba anônima

## Enquanto isso

- Manter CSP + headers no Nginx do app
- Não injetar HTML de usuário sem sanitização
- Expiração Sanctum curta (`SANCTUM_EXPIRATION`)
- Revogar tokens no logout (já feito)
