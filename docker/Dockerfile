FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        ca-certificates \
        gnupg \
        unzip \
        libicu-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libpq-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        intl \
        gd \
        bcmath \
        pcntl \
        exif \
    && docker-php-ext-enable opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid app --shell /bin/bash --create-home app \
    && mkdir -p /var/www/html/node_modules /var/www/html/vendor \
    && chown -R app:app /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-cesizen.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

USER app

# Pre-populate vendor/ in the image so the `app-vendor` named volume
# initializes with content owned by the `app` user. Without this, the
# volume is created empty and owned by root, which breaks composer
# install at runtime.
COPY --chown=app:app composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
