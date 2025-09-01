# syntax=docker/dockerfile:1
FROM alpine:latest

# Tiny runtime
RUN apk add --no-cache \
    nginx php83 php83-fpm php83-opcache supervisor \
    php83-pgsql \
    php83-pdo php83-pdo_pgsql \
    php83-ctype php83-fileinfo php83-mbstring php83-openssl php83-tokenizer \
    php83-dom php83-session php83-xml php83-zip php83-xmlwriter \
    composer



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

# Copy App
COPY --chown=web:web app /var/www/app

WORKDIR /var/www/app
RUN composer install --no-dev --optimize-autoloader


USER web


EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
