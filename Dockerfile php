FROM php:8.4-fpm

# Install NGINX and Supervisor
RUN apt-get update && apt-get install -y nginx supervisor \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Copy app files into container
COPY . /var/www/html

# Copy config files
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord"]
