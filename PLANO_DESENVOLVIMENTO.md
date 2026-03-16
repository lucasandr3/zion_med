# Plano de Desenvolvimento — Zion Med

Documento de acompanhamento do plano de ajustes para lançamento.  
Variáveis de ambiente e checklist de go-live estão no final.

---

## Arquitetura: Backend (API) e Frontend

- **Este repositório** é o **backend/API** (Laravel). As telas de uso (login, esqueci senha, redefinir senha, verificação de e-mail, billing, integrações, etc.) **não** são implementadas aqui; a UI fica no **frontend em Angular**.
- **Frontend**: projeto separado (ex.: `zion_med_front` em Angular). As telas devem consumir a **API REST** documentada abaixo (base URL normalmente `APP_URL/api/v1`).
- As rotas e views Blade existentes neste projeto servem à área web legada ou a fluxos administrativos; o aplicativo principal do usuário final é o SPA Angular.

---

## API para o frontend (Angular)

Base: `GET/POST .../api/v1/...`. Autenticação: `Authorization: Bearer {token}` (Sanctum), exceto onde indicado como público.

| Área | Método | Rota | Auth | Descrição |
|------|--------|------|------|-----------|
| **Auth** | POST | `/auth/login` | Não | Login (email, password). Retorna token + user + clinics. Throttle 5/min por IP. |
| | POST | `/auth/logout` | Sim | Revoga o token atual. |
| | POST | `/auth/forgot-password` | Não | Envia link de redefinição (body: `email`). Throttle 5/min por IP. |
| | POST | `/auth/reset-password` | Não | Redefine senha (body: `token`, `email`, `password`, `password_confirmation`). |
| | GET | `/auth/verify-email` | Não | Verifica e-mail via link (query: `id`, `hash`, `expires`, `signature`). |
| | POST | `/auth/send-verification-email` | Sim | Reenvia e-mail de verificação. |
| **Billing** | GET | `/billing` | Sim (tenant) | Lista assinaturas, pagamentos e planos da clínica. |
| | POST | `/billing/checkout` | Sim (tenant) | Cria assinatura (body: `plan_key`). |
| | POST | `/billing/subscriptions/{id}/cancel` | Sim (tenant) | Cancela assinatura. |
| | POST | `/billing/change-plan` | Sim (tenant) | Troca de plano (body: `plan_key`). Cancela atual e cria nova. |
| **Integrações** | GET | `/clinica/integracoes` | Sim (tenant) | Lista tokens, webhooks e deliveries. |
| | POST | `/clinica/integracoes/webhook-deliveries/{id}/retry` | Sim (tenant) | Reenvia entrega de webhook falha. |
| **Landing** | GET | `/landing` | Não | Retorna `trial_days`, `plans`, `headline`, `subheadline`, `niches` (copy da landing). |
| **Formulário público** | POST | `/formulario-publico/{token}/otp/send` | Não | Envia OTP por e-mail (body: `email`). Throttle por token. |
| | POST | `/formulario-publico/{token}/otp/verify` | Não | Verifica OTP (body: `email`, `code`). |
| **Protocolos** | GET | `/protocols/{id}/dossie` | Sim (tenant) | Download ZIP com PDF do protocolo + JSON de evidências. |
| | GET | `/protocols/{id}/timeline` | Sim (tenant) | Timeline de eventos do protocolo. |
| **Documentos** | GET | `/document-sends` | Sim (tenant) | Lista envios (query: `caixa=pendentes|assinados|expirados|cancelados`, `template_id`, `channel`). |
| | POST | `/document-sends` | Sim (tenant) | Envia link por e-mail ou WhatsApp (body: `template_id`, `channel=email|whatsapp`, `recipient_email` ou `recipient_phone`, `expires_at?`). |
| | POST | `/document-sends/{id}/reenvio` | Sim (tenant) | Reenvia o link por e-mail. |
| | POST | `/document-sends/{id}/cancel` | Sim (tenant) | Cancela envio pendente (já assinado retorna 422). |
| **Templates** | POST | `/templates/{id}/enviar` | Sim (tenant) | Envia link por e-mail ou WhatsApp (body: `channel?`, `recipient_email` ou `recipient_phone`, `expires_at?`). |
| | POST | `/templates/{id}/duplicar` | Sim (tenant) | Duplica template (body: `name?`). |
| | GET | `/templates/biblioteca` | Sim (tenant) | Sugestões da biblioteca por categoria (query: `category?`). |

