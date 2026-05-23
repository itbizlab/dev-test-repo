# AGENTS.md

## Project Overview

EIDOC is a Docker Compose-based telemedicine platform. The repository contains infrastructure orchestration files only (`docker-compose.yml`, `Dockerfile`). The actual PHP application source code is expected in `./www/` and Docker service configs in `./docker/`.

## Cursor Cloud specific instructions

### Starting Docker

The Docker daemon must be started manually before running services:

```bash
sudo dockerd &>/tmp/dockerd.log &
sleep 3
```

### Running Services

```bash
cd /workspace && docker compose up -d --build
```

Core services and their ports:
| Service | Port | Purpose |
|---------|------|---------|
| nginx | 80, 443 | Reverse proxy / PHP app |
| php-fpm | 9000 (internal) | PHP application server |
| PostgreSQL | 5432 | Database (user: `eidoc`, password: `eidoc_pass`, db: `eidoc`) |
| Redis | 6379 | Cache / sessions |
| Adminer | 8081 | DB admin UI |
| callme | 8000 (internal) | MiroTalk video calling (requires actual app files in `./www/call-me/`) |

### Important Gotchas

- The `callme` service will restart continuously unless the actual MiroTalk app files are present in `./www/call-me/`. This does not affect the core PHP application.
- The `docker/php/Dockerfile` is not in the repo — it must be created locally with PHP 8.2-FPM, PostgreSQL (`pdo_pgsql`), and common extensions. See the local scaffolding setup.
- The `docker/nginx/conf.d/` directory needs at least one server block config pointing to the PHP-FPM container.
- PHP app source goes in `./www/` with `./www/public/` as the nginx document root.
- PostgreSQL health check gates the PHP container startup (`service_healthy` condition).

### Linting / Testing

This is an infrastructure skeleton repo — there are no application-level lint/test commands. Validation is done by running `docker compose up -d` and verifying services respond:

```bash
curl http://localhost/          # PHP app
curl http://localhost:8081/     # Adminer
docker exec eidoc_redis redis-cli PING
docker exec eidoc_postgres psql -U eidoc -d eidoc -c "SELECT 1;"
```

### Docker-in-Docker Notes (Cloud VM)

- Uses `fuse-overlayfs` storage driver (required for nested Docker in Firecracker VMs).
- Uses `iptables-legacy` (kernel doesn't support all nftables features).
- The Docker socket permissions need `sudo chmod 666 /var/run/docker.sock` after daemon start for non-root access.
