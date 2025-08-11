# Ubuntu-only, using default packages (NGINX, PHP-FPM 8.3, Supervisor)
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Install default packages (no third-party repos)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    php-fpm \
    php-mysql \
    supervisor \
    ca-certificates \
    tzdata \
 && rm -rf /var/lib/apt/lists/*

# Make NGINX logs go to container stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log

# Copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Set sane ownership for web root
RUN chown -R www-data:www-data /var/www/html

# NGINX & Supervisor configs
COPY docker/nginx-default.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

# Run both services via Supervisor in foreground
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
