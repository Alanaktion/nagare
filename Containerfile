FROM ghcr.io/serversideup/php:8.5-frankenphp AS base

USER root

RUN install-php-extensions intl sockets imagick gd

USER www-data

FROM base AS development

ARG USER_ID
ARG GROUP_ID

USER root

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service frankenphp

RUN install-php-extensions xdebug

USER www-data

# Production: dependencies, built assets and code are baked into the image,
# so no bind mount is needed. Build with `docker build -f Containerfile --target production .`.
FROM base AS vendor

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# Vite needs PHP too: the Wayfinder plugin generates route helpers with artisan.
FROM vendor AS assets

COPY --from=node:24-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-slim /usr/local/lib/node_modules/corepack /usr/local/lib/node_modules/corepack

ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
    COREPACK_HOME=/tmp/corepack

COPY --chown=www-data:www-data package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./

RUN node /usr/local/lib/node_modules/corepack/dist/corepack.js pnpm install --frozen-lockfile

COPY --chown=www-data:www-data . .

RUN composer dump-autoload --no-dev --optimize && \
    node /usr/local/lib/node_modules/corepack/dist/corepack.js pnpm run build

FROM base AS production

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative
