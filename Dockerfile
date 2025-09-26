# syntax=docker/dockerfile:1
########## Stage 1: install PHP deps with Composer ##########
FROM composer:2 AS vendor
WORKDIR /app

# Copy only what Composer needs first (better layer caching)
COPY app/composer.json app/composer.lock* ./

# Install deps (cache downloads with BuildKit)
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --prefer-dist --no-interaction --no-progress \
      --optimize-autoloader --classmap-authoritative \
      --no-ansi --audit --cache-dir=/tmp/composer-cache

# If your app has autoloadable code or scripts that affect autoload:
# COPY app/ .
# RUN composer dump-autoload --optimize --classmap-authoritative


########## Stage 2: runtime (nginx + php-fpm) ##########
FROM alpine:3.22

# Tiny runtime
RUN apk add --no-cache \
    nginx php83 php83-fpm php83-opcache supervisor \
    php83-pgsql php83-openssl php83-sodium
#php83-mysqli

ARG APP_UID=10001
ARG APP_GID=10001
RUN addgroup -g ${APP_GID} web && adduser -D -H -u ${APP_UID} -G web web

# Runtime dirs + logs to stderr for php-fpm
RUN set -eux; \
    mkdir -p /var/log/nginx /var/log/php83 /run/nginx /run/php /var/www \
             /var/cache/nginx /var/lib/nginx/tmp; \
    rm -f /etc/nginx/http.d/default.conf || true; \
    chown -R web:web /var/log/nginx /var/log/php83 /run/nginx /run/php \
                     /var/www /var/cache/nginx /var/lib/nginx;

# Configs
COPY config/nginx.conf                  /etc/nginx/nginx.conf
COPY config/supervisord.conf            /etc/supervisord.conf
COPY config/php-fpm.d/www.conf          /etc/php83/php-fpm.d/www.conf
COPY config/php-fpm.d/zz-env.conf       /etc/php83/php-fpm.d/zz-env.conf
COPY config/99-opcache.ini              /etc/php83/conf.d/99-opcache.ini

# App
COPY --chown=web:web app /var/www
COPY --chown=web:web --from=vendor /app/vendor /var/www/vendor

USER web


EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
