# AGENTS.md

## Cursor Cloud specific instructions

### What this repo is

`dev-test-repo` is a **Docker Compose stub** for the EIDOC PHP stack. The tracked repo only contains `docker-compose.yml`, root `Dockerfile`, and `README.md`. A full checkout also needs local directories that compose references but does not commit: `docker/php/`, `docker/nginx/`, and `www/`.

### Prerequisites

- **Docker** with the Compose plugin (`docker compose`). Cloud VMs need `fuse-overlayfs` as the storage driver and `iptables-legacy` (see setup notes in the VM image).
- No Node/Python/Composer lockfiles in this repo; dependencies are container images only.

### First-time bootstrap (required before `docker compose up`)

If `docker/php/Dockerfile`, nginx config, or `www/` are missing, create minimal scaffolding:

1. `docker/php/Dockerfile` — PHP 8.2-FPM image (see root `Dockerfile`; add `libonig-dev`, `libpq-dev`, and `pdo_pgsql` for PostgreSQL).
2. `docker/nginx/conf.d/default.conf` — nginx → `php:9000` FastCGI for `/var/www/html`.
3. `www/index.php` — any PHP entrypoint (health page is enough for dev verification).
4. `www/call-me/.env` plus empty `www/call-me/app/` and `www/call-me/public/` — required by compose; **callme will restart** until real MiroTalk CME assets are present. Core web/db/redis still work without a healthy callme container.

Empty dirs for certbot mounts: `docker/nginx/letsencrypt/`, `docker/nginx/www/`.

### Running the stack

From repo root:

```bash
sudo docker compose pull
sudo docker compose build php
sudo docker compose up -d
```

| Service | URL / port | Notes |
|---------|------------|--------|
| nginx + PHP app | http://localhost | Main entry |
| Adminer | http://localhost:8081 | DB UI; server `db`, user `eidoc`, password `eidoc_pass`, database `eidoc` |
| PostgreSQL | localhost:5432 | Same credentials as compose |
| Redis | localhost:6379 | No auth |
| callme (MiroTalk) | internal :8000 | Optional; needs full `www/call-me/` tree |

Stop: `sudo docker compose down`. Data persists in compose volumes `eidoc_pgdata` and `eidoc_redis`.

### Lint / test

No application test suite or linters are defined in this repo. Verification is operational:

- `curl http://localhost/` — PHP page with PostgreSQL + Redis checks
- `curl -o /dev/null -w '%{http_code}\n' http://localhost:8081/` — Adminer `200`
- `docker compose ps` — `db` healthy, `nginx`/`php`/`redis`/`adminer` up

### Gotchas

- Compose builds PHP from `docker/php/Dockerfile`, **not** the root `Dockerfile`.
- `mbstring` build needs `libonig-dev` in the PHP image.
- `callme` mounts `./www/call-me/app` over the image’s `/src/app`; empty mounts break that service (nginx still starts).
- Use `sudo docker` if the current user is not in the `docker` group after install.
