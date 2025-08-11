FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx php-fpm php-mysql supervisor ca-certificates tzdata \
 && rm -rf /var/lib/apt/lists/*

# Let PHP-FPM see env vars from Kubernetes
RUN sed -ri 's@^;?\s*clear_env\s*=\s*yes@clear_env = no@' /etc/php/8.3/fpm/pool.d/www.conf

# Stream NGINX logs to stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log

# App
WORKDIR /var/www/html
COPY . /var/www/html
#RUN chown -R www-data:www-data /var/www/html

# NGINX site (uses default PHP-FPM socket)
COPY docker/nginx-default.conf /etc/nginx/sites-available/default

# Supervisor to run both in one container
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

# Start via entrypoint so the drop-in is created before php-fpm starts
CMD ["/entrypoint.sh"]


#CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
