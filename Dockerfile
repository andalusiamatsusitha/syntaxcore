FROM php:8.3-fpm-alpine

# Install system runtime & build dependencies
RUN apk add --no-cache \
    bash \
    curl \
    mariadb-client \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Install Composer (pinned for reproducible builds)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Set permissions for storage
RUN mkdir -p /var/www/html/storage/cache \
             /var/www/html/storage/logs \
             /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Install dependencies if composer.json exists
RUN if [ -f composer.json ]; then \
        composer install --no-interaction --optimize-autoloader --no-progress; \
    fi

# Configure entrypoint
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
