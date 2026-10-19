#syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5-alpine AS frankenphp_upstream

# ---------- Base ----------
FROM frankenphp_upstream AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /srv

RUN <<-EOF
	set -eux
	apk add --no-cache file git libcap-utils
	install-php-extensions @composer apcu intl opcache zip

	addgroup -g "${GROUP_ID}" app
	adduser -D -u "${USER_ID}" -G app app

	# bind 80/443 sans être root
	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

	# Caddy/Mercure écrivent ici (mercure.db dans /data/caddy)
	mkdir -p /data/caddy /config/caddy
	chown -R app:app /data /config /srv
EOF

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link .docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link .docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# ---------- Dev ----------
FROM base AS dev

ENV NETTE_DEBUG=1 \
    XDEBUG_MODE=off \
    FRANKENPHP_WORKER_CONFIG=watch

RUN <<-EOF
	set -eux
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	install-php-extensions xdebug
	git config --system --add safe.directory /srv
EOF

COPY --link .docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

USER app
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch"]


# ---------- Prod builder ----------
FROM base AS prod_builder

ENV NETTE_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --link composer.* ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=.docker --exclude=node_modules --exclude=var . ./

RUN <<-EOF
	set -eux
	mkdir -p var/log var/temp
	composer dump-autoload --classmap-authoritative --no-dev
EOF

# ---------- Prod ----------
FROM base AS prod

ENV NETTE_DEBUG=0

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link .docker/frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY --from=prod_builder --chown=app:app /srv /srv

USER app
