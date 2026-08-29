#!/bin/sh
set -eu

attempt=1
maximum_attempts="${WALLAI_INIT_ATTEMPTS:-30}"

until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge "$maximum_attempts" ]; then
        echo "Database initialization failed after $maximum_attempts attempts." >&2
        exit 1
    fi

    echo "Database is not ready yet (attempt $attempt/$maximum_attempts)." >&2
    attempt=$((attempt + 1))
    sleep 2
done

php artisan schedule:run --no-interaction

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan wallai:doctor --deployment
else
    php artisan wallai:doctor
fi
