#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() { echo -e "${GREEN}[DPanel]${NC} $*"; }
warn() { echo -e "${YELLOW}[DPanel]${NC} $*"; }
error() { echo -e "${RED}[DPanel]${NC} $*" >&2; }

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        error "Missing required command: $1"
        exit 1
    fi
}

random_string() {
    openssl rand -hex 24
}

load_env() {
    if [ ! -f .env ]; then
        cp .env.example .env
        info "Created .env from .env.example"
    fi
    # shellcheck disable=SC1091
    set -a && source .env && set +a
}

set_env_var() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

prompt_if_empty() {
    local var_name="$1"
    local prompt_text="$2"
    local default_value="${3:-}"
    local current_value="${!var_name:-}"

    if [ -z "$current_value" ]; then
        if [ -n "$default_value" ]; then
            read -r -p "$prompt_text [$default_value]: " input
            current_value="${input:-$default_value}"
        else
            read -r -p "$prompt_text: " current_value
        fi
        set_env_var "$var_name" "$current_value"
        # shellcheck disable=SC1091
        set -a && source .env && set +a
    fi
}

generate_config() {
    info "Generating config/.config.php"

    cp config/.config.example.php config/.config.php

    local app_key="${APP_KEY:-$(random_string)}"
    local mu_key="${MU_KEY:-$(random_string)}"

    set_env_var APP_KEY "$app_key"
    set_env_var MU_KEY "$mu_key"

    # Prefer host PHP; fall back to Composer/PHP Docker image so VPS without php-cli still works
    if command -v php >/dev/null 2>&1; then
        php docker/setup-config.php
    else
        info "php-cli not found on host; running setup-config via Docker"
        docker run --rm -v "$ROOT_DIR":/app -w /app php:8.3-cli php docker/setup-config.php
    fi

    if [ ! -f config/appprofile.php ]; then
        cp config/appprofile.example.php config/appprofile.php
    fi
}

check_docker() {
    require_command docker
    if ! docker compose version >/dev/null 2>&1; then
        error "Docker Compose plugin is required (docker compose)."
        exit 1
    fi
}

wait_for_php() {
    local attempts=30
    local i=1
    while [ "$i" -le "$attempts" ]; do
        if docker compose exec -T php php -v >/dev/null 2>&1; then
            return 0
        fi
        sleep 3
        i=$((i + 1))
    done
    error "PHP container did not become ready in time."
    exit 1
}

run_migrations() {
    info "Running database migrations"
    docker compose exec -T php php xcat Migration new
    docker compose exec -T php php xcat Migration latest
    docker compose exec -T php php xcat Tool importSetting
}

create_admin() {
    info "Creating admin account: ${ADMIN_EMAIL}"
    docker compose exec -T php php xcat Tool createAdmin "${ADMIN_EMAIL}" "${ADMIN_PASSWORD}"
}

print_summary() {
    echo ""
    info "Installation complete!"
    echo ""
    echo "  Panel URL : ${APP_URL}"
    echo "  Admin     : ${ADMIN_EMAIL}"
    echo ""
    echo "Useful commands:"
    echo "  docker compose ps"
    echo "  docker compose logs -f"
    echo "  docker compose exec php php xcat Tool"
    echo ""
    warn "Configure HTTPS in front of Nginx (Caddy, Traefik, or host Nginx + Certbot)."
}

main() {
    info "DPanel Docker installer"
    check_docker
    load_env

    prompt_if_empty APP_URL "Panel URL (must start with https://)" "https://panel.example.com"
    prompt_if_empty DB_ROOT_PASSWORD "MariaDB root password" "$(random_string)"
    prompt_if_empty DB_PASSWORD "MariaDB user password" "$(random_string)"
    prompt_if_empty ADMIN_EMAIL "Admin email" "admin@example.com"
    prompt_if_empty ADMIN_PASSWORD "Admin password" "$(openssl rand -base64 16)"

    if [ -z "${APP_KEY:-}" ]; then
        set_env_var APP_KEY "$(random_string)"
    fi
    if [ -z "${MU_KEY:-}" ]; then
        set_env_var MU_KEY "$(random_string)"
    fi

    # shellcheck disable=SC1091
    set -a && source .env && set +a

    generate_config

    info "Building and starting containers"
    docker compose up -d --build

    wait_for_php
    run_migrations
    create_admin
    print_summary
}

main "$@"
