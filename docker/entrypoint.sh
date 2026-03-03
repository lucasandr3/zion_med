#!/bin/sh
set -e

cd /var/www/html

# garantir diretórios e permissões
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/tmp bootstrap/cache
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# evita cache congelado do build
php artisan optimize:clear || true

# se existir APP_KEY, pode cachear
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache  || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf