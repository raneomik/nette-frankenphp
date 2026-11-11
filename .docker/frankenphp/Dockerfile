#syntax=docker/dockerfile:1

# Debian (glibc) over Alpine (musl) :  https://frankenphp.dev/docs/performance/
FROM dunglas/frankenphp:1-php8.5-trixie AS frankenphp_upstream

# ---------- Base (dev / prod) ----------
FROM frankenphp_upstream AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /srv

RUN <<-EOF
	set -eux
	install-php-extensions apcu gmp intl opcache zip zlib

	groupadd --gid "${GROUP_ID}" app
	useradd --uid "${USER_ID}" --gid app --create-home --shell /usr/sbin/nologin app

	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

	mkdir -p /data/caddy /config/caddy
	chown -R app:app /data /config
EOF

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link .docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link .docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Migrations SQL (db/migrations) : https://github.com/amacneil/dbmate, binary.
# Same file as Nette DSN (config/common.neon) ;
# _txlock=immediate & _busy_timeout are provided by SqliteConfigurator.
COPY --link --from=ghcr.io/amacneil/dbmate:2.37.0 /usr/local/bin/dbmate /usr/local/bin/dbmate
ENV DATABASE_URL="sqlite:/srv/var/data/app.db?_busy_timeout=5000&_txlock=immediate"

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'

# ---------- Dev ----------
FROM base AS dev

ENV NETTE_DEBUG=1 \
    FRANKENPHP_WORKER_CONFIG=watch

COPY --link --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN <<-EOF
	set -eux
	apt-get update
	apt-get install -y --no-install-recommends file git sqlite3 unzip
	rm -rf /var/lib/apt/lists/*
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	install-php-extensions xdebug
	git config --system --add safe.directory /srv
	chown app:app /srv
EOF

COPY --link .docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

USER app
# dbmate also updates db/schema.sql (full schema, to commit: it shows the diff)
CMD mkdir -p var/data && dbmate up; \
    exec frankenphp run --config /etc/frankenphp/Caddyfile --watch


# ---------- Assets (Tailwind + Vite) ----------
# The output (www/assets) is ignored by git: it must be built in the image
FROM node:24-slim AS assets

WORKDIR /app

COPY --link package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Tailwind scans the Latte templates to find the classes in use
COPY --link vite.config.ts .gitignore ./
COPY --link assets ./assets
COPY --link app ./app

# "build" runs tailwind then vite (app.js imports assets/.build/tailwind.css)
RUN npm_config_yes=true npm run build


# ---------- Prod builder ----------
FROM base AS prod_builder

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --link --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# deps first: a code change does not rerun composer install.
# The (optional) secret avoids the GitHub API rate limit for dev-main packages in CI.
COPY --link composer.json composer.lock ./
RUN --mount=type=secret,id=composer_auth,env=COMPOSER_AUTH \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=.docker --exclude=assets --exclude=node_modules . ./
COPY --link --from=assets /app/www/assets ./www/assets

RUN <<-EOF
	set -eux
	composer dump-autoload --classmap-authoritative --no-dev
	rm -rf var
EOF

# ---------- Prod ----------
FROM base AS prod

ENV NETTE_DEBUG=0 \
    DBMATE_NO_DUMP_SCHEMA=true \
    APP_RUNNER=frankenphp \
    FRANKENPHP_SITE_CONFIG="worker ./www/index.php"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link .docker/frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

# The code belongs to root: the app user can only write to var/
COPY --from=prod_builder /srv /srv
RUN <<-EOF
	set -eux
	mkdir -p var/log var/temp/sessions var/data
	chown -R app:app var
EOF

USER app

# Probes /health ("PHP  worker responds"), not only Caddy.
# redirects are followed and local certificate is accepted to work
# with SERVER_NAME=localhost (compose.yml) and SERVER_NAME=:80 (behind proxy).
HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --start-interval=2s --retries=3 \
    CMD php -r 'exit(false === @file_get_contents("http://localhost/health", context: stream_context_create(["http" => ["timeout" => 4], "ssl" => ["verify_peer" => false, "verify_peer_name" => false]])) ? 1 : 0);'

# Migrate before start : container stops on fail, is never
# "healthy", and docker rollout keeps previous version.
# Without the Mercure secret the hub refuses every token, yet /health still answers: stop
# here so that the container is never "healthy" and docker rollout keeps the previous version.
CMD test -n "$MERCURE_JWT_SECRET" || { echo 'MERCURE_JWT_SECRET is not set (app.env)' >&2; exit 1; }; \
    rm -rf var/temp/cache; \
    dbmate up || exit 1; \
    exec frankenphp run --config /etc/frankenphp/Caddyfile
