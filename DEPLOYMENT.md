# Production Deployment Guide

Mally is a Laravel 12 + Filament 4 app. The admin panel lives at `/admin`.
Sessions, cache, and queues are all backed by **MySQL** (no Redis required).

Deployment uses a **registry-based** flow:

1. Push to `main` triggers GitHub Actions.
2. CI builds the Docker image (PHP deps + Vite/Tailwind assets) and pushes it to
   **GHCR** (`ghcr.io/hotkaybee/mally`).
3. CI then SSHes into the production server, pulls the new image, and restarts
   the containers.

The server only needs `docker-compose.prod.yml` and a production `.env` — not the
full source tree.

---

## Quick Start (TL;DR)

On the production server (one-time setup):

```bash
# 1. Create the deploy directory
sudo mkdir -p /var/www/mally && cd /var/www/mally

# 2. Copy docker-compose.prod.yml into this directory
#    (scp it, or curl from the repo)

# 3. Create the production .env (see "Environment File" below)
nano .env

# 4. Authenticate Docker with GHCR so it can pull the image
echo "<GHCR_PAT>" | docker login ghcr.io -u <github-username> --password-stdin

# 5. Pull and start
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

After this, every push to `main` deploys automatically.

---

## Prerequisites

- Docker and Docker Compose installed on the server.
- A GitHub Container Registry (GHCR) personal access token (PAT) with `read:packages`,
  or the package set to public.
- SSH access to the server for the GitHub Actions deploy job.

## Environment File

Create `/var/www/mally/.env` on the server:

```env
APP_NAME=Mally
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Russian is the default locale (en | ru | uz)
APP_LOCALE=ru
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mally
DB_USERNAME=mally
DB_PASSWORD=your_secure_password_here

# Database-backed drivers (no Redis)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# MySQL root password (used by the mysql container only)
DB_ROOT_PASSWORD=your_root_password_here

# Image to pull (override if you tag differently)
APP_IMAGE=ghcr.io/hotkaybee/mally:latest

# Host port of the app. It listens on 127.0.0.1 only (APP_BIND): the host
# nginx below is the only way in, and the app trusts its X-Forwarded-* headers.
APP_PORT=8080
# TRUSTED_PROXIES=127.0.0.1,172.16.0.0/12   (default: localhost + Docker gateway)

# Logs: daily files, warnings and errors only
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning

# Mail (login / registration / password-reset codes). Must be a real
# mailer: with MAIL_MAILER=log production refuses to send codes.
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Mally

# Telegram: the login bot and the admin bot must be two different bots
TELEGRAM_AUTH_BOT_TOKEN=
TELEGRAM_AUTH_BOT_USERNAME=
TELEGRAM_AUTH_WEBHOOK_SECRET=     # empty = the telegram:auth-poll worker
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
TELEGRAM_ADMIN_CHAT_IDS=          # comma-separated; the admin bot answers only these
TELEGRAM_ADMIN_USERNAME=

# Shop
STORE_CARD_NUMBER=
STORE_CARD_HOLDER=
STORE_PROMO_CODE=                 # empty = no promo code
STORE_PROMO_PERCENT=0
ORDERS_API_TOKEN=                 # empty = POST /api/orders closed
```

With `APP_URL=https://…` the session cookie is marked Secure and every URL
is generated as https.

### Generate an APP_KEY

```bash
echo "base64:$(openssl rand -base64 32)"
```

Or, after the first container is up:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan key:generate --show
```

### Create the first admin user

Filament login requires a user. After the first deploy:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan make:filament-user
docker compose -f docker-compose.prod.yml exec app php artisan admin:grant you@example.com
```

When upgrading an existing database, migration `2026_10_16_000001` marks old
users without a phone (and not shoppers) as admins. Check the result right
after the first deploy and revoke anyone unexpected:

```bash
docker compose -f docker-compose.prod.yml exec -T mysql sh -c \
  'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT id, email FROM users WHERE is_admin = 1"'
```

### Product photos

