# syntax=docker/dockerfile:1.4@sha256:9ba7531bd80fb0a858632727cf7a112fbfd19b17e94c4e84ced81e24ef1a0dbc

FROM node:24-bookworm-slim@sha256:3638d9a6fe4030bd716be989438248074489337ba3275657f93595428be4fc03 AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS composer

FROM dunglas/frankenphp:1.12.7-builder-php8.5-bookworm@sha256:48f74f8e25f053bd9381220f0487c064d8835eaf6f794f1d197531d4d3fcc798 AS frankenphp-builder

COPY docker/frankenphp/main.go /go/src/app/caddy/frankenphp/main.go

WORKDIR /go/src/app/caddy

RUN go get google.golang.org/grpc@v1.82.1 \
    && go mod tidy

WORKDIR /go/src/app/caddy/frankenphp

RUN GOBIN=/usr/local/bin ../../go.sh install \
    -ldflags "-w -s -X 'github.com/caddyserver/caddy/v2.CustomVersion=FrankenPHP v1.12.7 PHP $PHP_VERSION Caddy' -X 'github.com/caddyserver/caddy/v2.CustomBinaryName=frankenphp' -X 'github.com/caddyserver/caddy/v2/modules/caddyhttp.ServerHeader=FrankenPHP Caddy'" \
    -buildvcs=true

FROM dunglas/frankenphp:1.12.7-php8.5-bookworm@sha256:8896df27f5fe22f4be4628a2cabfc9959229e1010b2890019f6768139a3dfbcf AS php-runtime

COPY --from=frankenphp-builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp

RUN install-php-extensions \
    curl \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    redis \
    zip \
    && if [ -n "$(getcap /usr/local/bin/frankenphp)" ]; then setcap -r /usr/local/bin/frankenphp; fi \
    && command -v setpriv > /dev/null

FROM php-runtime AS composer-dependencies

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY app ./app
COPY database/seeders ./database/seeders
RUN composer dump-autoload \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --classmap-authoritative

FROM php-runtime AS runtime

ARG WALLAI_VERSION=dev
ARG WALLAI_REVISION=unknown

LABEL org.opencontainers.image.title="WallAI" \
    org.opencontainers.image.description="Self-hosted AI wallpaper generator" \
    org.opencontainers.image.source="https://github.com/lauroguedes/wallai" \
    org.opencontainers.image.licenses="MIT" \
    org.opencontainers.image.version="${WALLAI_VERSION}" \
    org.opencontainers.image.revision="${WALLAI_REVISION}"

WORKDIR /app

COPY --chown=www-data:www-data . .
COPY --from=composer-dependencies --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/php.ini /usr/local/etc/php/conf.d/99-wallai.ini
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/wallai-entrypoint
COPY --chmod=755 docker/initialize.sh /usr/local/bin/wallai-initialize

RUN mkdir -p \
        /var/lib/wallai \
        storage/app/public/wallpapers \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && ln -s /app/storage/app/public /app/public/storage \
    && chown -R www-data:www-data /var/lib/wallai storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SERVER_NAME=:8080 \
    WALLAI_VERSION=${WALLAI_VERSION} \
    XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data

USER 33:33

EXPOSE 8080

ENTRYPOINT ["wallai-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
