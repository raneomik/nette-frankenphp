FROM dunglas/frankenphp:1-php8.5-bookworm AS base

RUN echo "variables_order = \"EGPCS\"" >> $PHP_INI_DIR/conf.d/990-php.ini

WORKDIR /srv

FROM base AS dev

RUN install-php-extensions xdebug

RUN echo "[xdebug]" >> $PHP_INI_DIR/conf.d/991-xdebug.ini && \
    echo "xdebug.mode=debug" >> $PHP_INI_DIR/conf.d/991-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> $PHP_INI_DIR/conf.d/991-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> $PHP_INI_DIR/conf.d/991-xdebug.ini

CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch" ]

# Prod
FROM base AS prod
