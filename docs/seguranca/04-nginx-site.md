# 04 — Nginx do site marketing (`gestgo.com.br`)

## Problema crítico corrigido no código

`assets/js/site.js` apontava `apiBase` para **homolog**. Em produção deve ser:

```js
apiBase: 'https://api.gestgo.com.br',
```

Após o deploy, confirme no browser (View Source / Network) que signup e landing batem em `api.gestgo.com.br`.

## 1. Publicar arquivos (sem Docker)

```bash
# Na VPS, por exemplo:
sudo mkdir -p /var/www/gestgo-site
rsync -avz --delete \
  index.html assets/ comece/ blog/ privacidade/ termos-de-uso/ l/ \
  modelos_link_bio/ robots.txt sitemap.xml favicon.svg site.webmanifest \
  user@vps:/var/www/gestgo-site/
```

**Importante:** inclua `modelos_link_bio/` (antes faltava no Dockerfile; em deploy manual também costumava faltar).

## 2. Nginx

Base: `gestgo-site/nginx.conf` do repo (CSP enxuta + HSTS).

```nginx
server {
    listen 443 ssl http2;
    server_name gestgo.com.br www.gestgo.com.br;

    root /var/www/gestgo-site;
    index index.html;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
    add_header Cross-Origin-Opener-Policy "same-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://api.gestgo.com.br; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'; frame-src 'self'" always;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /comece/ {
        try_files $uri $uri/ /comece/index.html;
    }

    location /blog/ {
        try_files $uri $uri/ /blog/index.html;
    }

    location /modelos_link_bio/ {
        try_files $uri =404;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options "nosniff" always;
        try_files $uri =404;
    }
}
```

## 3. Cloudflare

Se o CF sobrescrever headers, **reaplique CSP** nas Transform Rules.  
Na auditoria, a CSP do repo **não** aparecia no live — isso é config de edge, não só do arquivo nginx.

## 4. Validar

```bash
curl -sI https://gestgo.com.br/ | grep -iE 'content-security|strict-transport|x-frame'
curl -s https://gestgo.com.br/assets/js/site.js | head -5
# Deve mostrar apiBase: 'https://api.gestgo.com.br'

curl -sI https://gestgo.com.br/modelos_link_bio/03_estetica.html | head -1
# Esperado: 200 (não 404)
```

## 5. Dados em homolog

Se o site público já enviou signups para `api-homolog`, revise/limpe leads e senhas na base de homolog e force reset se necessário.
