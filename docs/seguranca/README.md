# Segurança — o que corrigir no servidor (sem Docker)

Este guia cobre o que **não fica só no código** e precisa ser aplicado na VPS/host (Nginx + PHP-FPM + Node build estático).  
Pressuposto: **não usa Docker** em produção.

## Ordem recomendada

1. [00-checklist.md](./00-checklist.md) — visão geral e status
2. [01-api-env-producao.md](./01-api-env-producao.md) — `.env` da API Laravel
3. [02-nginx-api.md](./02-nginx-api.md) — Nginx da API (`api.gestgo.com.br`)
4. [03-nginx-app.md](./03-nginx-app.md) — Nginx do app Angular (`app.gestgo.com.br`)
5. [04-nginx-site.md](./04-nginx-site.md) — Nginx do site marketing (`gestgo.com.br`)
6. [05-dependencias-e-deploy.md](./05-dependencias-e-deploy.md) — composer/npm e deploy
7. [06-business-hub-e-webhooks.md](./06-business-hub-e-webhooks.md) — conector + n8n + Asaas
8. [07-sessao-httponly-futuro.md](./07-sessao-httponly-futuro.md) — migração futura do token (localStorage → cookie)

## Repositórios

| Projeto | Pasta local típica | Doc espelhada |
|---------|-------------------|---------------|
| API | `zion_med` | esta pasta |
| App | `zion_med_front` | `docs/seguranca/` |
| Site | `gestgo-site` | `docs/seguranca/` |
