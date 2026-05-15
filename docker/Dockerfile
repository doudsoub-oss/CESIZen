FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libpq-dev \
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
    && mkdir -p /var/www/html \
    && chown -R app:app /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-cesizen.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

USER app

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
