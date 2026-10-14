#!/usr/bin/env bash

set -euo pipefail

sudo -v
sudo apt-get update
sudo apt-get install -y ca-certificates curl

sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

. /etc/os-release
architecture="$(dpkg --print-architecture)"

printf 'Types: deb\nURIs: https://download.docker.com/linux/ubuntu\nSuites: %s\nComponents: stable\nArchitectures: %s\nSigned-By: /etc/apt/keyrings/docker.asc\n' \
    "$VERSION_CODENAME" "$architecture" \
    | sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null

sudo apt-get update
sudo apt-get install -y \
    docker-ce \
    docker-ce-cli \
    containerd.io \
    docker-buildx-plugin \
    docker-compose-plugin

sudo systemctl enable --now docker
sudo usermod -aG docker "$(id -un)"

sudo docker run --rm hello-world
sudo docker version --format 'Docker Server {{.Server.Version}}'
sudo docker compose version

echo
echo 'Docker wurde installiert. Bitte einmal ab- und wieder anmelden,'
echo 'damit die neue docker-Gruppenmitgliedschaft überall aktiv ist.'
