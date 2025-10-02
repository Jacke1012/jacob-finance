# syntax=docker/dockerfile:1

FROM alpine:3.22

# Tiny runtime
RUN apk add --no-cache \
    nginx supervisor

ARG APP_UID=10001
ARG APP_GID=10001
RUN addgroup -g ${APP_GID} web && adduser -D -H -u ${APP_UID} -G web web

# Runtime dirs + logs to stderr for php-fpm
RUN set -eux; \
    mkdir -p /var/log/nginx /run/nginx /var/www \
             /var/cache/nginx /var/lib/nginx/tmp; \
    rm -f /etc/nginx/http.d/default.conf || true; \
    chown -R web:web /var/log/nginx /run/nginx \
                     /var/www /var/cache/nginx /var/lib/nginx;

# Configs
COPY config/nginx.conf                  /etc/nginx/nginx.conf
COPY config/supervisord.conf            /etc/supervisord.conf

# App
COPY --chown=web:web app /var/www

USER web


EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
