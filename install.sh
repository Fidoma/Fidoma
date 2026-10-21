#!/bin/sh
set -eu

REPOSITORY="${FIDOMA_REPOSITORY:-Fidoma/Fidoma}"
VERSION="${FIDOMA_VERSION:-main}"

if [ -n "${FIDOMA_INSTALL_DIR:-}" ]; then
    INSTALL_DIR="$FIDOMA_INSTALL_DIR"
elif [ -f setup.sh ] && [ -f compose.production.yaml ] && [ -f .env.production.example ]; then
    INSTALL_DIR=.
else
    INSTALL_DIR=fidoma
fi

fail() {
    printf 'Fehler: %s\n' "$1" >&2
    exit 1
}

case "$REPOSITORY" in
    *[!A-Za-z0-9._/-]* | /* | */ | *..*)
        fail 'FIDOMA_REPOSITORY enthält ungültige Zeichen.'
        ;;
esac

case "$VERSION" in
    *[!A-Za-z0-9._-]*)
        fail 'FIDOMA_VERSION enthält ungültige Zeichen.'
        ;;
esac

command -v curl >/dev/null 2>&1 || fail 'curl ist nicht installiert.'
command -v docker >/dev/null 2>&1 || fail 'Docker ist nicht installiert.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose v2 ist nicht verfügbar.'

if [ "$INSTALL_DIR" = "." ] && [ -f fidoma/.env.production ]; then
    fail 'Alte Installation unter ./fidoma gefunden. Verschiebe deren .env.production und Statusdateien zuerst in dieses Verzeichnis.'
fi

mkdir -p "$INSTALL_DIR"

download_file() {
    file="$1"
    target="$INSTALL_DIR/$file"
    temporary_target="${target}.download"
    source_url="https://raw.githubusercontent.com/${REPOSITORY}/${VERSION}/${file}"

    printf 'Lade %s ...\n' "$file"
    curl --proto '=https' --tlsv1.2 --fail --silent --show-error --location \
        "$source_url" --output "$temporary_target"
    mv "$temporary_target" "$target"
}

download_file setup.sh
download_file compose.production.yaml
download_file .env.production.example

chmod +x "$INSTALL_DIR/setup.sh"

if [ "$VERSION" != "main" ] && [ -z "${FIDOMA_IMAGE:-}" ]; then
    FIDOMA_IMAGE="ghcr.io/fidoma/fidoma:${VERSION}"
    export FIDOMA_IMAGE
fi

printf 'Starte Fidoma %s in %s ...\n' "$VERSION" "$INSTALL_DIR"
cd "$INSTALL_DIR"
exec ./setup.sh
