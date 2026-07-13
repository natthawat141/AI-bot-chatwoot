# Production deploy (single GCP VM)

Full stack in one `docker compose`: MySQL · Laravel (php-fpm) · nginx · LINE bot,
plus observability: Loki · Promtail · Prometheus · cAdvisor · Grafana.

**Recommended VM:** Ubuntu 22.04/24.04, `e2-medium` (2 vCPU / 4 GB). The
observability stack needs the extra RAM; `e2-small` works only without it.

## 1. Provision the VM (once)

```bash
git clone <your-public-repo> line && cd line
bash deploy/bootstrap-vm.sh   # installs Docker + compose, opens firewall
# log out/in once so your user picks up the docker group
```

## 2. Configure secrets (once)

```bash
cp .env.prod.example .env.prod
# generate an app key and a shared bot token:
docker compose --env-file .env.prod -f docker-compose.prod.yml run --rm management php artisan key:generate --show
openssl rand -hex 32     # use for BOT_API_TOKEN
nano .env.prod           # fill APP_KEY, BOT_API_TOKEN, LINE_*, OPENROUTER_API_KEY, passwords
```

## 3. Launch

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

Startup order is automatic: `mysql → management (migrate+seed) → nginx → bot`.

## 4. Point LINE at it

Webhook URL: `http://<vm-ip>/webhook` (add a domain + TLS for real HTTPS —
e.g. put Caddy or nginx TLS in front, or keep ngrok for a quick demo).

## Access

| What | URL |
|---|---|
| LINE webhook | `http://<vm-ip>/webhook` |
| Admin (Filament) | `http://<vm-ip>/` |
| Grafana (logs + metrics) | `http://<vm-ip>:3000` — login `admin` / `GRAFANA_ADMIN_PASSWORD` |

Grafana comes with **Loki** (logs) and **Prometheus** (metrics) datasources
pre-wired. In Grafana → Explore → Loki, filter by `container` (e.g.
`{container="line-bot-service"}`). For container CPU/RAM dashboards, import
dashboard ID **893** (Docker/cAdvisor) — datasource Prometheus.

> Keep Grafana private: don't open port 3000 in the firewall; reach it via
> `gcloud compute ssh <vm> -- -L 3000:localhost:3000` and browse `localhost:3000`.

## Update a deployed release

```bash
git pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

> If you changed frontend (Vite) assets, refresh the shared public volume:
> `docker compose -f docker-compose.prod.yml down && docker volume rm line_mgmt-public && ...up`.

## Notes / verify on first boot

- This compose is **config-validated** (`docker compose config`, `nginx -t`) but a
  full `up` should be run once on the VM to confirm images build and seed cleanly.
- cAdvisor may need `/dev/kmsg` on some kernels; if it crash-loops, add
  `devices: ["/dev/kmsg"]` to the `cadvisor` service.
