#!/bin/sh
set -e
mkdir -p /run/php /var/log/supervisor
rm -f /run/php/php8.3-fpm.sock
exec "$@"
