#!/usr/bin/env bash
# One-shot provisioning for a fresh Ubuntu 22.04/24.04 GCP VM.
# Installs Docker Engine + compose plugin, opens the firewall, and prepares the repo.
#   curl -fsSL <raw-url>/deploy/bootstrap-vm.sh | bash
# or: bash deploy/bootstrap-vm.sh
set -euo pipefail

echo "==> Installing Docker Engine + compose plugin"
if ! command -v docker >/dev/null 2>&1; then
  sudo apt-get update -y
  sudo apt-get install -y ca-certificates curl git ufw
  sudo install -m 0755 -d /etc/apt/keyrings
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
  echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
    $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
    sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo apt-get update -y
  sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

echo "==> Adding $USER to the docker group (log out/in to take effect)"
sudo usermod -aG docker "$USER" || true

echo "==> Enabling Docker on boot"
sudo systemctl enable --now docker

echo "==> Firewall: allow SSH + HTTP (80). Grafana (3000) is optional."
sudo ufw allow OpenSSH || true
sudo ufw allow 80/tcp || true
# Uncomment to expose Grafana publicly (otherwise reach it via SSH tunnel):
# sudo ufw allow 3000/tcp || true
sudo ufw --force enable || true

echo "==> Done. Next:"
echo "   1) cp .env.prod.example .env.prod  &&  edit .env.prod"
echo "   2) docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build"
echo "   3) Point the LINE webhook at http(s)://<this-host>/webhook"
