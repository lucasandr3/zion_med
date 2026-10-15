# 06 — Business Hub, n8n e Asaas

## Business Hub (conector)

O Bearer do conector enxerga **dados da plataforma** (by design atual). Mitigações no servidor:

### 1. Allowlist de IP (recomendado)

No `.env` da API:

```env
BUSINESS_HUB_CONNECTOR_ALLOWED_IPS=IP_DO_GESTOR_APP,OUTRO_IP
```

Depois:

```bash
php artisan config:clear && php artisan config:cache
```

### 2. Rotacionar token

Painel → Plataforma → Integrações → Business Hub → regenerar.  
Atualize o consumidor imediatamente.

### 3. Nginx (extra)

```nginx
location /api/v1/conector {
    allow 1.2.3.4;
    deny all;
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Asaas webhook

1. No painel Asaas, configure a URL `https://api.gestgo.com.br/webhooks/asaas`
2. Defina o access token = valor de `ASAAS_WEBHOOK_SECRET` no `.env`
3. Sem secret, a API responde **503** (comportamento seguro)

## n8n

O código **não** traz mais URL default com UUID.  
Se `/comece` ou demonstração precisam do n8n, preencha no `.env`:

```env
N8N_DEMONSTRACAO_WEBHOOK_URL=https://seu-n8n/...
N8N_WEBHOOK_ERRO_PAGAMENTO=https://seu-n8n/...
N8N_WHATSAPP_WEBHOOK_URL=https://seu-n8n/...
```

Proteja os webhooks n8n com autenticação/header secreto no próprio n8n.
