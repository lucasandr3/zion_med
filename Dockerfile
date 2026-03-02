# Dockerfile para Laravel em produção (Easypanel)
FROM php:8.4-fpm-alpine

# Instalar dependências do sistema + node/npm
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    && rm -rf /var/cache/apk/*

# Instalar Redis (sem remover deps que você nem instalou aqui)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurações PHP customizadas
RUN echo "memory_limit = 256M" > $PHP_INI_DIR/conf.d/memory.ini \
    && echo "upload_max_filesize = 20M" > $PHP_INI_DIR/conf.d/uploads.ini \
    && echo "post_max_size = 20M" >> $PHP_INI_DIR/conf.d/uploads.ini \
    && echo "max_execution_time = 60" > $PHP_INI_DIR/conf.d/timeouts.ini \
    && echo "opcache.enable=1" > $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> $PHP_INI_DIR/conf.d/opcache.ini

# Configurar Nginx + Supervisor
COPY docker/nginx-production.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar usuário www
RUN addgroup -g 1000 www && adduser -D -u 1000 -G www www

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar projeto
COPY --chown=www:www . .

# Garantir tmp e permissões (antes de rodar artisan)
RUN chmod 1777 /tmp \
    && mkdir -p /var/www/html/storage/tmp \
    && chown -R www:www /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Usar tmp interno do Laravel (mais estável em plataformas)
ENV TMPDIR=/var/www/html/storage/tmp

# Instalar deps PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build do front (Vite/Mix)
RUN npm ci && npm run build && npm cache clean --force

# Otimizações Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Logs
RUN mkdir -p /var/log/supervisor /var/log/nginx

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
