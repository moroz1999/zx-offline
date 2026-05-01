FROM php:8.5-cli

# Install runtime dependencies and PHP extensions for the CLI app
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        zip \
        pdo \
        pdo_sqlite \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/conf.d/zx-offline.ini

WORKDIR /app
