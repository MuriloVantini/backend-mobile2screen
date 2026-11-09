FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
RUN npm run build


FROM composer:2 AS dependencies

WORKDIR /app

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader


FROM php:8.3-cli-alpine AS runtime

WORKDIR /app

RUN apk add --no-cache \
        icu-libs \
        libcurl \
        libpq \
        libzip \
        sqlite-libs \
        supervisor \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        icu-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
        postgresql-dev \
        sqlite-dev \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        curl \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        sockets \
        zip \
    && apk del .build-deps

COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=dependencies /app/bootstrap/cache ./bootstrap/cache
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && ln -s ../storage/app/public public/storage \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache database

USER www-data

EXPOSE 9187 9188

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -qO- "http://127.0.0.1:9187/up" >/dev/null || exit 1

ENTRYPOINT ["/app/docker/entrypoint.sh"]