Photos uploaded in the admin are stored in `storage/app/public/products`
(inside the `./storage` volume) and served through the `public/storage` link
the container creates on start. If an older version uploaded photos to
private storage, copy them once:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan products:publish-images
```

Anything in the host's `./public/storage` folder (the old volume) must be
moved to `./storage/app/public`.

---

## GitHub Actions Setup

The workflow is `.github/workflows/deploy.yml`. It has two jobs:
`build-and-push` (builds + pushes to GHCR) and `deploy` (SSH pull + restart).

### Required repository secrets

| Secret | Purpose |
|---|---|
| `SERVER_HOST` | Production server hostname/IP |
| `SERVER_USER` | SSH user on the server |
| `SSH_PRIVATE_KEY` | Private key for that SSH user |
| `SERVER_PORT` | SSH port (optional, defaults to 22) |
| `GHCR_PAT` | PAT with `read:packages` for the server to pull a **private** image (optional if the package is public) |

`GITHUB_TOKEN` is provided automatically and is used to push the image to GHCR
during the build job.

### Make the GHCR package accessible

If you keep the image **private**, the server must log in with a PAT
(`GHCR_PAT`). To make it **public** instead, go to the package settings in GitHub
and change visibility — then the server pull needs no auth.

---

## Manual Deployment

A `deploy.sh` helper is included for manual deploys from the server:

```bash
chmod +x deploy.sh
./deploy.sh
```

It pulls the latest image, restarts containers, runs migrations, and caches config.

### Fully manual steps

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
docker compose -f docker-compose.prod.yml exec app php artisan filament:optimize
```

### Build locally instead of pulling

Uncomment the `build:` block in `docker-compose.prod.yml`, then:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

---

## Useful Commands

```bash
# Logs
docker compose -f docker-compose.prod.yml logs -f
docker compose -f docker-compose.prod.yml logs -f app

# Shell access
docker compose -f docker-compose.prod.yml exec app sh

# Run any artisan command
docker compose -f docker-compose.prod.yml exec app php artisan <command>

# Clear stale caches (after locale / config changes)
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear

# Stop / restart
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml restart
```

### Database backup / restore

Every deploy (CI or `deploy.sh`) writes a dump to `backups/` before the new
container migrates. Also back up daily (cron), together with the photos:

```bash
# Backup (-T: no TTY, so the dump isn't corrupted)
docker compose -f docker-compose.prod.yml exec -T mysql sh -c \
  'exec mysqldump --single-transaction --routines -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
tar czf storage_$(date +%Y%m%d).tar.gz storage/app/public

# Restore
docker compose -f docker-compose.prod.yml exec -T mysql \
  mysql -u mally -p mally < backup.sql
```

---

## SSL / HTTPS

The app container listens on plain HTTP (port 80, mapped to `APP_PORT`). Terminate
TLS in front of it with a reverse proxy. Example Nginx:

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        client_max_body_size 20M;
    }

    add_header Strict-Transport-Security "max-age=31536000" always;
}

server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}
```

Set `APP_URL=https://yourdomain.com` in `.env` so Laravel generates correct
URLs. The app trusts `X-Forwarded-*` only from `TRUSTED_PROXIES` (localhost and
the Docker gateway by default); without that every visitor would share the
proxy's IP and its rate limits.

---

## Troubleshooting

### Container won't start

```bash
docker compose -f docker-compose.prod.yml logs app
```

### Permission issues

```bash
docker compose -f docker-compose.prod.yml exec app \
  chown -R www-data:www-data storage bootstrap/cache
docker compose -f docker-compose.prod.yml exec app \
  chmod -R 775 storage bootstrap/cache
```

### Stale labels / translations

Mally caches config and translations aggressively. After changing locale or
Filament config:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear
```

### Database connection issues

```bash
docker compose -f docker-compose.prod.yml exec app php artisan db:show
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`
- [ ] Firewall allows only 80, 443, 22
- [ ] SSL/TLS configured via reverse proxy
- [ ] GHCR image private (with `GHCR_PAT`) or intentionally public
- [ ] SSH key for the deploy job is dedicated and least-privilege
- [ ] Regular database backups configured (plus `storage/app/public`)
- [ ] App port bound to 127.0.0.1 (`APP_BIND`), only the HTTPS proxy in front
- [ ] Real `MAIL_*` settings (codes are never sent through the `log` mailer)
- [ ] `SELECT email FROM users WHERE is_admin = 1` checked after the first migration
- [ ] `composer audit` clean (update Livewire / Filament / Laravel when it reports advisories)
