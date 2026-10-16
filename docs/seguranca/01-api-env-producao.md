# 01 — `.env` da API em produção (passo a passo)

Arquivo típico: `/var/www/gestgo-api/.env` (ajuste o path da sua VPS).

## 1. Backup

```bash
sudo cp /var/www/gestgo-api/.env /var/www/gestgo-api/.env.bak-$(date +%Y%m%d)
```

## 2. Valores obrigatórios de segurança

Edite o `.env` e confira:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.gestgo.com.br
FRONTEND_URL=https://app.gestgo.com.br

# Sessão HttpOnly do SPA. Cookie só no host da API — não defina SESSION_DOMAIN=.gestgo.com.br
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_DOMAIN=
SESSION_SAME_SITE=lax

# CORS — liste só origens reais (vírgula, sem espaços extras desnecessários)
CORS_ALLOWED_ORIGINS=https://app.gestgo.com.br,https://gestgo.com.br

SANCTUM_EXPIRATION=1440
# Só o SPA. Não inclua gestgo.com.br (o /comece do site não envia CSRF).
SANCTUM_STATEFUL_DOMAINS=app.gestgo.com.br

LOG_VIEWER_ENABLED=false
SCRAMBLE_PUBLIC=false

# Asaas — webhook NÃO funciona sem secret
ASAAS_WEBHOOK_SECRET=cole-o-mesmo-token-configurado-no-painel-asaas
ASAAS_API_KEY=...

# n8n — agora SEM default no código; se usar, preencha
N8N_WHATSAPP_WEBHOOK_URL=https://...
N8N_WEBHOOK_ERRO_PAGAMENTO=https://...
N8N_DEMONSTRACAO_WEBHOOK_URL=https://...

# Opcional: restringe IPs do conector Business Hub
# BUSINESS_HUB_CONNECTOR_ALLOWED_IPS=1.2.3.4,5.6.7.8

# Opcional: hosts extras no redirect CTA da landing
# LANDING_REDIRECT_ALLOWED_HOSTS=parceiro.exemplo.com.br
```

## 3. Limpar caches Laravel

```bash
cd /var/www/gestgo-api
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
```

## 4. Validar

```bash
php artisan about
# Confirme environment=production e debug=false

curl -sI https://api.gestgo.com.br/up | head
# Esperado: headers X-Content-Type-Options, X-Frame-Options, HSTS (se HTTPS)
```

## 5. Regenerar token do Business Hub (se já vazou ou era antigo)

No painel Plataforma → Integrações → Business Hub → regenerar token.  
Atualize o consumidor (gestor_app) com o novo valor.