Header opcional para contexto de clínica: `X-Clinic-Id: {id}`. Demais endpoints da API (templates, protocolos, usuários, etc.) seguem as rotas em `routes/api.php`. Especificação de evidência e concorrência: `docs/SPEC_CONCORRENCIA_ASSINATURA_DIGITAL.md`.

---

## Status das entregas

### ✅ Concluído

- **Epic 1 — Segurança imediata**
  - `.env.example` sem credenciais reais (MinIO com placeholders).
  - Remoção de todo o código de depuração em `BillingController::checkout`.
  - Variáveis de produção documentadas abaixo.

- **Epic 2 — Autenticação**
  - Recuperação de senha: rotas `/esqueci-a-senha` e `/redefinir-senha/{token}`, controllers e views.
  - Verificação de e-mail: rotas `/verificar-email`, controllers e view; modelo `User` implementa `MustVerifyEmail`.
  - Link "Esqueceu a senha?" na tela de login apontando para `password.request`.

- **Epic 3 — E-mail (Resend)**
  - Configuração para uso do Resend: `MAIL_MAILER=resend`, `RESEND_API_KEY` e `MAIL_FROM_*` no `.env.example`.
  - Com isso, todos os e-mails do Laravel (reset de senha, verificação, notificações) saem pelo Resend quando a chave estiver definida.

- **Epic 4 — CI**
  - Pipeline GitHub Actions em `.github/workflows/ci.yml`: PHP 8.4, Composer, SQLite, migrations, `php artisan test`, e job de frontend (npm ci + build).

- **Backlog implementado**
  - Rate limit em login e "esqueci a senha" (API e web): throttle `auth` (5 req/min por IP).
  - API de auth para o front: forgot-password, reset-password, verify-email (link assinado), send-verification-email.
  - Reenvio de webhooks falhos: `POST /api/v1/clinica/integracoes/webhook-deliveries/{id}/retry`.
  - Billing: cancelar assinatura e trocar de plano (`/billing/subscriptions/{id}/cancel`, `/billing/change-plan`).
  - Serviço de e-mail transacional: `App\Services\TransactionalEmailService` + job `SendTransactionalEmailJob` (Resend + fila); view base `emails.transactional`.

- **SPEC Concorrência (assinatura eletrônica e dossiê)**
  - Documento de spec: `docs/SPEC_CONCORRENCIA_ASSINATURA_DIGITAL.md`.
  - Versionamento de template: tabela `form_template_versions`, serviço `TemplateVersionService`; snapshot de campos na submissão.
  - Evidência robusta: em `form_submissions` e `submission_signatures` (document_hash, evidence_hash, channel, status, locale, timezone, accepted_text_at); timeline em `submission_events.meta_json`.
  - Dossiê exportável: `GET /api/v1/protocols/{protocol}/dossie` (ZIP com PDF + JSON de evidências); `DossierService` e PDF com hashes/canal no rodapé.
  - OTP para formulário público: `OtpService`, tabela `otp_challenges`; rotas `POST /formulario-publico/{token}/otp/send` e `.../otp/verify`.
  - Landing: `config/landing.php` e resposta de `GET /landing` com `headline`, `subheadline`, `niches`; variáveis opcionais `LANDING_HEADLINE`, `LANDING_SUBHEADLINE`.
  - Nível de segurança por organização: coluna `organizations.signing_security_level` (basic | reinforced).
  - **Epic 3–5 (continuação):** `DocumentSendService`: envio por e-mail com link público, reenvio; `DocumentSendController`: listagem com caixas (pendentes/assinados/expirados), reenvio; versionamento ao gerar link (`gerarLink` chama `TemplateVersionService`); `POST /templates/{id}/enviar`, `POST /templates/{id}/duplicar`, `GET /templates/biblioteca`; `GET /protocols/{id}/timeline`; dashboard com `documentos_pendentes_assinatura` e `documentos_expirados`; vínculo submissão–envio ao submeter (e-mail coincidente).
  - **Cancelamento e lembretes:** Caixa `cancelados` em `GET /document-sends`; `POST /document-sends/{id}/cancel`; `DocumentSendService::cancel` e `sendReminder`; migration `cancelled_at` e `reminded_at` em `document_sends`; comando `documents:send-reminders` agendado diariamente (variável `DOCUMENT_REMINDER_DAYS`, padrão 2).
  - **WhatsApp:** `DocumentSendService::sendByWhatsApp` (webhook n8n); `POST /document-sends` e `POST /templates/{id}/enviar` com `channel=whatsapp` e `recipient_phone`; config `services.n8n_whatsapp.webhook_url`.
  - **Middleware verified:** Alias `verified` => `EnsureEmailIsVerified` registrado em `bootstrap/app.php`; usar em rotas quando quiser exigir e-mail verificado.
  - **Testes API:** `tests/Feature/Api/DocumentSendControllerTest` (index caixas, cancel); `ProtocolDossieTest` (dossiê ZIP); `PublicFormOtpTest` (OTP send/verify).

