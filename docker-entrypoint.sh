#!/bin/sh
set -eu

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
