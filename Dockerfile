FROM php:8.3-fpm-alpine

# Install system deps
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    caddy \
    supervisor \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Step 1: Install PHP deps first (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Step 2: Copy app code
COPY . .

# Step 3: Run composer post-scripts (autoload, etc)
RUN composer dump-autoload --optimize --no-dev

# Step 4: Build frontend assets
RUN npm ci && npm run build

# Step 5: Clean up node_modules (not needed at runtime)
RUN rm -rf node_modules

# Step 6: Fix permissions
RUN chown -R caddy:caddy /var/www \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Config files
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