---

## Variáveis de ambiente (produção)

Configure no servidor (nunca commite valores reais):

| Variável | Obrigatório | Descrição |
|----------|-------------|-----------|
| `APP_ENV` | Sim | `production` |
| `APP_DEBUG` | Sim | `false` |
| `APP_URL` | Sim | URL pública do backend (ex: `https://api.seudominio.com`) |
| `FRONTEND_URL` | Recomendado | URL base do SPA (Angular). Usada no link de redefinição de senha no e-mail (ex: `https://app.seudominio.com`, sem barra final). Se não definida, o link aponta para a rota web do backend. |
| `APP_KEY` | Sim | `php artisan key:generate` |
| `DB_*` | Sim | Conexão PostgreSQL (ou MySQL) |
| `SESSION_SECURE_COOKIE` | Recomendado | `true` em HTTPS |
| `MAIL_MAILER` | Sim | `resend` para envio real |
| `RESEND_API_KEY` | Se MAIL_MAILER=resend | Chave da API Resend |
| `MAIL_FROM_ADDRESS` | Sim | E-mail remetente (ex: `noreply@seudominio.com`) |
| `MAIL_FROM_NAME` | Sim | Nome do remetente (ex: `Zion Med`) |
| `ASAAS_BASE_URL` | Sim | Produção: `https://api.asaas.com/v3` |
| `ASAAS_API_KEY` | Sim | Token da API Asaas produção |
| `ASAAS_WEBHOOK_SECRET` | Sim | Token definido no webhook Asaas |
| `MINIO_*` ou `AWS_*` | Conforme uso | Storage (MinIO/S3) para anexos, assinaturas, logos |
| `N8N_WHATSAPP_WEBHOOK_URL` | Opcional | Webhook n8n para envio de link por WhatsApp |
| `DOCUMENT_REMINDER_DAYS` | Opcional | Dias após envio para disparar lembrete (padrão: 2) |
| `N8N_WEBHOOK_ERRO_PAGAMENTO` | Opcional | Webhook n8n para erros de pagamento |

---

## Checklist de go-live

- [ ] Nenhuma credencial real no repositório.
- [ ] `APP_DEBUG=false` e `APP_ENV=production` em produção.
- [ ] Reset de senha testado (fluxo completo).
- [ ] Verificação de e-mail testada (rotas de clínica exigem e-mail verificado; front redireciona 403 para /verificacao-pendente).
- [ ] Resend configurado e domínio verificado (SPF/DKIM).
- [ ] Pipeline CI verde (push em `main`/`develop`).
- [ ] Testes críticos passando (auth, billing, formulário público, escopo por clínica).
- [ ] Webhook Asaas configurado e testado.
- [ ] Backup e procedimento de rollback definidos.

---

## Próximos passos (backlog)

- Documentação OpenAPI/Swagger da API (conforme necessidade).

---

**Frontend (Angular):** Tratamento de 403 (e-mail não verificado) com redirecionamento para `/verificacao-pendente` e botão "Reenviar e-mail de verificação"; tela **Envios de documento** (`/envios`) com caixas Pendentes/Assinados/Expirados/Cancelados, ações reenviar e cancelar, e formulário "Novo envio" (template, canal e-mail/WhatsApp, destinatário). Serviço `document-sends.service` e método `enviarDocumento` em `templates.service`.

*Última atualização: middleware verified aplicado nas rotas de clínica; documento PLANO_EPICS.md; front com verificacao-pendente e tela Envios.*
