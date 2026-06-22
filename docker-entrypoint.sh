#!/bin/sh
set -eu

app_env="${APP_ENV:-prod}"
if [ "$app_env" != "dev" ] && [ -z "${JWT_SECRET:-}" ]; then
    echo "JWT_SECRET is required when APP_ENV is not dev" >&2
    exit 1
fi

max_attempts="${DB_FIX_MAX_ATTEMPTS:-30}"
attempt=1

while [ "$attempt" -le "$max_attempts" ]; do
    if php83 /var/www/bin/db_fix.php; then
        exec "$@"
    fi

    echo "db_fix failed, waiting for database ($attempt/$max_attempts)" >&2
    attempt=$((attempt + 1))
    sleep 2
done

echo "db_fix failed after $max_attempts attempts" >&2
exit 1
