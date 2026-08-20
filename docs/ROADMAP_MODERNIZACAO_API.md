# Roadmap — Modernização da API (Laravel) alinhada ao Angular 21 zoneless

Documento de análise e plano do backend Gestgo (`zion_med`) após a migração do SPA para **Angular 21 + zoneless**.

**Repositório frontend:** `../zion_med_front` → ver [`docs/ROADMAP_ZONELESS_SIGNALS.md`](../../zion_med_front/docs/ROADMAP_ZONELESS_SIGNALS.md).

---

## 1. Stack atual (baseline)

| Item | Valor |
|------|--------|
| Framework | **Laravel 12** (`laravel/framework` ~12.62) |
| PHP | **^8.4** |
| Auth API | **Laravel Sanctum 4** — Bearer `spa`, abilities `tenant:{orgId}` |
| API docs | **dedoc/scramble** |
| Filas | `database` (dev) / **redis** (prod) |
| Storage | MinIO / S3 |
| Mail | Resend |
| Nome Composer | `gestgo/gestgo-api` |

**Não é NestJS.** API Laravel + SPA Angular separado.

### Estrutura relevante

```
app/Http/Controllers/Api/V1/   # ~59 controllers
app/Services/                  # domínio
app/Jobs/                      # poucos jobs
app/Support/                   # ApiPagination, ApiErrorResponse, …
routes/api.php                 # ~299 linhas — arquivo único
database/migrations/
tests/
docs/
.cursor/rules/                 # convenções aspiracionais (desatualizadas vs código)
```

---

## 2. Relação com o zoneless do front

### Veredito

| Pergunta | Resposta |
|----------|----------|
| O zoneless **exige** mudança de API? | **Quase não** |
| HttpClient + signals / `resource` funcionam com o REST atual? | **Sim** |
| O que o back deve fazer? | Estabilizar contratos, aliviar poll, async para trabalho pesado, alinhar **rules/docs** ao código real |

O SPA deixou de depender de ZoneJS para saber *quando* redesenhar. Isso **não muda** JSON, auth ou rotas — muda a disciplina do front ao consumir dados. O backend ajuda quando oferece:

- payloads estáveis e previsíveis (`data` + `meta`);
- contagens leves (evitar listar tudo só para badge);
- trabalho longo fora do request HTTP;
- erros no formato que o front já parseia.

---

## 3. Contratos que o front já consome

### 3.1 Auth

- `POST /api/v1/auth/login` → `{ data: { token, user, organizations, … } }`
- Sem refresh token; expiração Sanctum (~24h)
- Front: 401 → logout; 403 `billing_blocked` / e-mail não verificado no interceptor
- Headers: `Authorization`, `X-Organization-Id` / `X-Clinic-Id`

### 3.2 Paginação (canônico)

`app/Support/ApiPagination.php`:

```json
{
  "data": [],
  "meta": { "current_page", "last_page", "per_page", "total" },
  "links": { "first", "last", "prev", "next" }
}
```

**Exceção:** connector Business Hub (`ConnectorPaginator` com `page` / `pageSize`) — documentar como contrato separado.

### 3.3 Erros (formato real)

`app/Support/ApiErrorResponse.php` + `bootstrap/app.php`:

```json
{ "code": "validation_failed", "message": "…", "details": {}, "errors": {} }
```

Códigos usados pelo SPA: `billing_blocked`, `email_unverified`, `validation_failed`, `unauthorized`, …

Front: `src/app/core/utils/api-error.util.ts` (envelope **flat**).

### 3.4 Real-time hoje

| Mecanismo | Status |
|-----------|--------|
| WebSockets / Reverb / SSE | **Inexistente** |
| Poll de job longo | **Não há** `/jobs/{id}/status` |
| Notificações | HTTP no mount do layout |
| WhatsApp Evolution | GET status/QR sob demanda |
| Presença | join + `leave-beacon` (HMAC) |

### 3.5 Jobs atuais

- `SendTransactionalEmailJob`
- `DispatchWebhookJob`
- `SendDemonstrationRequestN8nJob`

PDF / dossiê / CSV em geral ainda são **síncronos no request** (stream).

---

## 4. Descompasso: rules Cursor vs código

Arquivo base: `.cursor/rules/laravel-api-base.mdc` (e regras irmãs).

| Rules (aspiracional) | Código real |
|----------------------|-------------|
| Laravel **11.x**, PHP **8.3+** | Laravel **12**, PHP **8.4** |
| Envelope `{ "error": { "code", "message" } }` | Flat `{ "code", "message", "details?" }` |
| Pastas `Actions/`, `DTOs/`, `Repositories/`, `Exceptions/` | Em grande parte **não existem** |
| Thin controllers | Controllers gordos (ex.: `PublicFormApiController` ~1080 LOC, `TemplateController` ~811) |

**Isso é a “mudança de regras”:** o time precisa tratar o envelope flat + Laravel 12 como fonte da verdade e atualizar as rules — não o contrário (não quebrar o Angular).

---

## 5. Prioridades (backend)

