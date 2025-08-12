# syntax=docker/dockerfile:1.7
# ---- stage: base-files (just to keep COPY clean) ----
FROM alpine:3.20 AS basefiles
WORKDIR /opt/bootstrap
# Minimal example app
RUN mkdir -p /app/public
RUN printf "<?php phpinfo();\n" > /app/public/index.php

# Nginx, PHP-FPM, Supervisor configs
RUN mkdir -p /opt/etc
# nginx.conf
RUN printf '%s\n' \
"worker_processes  auto;" \
"events { worker_connections 1024; }" \
"http {" \
"  include       mime.types;" \
"  default_type  application/octet-stream;" \
"  sendfile      on;" \
"  access_log    /dev/stdout;" \
"  error_log     /dev/stderr;" \
"  server {" \
"    listen 8080;" \
"    server_name _;" \
"    root /var/www/public;" \
"    index index.php;" \
"    location / {" \
"      try_files \$uri /index.php?\$query_string;" \
"    }" \
"    location ~ \.php$ {" \
"      include fastcgi_params;" \
"      fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;" \
"      fastcgi_pass unix:/run/php/php-fpm.sock;" \
"      fastcgi_index index.php;" \
"    }" \
"  }" \
"}" \
> /opt/etc/nginx.conf

# php-fpm pool (www.conf)
RUN mkdir -p /opt/etc/php/php-fpm.d
RUN printf '%s\n' \
"[www]" \
"user = nginx" \
"group = nginx" \
"listen = /run/php/php-fpm.sock" \
"listen.owner = nginx" \
"listen.group = nginx" \
"pm = dynamic" \
"pm.max_children = 8" \
"pm.start_servers = 2" \
"pm.min_spare_servers = 1" \
"pm.max_spare_servers = 4" \
"catch_workers_output = yes" \
"php_admin_value[error_log] = /dev/stderr" \
"php_admin_flag[log_errors] = on" \
> /opt/etc/php/php-fpm.d/www.conf

# supervisord.conf
RUN printf '%s\n' \
"[supervisord]" \
"logfile = /dev/null" \
"nodaemon = true" \
"user = root" \
"[program:php-fpm]" \
"command = /usr/sbin/php-fpm -F" \
"autorestart = true" \
"stdout_logfile = /dev/stdout" \
"stdout_logfile_maxbytes = 0" \
"stderr_logfile = /dev/stderr" \
"stderr_logfile_maxbytes = 0" \
"[program:nginx]" \
"command = /usr/sbin/nginx -g 'daemon off;'" \
"autorestart = true" \
"stdout_logfile = /dev/stdout" \
"stdout_logfile_maxbytes = 0" \
"stderr_logfile = /dev/stderr" \
"stderr_logfile_maxbytes = 0" \
> /opt/etc/supervisord.conf


# ---- stage: final tiny runtime image ----
FROM alpine:3.20

# Install only what we need; php83 is current in Alpine 3.20.
# Adjust the php* packages if you need extensions later.
RUN apk add --no-cache \
      nginx \
      php83 \
      php83-fpm \
      php83-opcache \
      supervisor \
      php83-mysqli \
      php83-pdo_mysql

# Create runtime dirs
RUN set -eux; \
    adduser -D -H -s /sbin/nologin nginx || true; \
    mkdir -p /run/nginx /run/php /var/www; \
    chown -R nginx:nginx /var/www /run/php; \
    # Remove default nginx site if present
    rm -f /etc/nginx/http.d/default.conf || true

# Copy in configs and app
COPY --from=basefiles /opt/etc/nginx.conf /etc/nginx/nginx.conf
COPY --from=basefiles /opt/etc/php/php-fpm.d/www.conf /etc/php83/php-fpm.d/www.conf
COPY --from=basefiles /opt/etc/supervisord.conf /etc/supervisord.conf
COPY --from=basefiles /app /var/www

# PHP-FPM expects a main config; the package provides it.
# Ensure php-fpm logs go to stderr/stdout via pool settings (already set).

# Keep the image lean: no cache, no docs, no locales beyond Alpine defaults.
# (apk --no-cache already avoids /var/cache/apk)
RUN rm -rf /var/cache/* /tmp/* /var/log/*

EXPOSE 8080
# Healthcheck: basic HTTP fetch
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1

# Supervisord runs both processes in foreground.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
