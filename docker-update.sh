#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

if [ ! -f .env ]; then
    echo "Missing .env file. Run ./install.sh first."
    exit 1
fi

# shellcheck disable=SC1091
set -a && source .env && set +a

if [ ! -f config/.config.php ]; then
    cp config/.config.example.php config/.config.php
fi

php docker/setup-config.php

docker compose exec -T php composer install --no-dev --no-interaction --optimize-autoloader
docker compose exec -T php php xcat Update
docker compose exec -T php php xcat Tool importSetting
docker compose exec -T php php xcat Migration latest
docker compose up -d --build

echo "Update complete."
