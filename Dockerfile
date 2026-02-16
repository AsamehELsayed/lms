FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    libicu-dev \
    libpq-dev \
    libsodium-dev \
    ffmpeg \
    supervisor \
    procps \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sodium

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install PCOV
RUN pecl install pcov \
    && docker-php-ext-enable pcov \
    && echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini \
    && echo "pcov.clobber=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . /var/www/html

# Install Composer and NPM dependencies (skip Playwright browser download in Docker)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
RUN npm ci
RUN npm run production

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Configure PHP
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 50M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "post_max_size = 50M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "memory_limit = 512M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "max_execution_time = 300" >> "$PHP_INI_DIR/conf.d/uploads.ini"

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Configure PHP-FPM to run in foreground
RUN sed -i 's/;daemonize = yes/daemonize = no/' /usr/local/etc/php-fpm.conf \
    || echo "daemonize = no" >> /usr/local/etc/php-fpm.conf

# Ensure PHP-FPM pool configuration exists and is valid
RUN if [ ! -f /usr/local/etc/php-fpm.d/www.conf ]; then \
        cp /usr/local/etc/php-fpm.d/www.conf.default /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true; \
    fi

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Healthcheck to verify PHP-FPM is running (check if process is alive)
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD pgrep -f php-fpm || exit 1

# Use entrypoint script to ensure proper startup
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
