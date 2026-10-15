# 05 — Dependências e deploy (sem Docker)

## API (`zion_med`)

```bash
cd /var/www/gestgo-api
git pull   # ou rsync do artefato
composer install --no-dev --optimize-autoloader
# Se ainda não rodou localmente o update de segurança:
composer update guzzlehttp/guzzle league/commonmark laravel/framework --with-dependencies
composer audit

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl reload php8.4-fpm   # versão da sua VPS
sudo systemctl reload nginx
```

### Permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## App (`zion_med_front`)

```bash
# Na CI ou máquina de build
npm ci
npm audit
npm run build:prod
rsync -avz --delete dist/.../ user@vps:/var/www/gestgo-app/
```

Não rode `ng serve` na internet.

## Site (`gestgo-site`)

Copie os estáticos incluindo `modelos_link_bio/` e `assets/js/site.js` com `apiBase` de produção.

## Saúde pós-deploy

| Check | Comando / ação |
|-------|----------------|
| API health | `curl https://api.gestgo.com.br/up` |
| Login app | Login real + 1 ação autenticada |
| Site signup | Network → request para `api.gestgo.com.br` |
| Webhook Asaas | Evento teste no painel Asaas |
| Form público | Abrir `/f/{token}` e validar CPF (prefill mascarado) |
