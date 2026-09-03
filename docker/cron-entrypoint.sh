#!/bin/sh
set -e

TZ_VALUE="${TZ:-Asia/Ho_Chi_Minh}"
echo "date.timezone = ${TZ_VALUE}" >> /usr/local/etc/php/conf.d/99-dpanel-runtime.ini

if [ ! -f config/.config.php ]; then
    echo "[cron] config/.config.php not found"
    exit 1
fi

echo "[cron] starting scheduler (every 1 minute)"

while true; do
    php /var/www/html/xcat Cron || true
    sleep 60
done
