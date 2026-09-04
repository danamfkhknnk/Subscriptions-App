FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    curl libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev zip unzip git \
    caddy supervisor nodejs npm

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev
RUN npm ci && npm run build
RUN rm -rf node_modules

RUN chown -R caddy:caddy /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
