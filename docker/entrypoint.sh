#!/bin/sh
set -e

TZ_VALUE="${TZ:-Asia/Ho_Chi_Minh}"
echo "date.timezone = ${TZ_VALUE}" >> /usr/local/etc/php/conf.d/99-dpanel-runtime.ini

wait_for_service() {
    host="$1"
    port="$2"
    name="$3"
    max_attempts=60
    attempt=1

    while [ "$attempt" -le "$max_attempts" ]; do
        if php -r "exit(@fsockopen(getenv('HOST'), (int) getenv('PORT')) ? 0 : 1);" HOST="$host" PORT="$port"; then
            echo "[entrypoint] ${name} is ready"
            return 0
        fi
        echo "[entrypoint] waiting for ${name} (${attempt}/${max_attempts})..."
        sleep 2
        attempt=$((attempt + 1))
    done

    echo "[entrypoint] timeout waiting for ${name}"
    exit 1
}

if [ -n "${DB_HOST:-}" ]; then
    wait_for_service "$DB_HOST" "${DB_PORT:-3306}" "MariaDB"
fi

if [ -n "${REDIS_HOST:-}" ]; then
    wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}" "Redis"
fi

mkdir -p \
    storage/framework/smarty/compile \
    storage/framework/smarty/cache \
    storage/framework/twig/cache \
    storage/logs

chown -R www-data:www-data storage 2>/dev/null || true

if [ ! -f config/.config.php ]; then
    echo "[entrypoint] config/.config.php not found. Run ./install.sh first."
    exit 1
fi

if [ ! -f config/appprofile.php ]; then
    cp config/appprofile.example.php config/appprofile.php
fi

exec "$@"
