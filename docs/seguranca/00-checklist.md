# Checklist de segurança (pós-auditoria)

## Já corrigido no código (deploy necessário)

- [x] IDOR em campos de template (`updateCampo` / `destroyCampo`)
- [x] Tokens de integração Sanctum com abilities `integration` + `tenant:{orgId}`
- [x] Open redirect CTA landing (allowlist de hosts)
- [x] `ClinicScope` fail-closed sem org no contexto
- [x] Prefill público com e-mail/telefone mascarados (sem endereço)
- [x] Rate limit OTP por IP + token + destinatário
- [x] URLs Link Bio só `https`/`mailto`/`tel`
- [x] Sanitização HTML de e-mails
- [x] ErrorHub: mais chaves PII redigidas
- [x] CORS: localhost só fora de `production`
- [x] Log Viewer default `false`
- [x] Defaults hardcoded de webhooks n8n removidos
- [x] Headers de segurança no nginx do app + CSP no site
- [x] Site `apiBase` → API de produção
- [x] Business Hub: allowlist opcional de IPs via env

## Você precisa fazer no servidor

- [ ] Atualizar `.env` da API (ver `01-api-env-producao.md`)
- [ ] Aplicar/recarregar Nginx da API, app e site
- [ ] Publicar builds novos (API `composer install`, front `ng build`, site estático)
- [ ] Confirmar `apiBase` do site em produção aponta para `api.gestgo.com.br`
- [ ] Garantir pasta `modelos_link_bio/` no deploy do site
- [ ] (Opcional) `BUSINESS_HUB_CONNECTOR_ALLOWED_IPS`
- [ ] Confirmar `ASAAS_WEBHOOK_SECRET`, `APP_DEBUG=false`, `LOG_VIEWER_ENABLED=false`
- [ ] Rodar `composer update` / `composer audit` limpo na API
- [ ] Auditar dados sensíveis já gravados em **homolog** se o site esteve apontando para lá

## Não automatizado nesta rodada (planejado)

- Migração completa Sanctum Bearer `localStorage` → cookie HttpOnly (ver `07-sessao-httponly-futuro.md`)
- Remover `'unsafe-inline'` da CSP (exige nonces/hashes no build)
