# Plano de Desenvolvimento — Zion Med (Epics)

Documento do plano em epics, alinhado ao `PLANO_DESENVOLVIMENTO.md` e à `SPEC_CONCORRENCIA_ASSINATURA_DIGITAL.md`.

---

## Visão e arquitetura

- **Backend:** API Laravel (este repositório). Autenticação Sanctum, multi-tenant por organização (clínica).
- **Frontend:** SPA Angular (projeto separado). Consome `APP_URL/api/v1`.
- **Posicionamento:** Solução de consentimento e documentação clínica digital para clínicas pequenas e médias; não prontuário completo.

---

## Epic 1 — Segurança imediata

| Item | Status | Notas |
|------|--------|--------|
| .env.example sem credenciais reais | ✅ | MinIO e demais com placeholders |
| Remoção de código de depuração em checkout | ✅ | BillingController |
| Variáveis de produção documentadas | ✅ | PLANO_DESENVOLVIMENTO.md |

---

## Epic 2 — Autenticação

| Item | Status | Notas |
|------|--------|--------|
| Recuperação de senha (API + front) | ✅ | forgot-password, reset-password |
| Verificação de e-mail | ✅ | User MustVerifyEmail; verify-email (link), send-verification-email |
| Middleware `verified` em rotas de clínica | ✅ | Rotas tenant exigem e-mail verificado |
| Link "Esqueceu a senha?" no login | ✅ | Front |

---

## Epic 3 — E-mail e notificações

| Item | Status | Notas |
|------|--------|--------|
| Resend (MAIL_MAILER=resend, RESEND_API_KEY) | ✅ | .env.example |
| E-mail transacional (TransactionalEmailService + Job) | ✅ | Resend + fila; view emails.transactional |
| Rate limit login / esqueci senha | ✅ | throttle:auth (5/min por IP) |

---

## Epic 4 — CI e qualidade

| Item | Status | Notas |
|------|--------|--------|
| Pipeline GitHub Actions | ✅ | .github/workflows/ci.yml (PHP 8.4, test, front build) |
| Testes críticos (auth, billing, escopo, document-sends, dossiê, OTP) | ✅ | tests/Feature e tests/Feature/Api |

---

## Epic 5 — Assinatura eletrônica e evidência (SPEC)

| Item | Status | Notas |
|------|--------|--------|
| Versionamento de template (form_template_versions, TemplateVersionService) | ✅ | Snapshot na submissão |
| Evidência (document_hash, evidence_hash, channel, status, locale, timezone) | ✅ | form_submissions, submission_signatures, submission_events.meta_json |
| OTP formulário público (otp_challenges, send/verify) | ✅ | POST formulario-publico/{token}/otp/send e verify |
| Nível de segurança por organização (signing_security_level) | ✅ | basic \| reinforced |
| Dossiê exportável (ZIP: PDF + JSON) | ✅ | GET /protocols/{id}/dossie, DossierService |
| Timeline de eventos por protocolo | ✅ | GET /protocols/{id}/timeline |

---

## Epic 6 — Fluxo de envio e gestão de documentos

| Item | Status | Notas |
|------|--------|--------|
| DocumentSendService (envio por e-mail, reenvio) | ✅ | sendByEmail, reenvio |
| Envio por WhatsApp (webhook n8n) | ✅ | sendByWhatsApp; N8N_WHATSAPP_WEBHOOK_URL |
| Listagem document-sends (caixas: pendentes, assinados, expirados, cancelados) | ✅ | GET /document-sends?caixa= |
| Cancelamento de envio pendente | ✅ | POST /document-sends/{id}/cancel |
| Lembretes automáticos | ✅ | documents:send-reminders (schedule diário; DOCUMENT_REMINDER_DAYS) |
| POST /document-sends (channel=email|whatsapp) | ✅ | recipient_email ou recipient_phone |
| POST /templates/{id}/enviar (channel opcional) | ✅ | E-mail ou WhatsApp |
| Versionamento ao gerar link (gerarLink) | ✅ | TemplateVersionService |
| Dashboard (documentos_pendentes_assinatura, documentos_expirados) | ✅ | GET /dashboard |
| Vínculo submissão–envio (e-mail coincidente) | ✅ | SubmissionService / DocumentSendService |

---

## Epic 7 — Biblioteca e templates

| Item | Status | Notas |
|------|--------|--------|
| GET /templates/biblioteca | ✅ | Sugestões por categoria |
| POST /templates/{id}/duplicar | ✅ | Duplicar template |

---

## Epic 8 — Compliance e jurídico (conteúdo)

| Item | Status | Notas |
|------|--------|--------|
| Política de privacidade / termos (páginas e rotas) | ✅ | Front: termos-de-uso, privacidade |
| Revisão de copy e alegações jurídicas | Backlog | Conteúdo; não implementação |
| DPA, retenção, incidentes, logs de acesso | Backlog | Conforme evolução do produto |

---

## Landing e preço

| Item | Status |
|------|--------|
| GET /landing (headline, subheadline, niches, plans, trial_days) | ✅ |
| config/landing.php, LANDING_HEADLINE, LANDING_SUBHEADLINE | ✅ |
| Faixa de preço (spec): R$ 247–397/mês conforme recursos | Documentado na SPEC |

---

## Próximos passos (backlog)

- Aplicar políticas de retenção e LGPD (documentação e fluxos) conforme necessidade.
- Documentação completa da API (OpenAPI/Swagger) para o front e parceiros.
- Revisão de copy e materiais jurídicos (Epic 8 conteúdo).

---

**Frontend:** Rota `/verificacao-pendente` (e-mail não verificado); interceptor redireciona 403 para essa rota. Tela `/envios` (Envios de documento): caixas, reenviar, cancelar, novo envio (template + canal e-mail/WhatsApp). Serviços `document-sends.service` e `templates.service.enviarDocumento`.

*Última atualização: plano em epics consolidado; middleware verified nas rotas de clínica; front verificacao-pendente e tela Envios.*
