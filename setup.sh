#!/bin/sh
set -eu

COMPOSE_FILE="compose.production.yaml"
ENV_FILE=".env.production"
ENV_EXAMPLE=".env.production.example"
ADMIN_STATE_FILE=".fidoma-admin"
INSTALL_STATE_FILE=".fidoma-installed"

fail() {
    printf 'Fehler: %s\n' "$1" >&2
    exit 1
}

random_hex() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex "$1"
        return
    fi

    od -An -N "$1" -tx1 /dev/urandom | tr -d ' \n'
}

random_base64() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 32 | tr -d '\n'
        return
    fi

    dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64 | tr -d '\n'
}

set_env_value() {
    key="$1"
    value="$2"
    file="$3"
    temporary_file="${file}.tmp"

    awk -v key="$key" -v value="$value" '
        BEGIN { updated = 0 }
        index($0, key "=") == 1 {
            print key "=" value
            updated = 1
            next
        }
        { print }
        END {
            if (! updated) {
                print key "=" value
            }
        }
    ' "$file" > "$temporary_file"
    mv "$temporary_file" "$file"
}

command -v docker >/dev/null 2>&1 || fail 'Docker ist nicht installiert.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose v2 ist nicht verfügbar.'
[ -f "$COMPOSE_FILE" ] || fail "$COMPOSE_FILE wurde nicht gefunden."
[ -f "$ENV_EXAMPLE" ] || fail "$ENV_EXAMPLE wurde nicht gefunden."

if [ ! -f "$ENV_FILE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    chmod 600 "$ENV_FILE"

    app_key="base64:$(random_base64)"
    database_password="$(random_hex 24)"
    database_root_password="$(random_hex 32)"

    set_env_value APP_KEY "$app_key" "$ENV_FILE"
    set_env_value DB_PASSWORD "$database_password" "$ENV_FILE"
    set_env_value APP_URL "${FIDOMA_APP_URL:-http://localhost:${FIDOMA_PORT:-8000}}" "$ENV_FILE"

    cat >> "$ENV_FILE" <<EOF

# Werte für Docker Compose
FIDOMA_IMAGE=${FIDOMA_IMAGE:-ghcr.io/fidoma/fidoma:latest}
FIDOMA_PORT=${FIDOMA_PORT:-8000}
FIDOMA_DB_DATABASE=fidoma
FIDOMA_DB_USERNAME=fidoma
FIDOMA_DB_PASSWORD=$database_password
FIDOMA_DB_ROOT_PASSWORD=$database_root_password
EOF
fi

printf 'Fidoma-Container werden geladen und gestartet ...\n'
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" pull --ignore-pull-failures
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" up -d --remove-orphans

printf 'Datenbankmigrationen werden ausgeführt ...\n'
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" exec -T app \
    php artisan migrate --force --no-interaction
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" exec -T app \
    php artisan optimize --no-interaction

if [ ! -f "$INSTALL_STATE_FILE" ]; then
    admin_name="${FIDOMA_ADMIN_NAME:-Administrator}"
    admin_email="${FIDOMA_ADMIN_EMAIL:-admin@fidoma.local}"
    admin_password="$(random_hex 12)"

    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" exec -T app \
        php artisan make:filament-user \
        --name="$admin_name" \
        --email="$admin_email" \
        --password="$admin_password" \
        --panel=admin \
        --no-interaction

    umask 077
    cat > "$ADMIN_STATE_FILE" <<EOF
E-Mail: $admin_email
Passwort: $admin_password
EOF
    : > "$INSTALL_STATE_FILE"
fi

port="$(awk -F= '/^FIDOMA_PORT=/{print $2; exit}' "$ENV_FILE")"
port="${port:-8000}"

printf '\nFidoma ist bereit: http://localhost:%s/admin\n' "$port"
if [ -f "$ADMIN_STATE_FILE" ]; then
    printf 'Die initialen Zugangsdaten stehen in %s. Löschen Sie die Datei nach dem ersten Login.\n' "$ADMIN_STATE_FILE"
    cat "$ADMIN_STATE_FILE"
fi
