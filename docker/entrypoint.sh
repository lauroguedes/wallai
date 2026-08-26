#!/bin/sh
set -eu

load_secret() {
    variable_name="$1"
    file_variable_name="${variable_name}_FILE"
    eval "file_path=\${$file_variable_name:-}"

    if [ -z "$file_path" ]; then
        return
    fi

    if [ ! -r "$file_path" ]; then
        echo "Secret file for $variable_name is not readable: $file_path" >&2
        exit 1
    fi

    secret_value=$(tr -d '\r\n' < "$file_path")
    export "$variable_name=$secret_value"
    unset "$file_variable_name"
}

for secret_name in APP_KEY REDIS_PASSWORD DB_PASSWORD MAIL_PASSWORD; do
    load_secret "$secret_name"
done

if [ "$(id -u)" -eq 0 ]; then
    exec setpriv --reuid=www-data --regid=www-data --init-groups -- "$0" "$@"
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required. Run ./bin/wallai install to generate it." >&2
    exit 1
fi

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database_path="${DB_DATABASE:-/var/lib/wallai/database.sqlite}"
    mkdir -p "$(dirname "$database_path")"
    touch "$database_path"
fi

if [ "${WALLAI_OPTIMIZE:-true}" = "true" ]; then
    php artisan package:discover --no-interaction
    php artisan optimize --no-interaction
fi

exec "$@"
