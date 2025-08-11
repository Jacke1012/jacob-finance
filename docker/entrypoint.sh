#!/usr/bin/env bash
set -euo pipefail

# Write a pool drop-in so php-fpm always sees your k8s env vars
cat >/etc/php/8.4/fpm/pool.d/zz-env.conf <<EOF
[www]
clear_env = no
env[DB_HOST] = ${DB_HOST:-mysql}
env[DB_NAME] = ${DB_NAME:-finance}
env[DB_USER] = ${DB_USER:-financeuser}
env[DB_PASS] = ${DB_PASS:-}
env[DB_PORT] = ${DB_PORT:-3306}
EOF

# (optional) log what's being used (mask password)
echo "php-fpm env -> DB_HOST=${DB_HOST:-mysql} DB_NAME=${DB_NAME:-finance} DB_USER=${DB_USER:-financeuser} DB_PORT=${DB_PORT:-3306}"

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
