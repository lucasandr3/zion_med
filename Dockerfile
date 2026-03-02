# Dockerfile para Laravel em produção (Easypanel)
FROM php:8.4-fpm-alpine

# Instalar dependências do sistema
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
    && apk del --no-cache ${PHPIZE_DEPS} \
    && rm -rf /var/cache/apk/*

# Instalar Redis
RUN apk add --no-cache ${PHPIZE_DEPS} \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache ${PHPIZE_DEPS}

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurações PHP customizadas
RUN echo "memory_limit = 256M" > $PHP_INI_DIR/conf.d/memory.ini \
    && echo "upload_max_filesize = 20M" >> $PHP_INI_DIR/conf.d/uploads.ini \
    && echo "post_max_size = 20M" >> $PHP_INI_DIR/conf.d/uploads.ini \
    && echo "max_execution_time = 60" >> $PHP_INI_DIR/conf.d/timeouts.ini \
    && echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> $PHP_INI_DIR/conf.d/opcache.ini

# Configurar Nginx
COPY docker/nginx-production.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar usuário www
RUN addgroup -g 1000 www && adduser -D -u 1000 -G www www

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY --chown=www:www . .

# Instalar dependências do Composer (sem dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Criar diretórios de logs
RUN mkdir -p /var/log/supervisor /var/log/nginx

# Ajustar permissões
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Otimizações Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expor porta
EXPOSE 80

# Iniciar supervisor (gerencia nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]