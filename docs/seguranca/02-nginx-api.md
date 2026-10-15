# 02 — Nginx da API (sem Docker)

Arquivo típico: `/etc/nginx/sites-available/api.gestgo.com.br`

## Objetivo

- TLS no Nginx (ou atrás do Cloudflare — ok)
- Encaminhar para PHP-FPM
- Headers de segurança (o middleware Laravel também envia; reforçar no edge)

## Exemplo de server block

```nginx
server {
    listen 443 ssl http2;
    server_name api.gestgo.com.br;

    # ssl_certificate / etc. (Let's Encrypt ou certificado do painel)

    root /var/www/gestgo-api/public;
    index index.php;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
    # API JSON — CSP mínima
    add_header Content-Security-Policy "default-src 'none'; frame-ancestors 'none'; base-uri 'none'" always;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # ajuste a versão
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name api.gestgo.com.br;
    return 301 https://$host$request_uri;
}
```

## Passos

```bash
sudo nano /etc/nginx/sites-available/api.gestgo.com.br
# cole/ajuste o bloco

sudo nginx -t
sudo systemctl reload nginx
```

## Webhook Asaas

Garanta que `POST /webhooks/asaas` chegue na API (não bloqueado por WAF sem o header `asaas-access-token`).

## Log Viewer / Scramble

Com `LOG_VIEWER_ENABLED=false` e `SCRAMBLE_PUBLIC=false`, as rotas ficam fechadas/desabilitadas.  
Se precisar abrir em homolog, use só VPN/IP allowlist no Nginx:

```nginx
location /log-viewer {
    allow 1.2.3.4;  # seu IP
    deny all;
    try_files $uri $uri/ /index.php?$query_string;
}
```
