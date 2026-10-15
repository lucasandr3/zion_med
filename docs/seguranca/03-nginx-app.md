# 03 — Nginx do app Angular (`app.gestgo.com.br`)

Sem Docker: build local/CI → copiar `dist/` para a VPS → Nginx serve arquivos estáticos.

## 1. Build de produção

Na máquina de build (ou CI):

```bash
cd zion_med_front
npm ci
npm run build:prod
# Saída típica: dist/gestgo_front/browser  (confirme o path no angular.json)
```

## 2. Publicar na VPS

```bash
# Exemplo rsync
rsync -avz --delete dist/gestgo_front/browser/ user@vps:/var/www/gestgo-app/
```

## 3. Nginx

Use o `nginx.conf` do repositório como base (já inclui CSP, XFO, nosniff).  
Arquivo típico: `/etc/nginx/sites-available/app.gestgo.com.br`

```nginx
server {
    listen 443 ssl http2;
    server_name app.gestgo.com.br;

    root /var/www/gestgo-app;
    index index.html;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
    add_header Cross-Origin-Opener-Policy "same-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self' https://api.gestgo.com.br https://api-errorhub.zionai.com.br; frame-src 'self' blob:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'" always;

    location = /index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options "nosniff" always;
        try_files $uri =404;
    }
}
```

> Se usar **Cloudflare**, publique os mesmos headers no edge (Transform Rules) — meta CSP do `index.html` **não** cobre `frame-ancestors`.

## 4. Validar

```bash
sudo nginx -t && sudo systemctl reload nginx
curl -sI https://app.gestgo.com.br/ | grep -iE 'content-security|x-frame|strict-transport'
```

## Nota sobre o token

O app ainda guarda o Bearer em `localStorage` (mitigação completa = cookie HttpOnly; ver `07-sessao-httponly-futuro.md`).  
CSP + ausência de XSS são a defesa atual.