### P0 — Alinhar verdade documental

1. Atualizar `.cursor/rules` para Laravel 12 / PHP 8.4  
2. Documentar envelope de erro **flat** (e remover a regra `{ error: {…} }` ou marcá-la como legado)  
3. CORS: garantir `config/cors.php` honrando `CORS_ALLOWED_ORIGINS` (README já menciona; validar código)  
4. Link cruzado com o roadmap zoneless do front  

### P1 — Contratos que destravam UX signals

1. Front passar a usar `meta.unread_count` (já na API de notificações) **ou** expor `GET /notificacoes/unread-count`  
2. Idempotência em submits críticos (`Idempotency-Key`): formulário público, checkout ASAAS, envio de documento  
3. Headers de cache explícitos: PII `private, no-store`; catálogos opcionalmente com ETag  
4. Documentar contrato de paginação único (+ exceção connector)  

### P2 — Async / trabalho longo

1. Jobs para PDF/dossiê/CSV pesados + endpoint de status (ou signed URL pronta)  
2. Status WhatsApp/Evolution estável (poll documentado; SSE/WS só se o poll do front crescer)  
3. Observabilidade de webhooks (retry já existe em parte)  

### P3 — Arquitetura interna

1. Extrair Actions/Services dos controllers monolíticos (começar por Public Form + Templates)  
2. Split `routes/api/*.php` por domínio  
3. Ampliar Feature tests (Platform / Feegow / Evolution)  
4. Deprecar superfície Blade de app (manter health/admin se necessário)  
5. OpenAPI Scramble como gate de breaking change no CI (opcional)  

---

## 6. Fases de roadmap (back)

### Fase 0 — Alinhamento (1–2 sprints)

- [ ] Atualizar `.cursor/rules/*` (stack + envelope real)  
- [ ] Revisar `config/cors.php` × `.env.example`  
- [ ] Nota no README: “zoneless Angular não muda contratos; SPA usa `data` + `meta` + erro flat”  
- [ ] Combinar com front: consumir `unread_count`  

### Fase 1 — Contratos estáveis para SPA signals (2–4 sprints)

- [ ] Endpoint leve de unread (se ainda necessário após uso do `meta`)  
- [ ] Idempotência em rotas críticas  
- [ ] Cache-Control / ETag onde fizer sentido  
- [ ] Timeouts Nginx alinhados a streams PDF/ZIP  
- [ ] Documentar paginação connector como exceção  

### Fase 2 — Async & UX longa (1–2 meses)

- [ ] Job + status (ou URL assinada) para exports pesados  
- [ ] Melhorar contrato Evolution (status/QR) para poll eficiente  
- [ ] Avaliar SSE/WebSocket **somente** para badge de notificações + WhatsApp  

### Fase 3 — Arquitetura (contínuo)

- [ ] Refatorar controllers gordos → Services/Actions  
- [ ] Split de rotas  
- [ ] Cobertura de testes nas áreas Platform/integrações  
- [ ] Epics LGPD / retenção (já há scheduler parcial)  

### Fase 4 — Escala (opcional)

- [ ] Reverb / Web Push  
- [ ] Refresh token / sliding expiration Sanctum  
- [ ] Breaking-change check OpenAPI front↔back no CI  

---

## 7. Matriz front ↔ back

| Necessidade do front (zoneless/signals) | Ação no back | Prioridade |
|------------------------------------------|--------------|------------|
| Badges no layout sem listar tudo | Usar / expor `unread_count` | P1 |
| Listagens com `resource`/`toSignal` | Manter `data`+`meta` estável | — (já ok) |
| Erros tipados no interceptor | Manter envelope flat; alinhar rules | P0 |
| Formulário público / OTP | Sem mudança; idempotência ajuda | P1 |
| WhatsApp QR na clínica | Status estável + poll eficiente | P2 |
| PDF/dossiê sem travar UI | Job + status | P2 |
| Tempo real de notificações | SSE/WS futuro | P3/Fase 4 |
| Sessão longa sem 401 duro | Refresh token (opcional) | Fase 4 |

---

## 8. O que **não** fazer “por causa do zoneless”

- Migrar para NestJS / Node só por Angular 21  
- Trocar Sanctum por JWT sem necessidade de produto  
- Quebrar o envelope de erro flat (quebraria o SPA)  
- Exigir OnPush/signals no front como pré-requisito de API  

---

## 9. Definition of Done (alinhamento API ↔ SPA)

1. Rules Cursor batem com Laravel 12 + erro flat.  
2. Front usa contagem leve de notificações (sem listar tudo no shell).  
3. Rotas críticas com idempotência documentada.  
4. Exports pesados não dependem só de request longo (plano Fase 2 iniciado ou feito).  
5. `docs/FRONTEND_README.md` e este roadmap apontam um para o outro e para o roadmap zoneless do front.

---

## 10. Histórico

| Data | Evento |
|------|--------|
| 2026-08-20 | Front em Angular 21 + zoneless |
| 2026-08-20 | Este roadmap criado (API + regras) |
