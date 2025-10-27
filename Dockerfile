# syntax=docker/dockerfile:1
########## Stage 1: install PHP deps with Composer ##########
FROM composer:2 AS vendor
WORKDIR /app

# Copy only what Composer needs first (better layer caching)
COPY app/composer.json app/composer.lock* ./

# Install deps (cache downloads with BuildKit)
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-dev --prefer-dist --no-interaction --no-progress \
      --optimize-autoloader --classmap-authoritative \
      --no-ansi --audit

# If your app has autoloadable code or scripts that affect autoload:
# COPY app/ .
# RUN composer dump-autoload --optimize --classmap-authoritative

########## Stage 2: assets##########
FROM alpine:3.22 AS assets
RUN apk add --no-cache curl
WORKDIR /assets

# Pin versions so builds are reproducible
ARG BOOTSTRAP_URL=https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css
ARG JQUERY_URL=https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js

# Download CDN assets into the build image (not your repo)
RUN curl -fsSL "$BOOTSTRAP_URL" -o bootstrap.min.css \
 && curl -fsSL "$JQUERY_URL"    -o jquery.min.js

########## Stage 3: runtime (nginx + php-fpm) ##########
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

# Assets
RUN mkdir -p /var/www/public/assets
COPY --chown=web:web --from=assets /assets/bootstrap.min.css /var/www/public/assets/bootstrap.min.css
COPY --chown=web:web --from=assets /assets/jquery.min.js     /var/www/public/assets/jquery.min.js

USER web


EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
