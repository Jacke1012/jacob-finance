# syntax=docker/dockerfile:1
FROM alpine:3.21

# Tiny runtime
RUN apk add --no-cache \
    nginx php83 php83-fpm php83-opcache supervisor \
    php83-pgsql
#php83-mysqli
# Runtime dirs + logs to stderr for php-fpm
RUN set -eux; \
    mkdir -p /var/log/nginx /run/nginx /run/php /var/www; \
    rm -f /etc/nginx/http.d/default.conf || true; \
    sed -i 's|^error_log = .*|error_log = /dev/stderr|' /etc/php83/php-fpm.conf

# Configs
COPY config/nginx.conf                  /etc/nginx/nginx.conf
COPY config/supervisord.conf            /etc/supervisord.conf
COPY config/php-fpm.d/www.conf          /etc/php83/php-fpm.d/www.conf
COPY config/php-fpm.d/zz-env.conf       /etc/php83/php-fpm.d/zz-env.conf
COPY config/99-opcache.ini              /etc/php83/conf.d/99-opcache.ini

# App
COPY app /var/www

EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
