#!/bin/bash

# Manual deploy script for Mally (Laravel + Filament) application.
# Pulls the latest image from GHCR and restarts containers.
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
COMPOSE_FILE="docker-compose.prod.yml"

echo "=========================================="
echo "Deploying Mally - Environment: $ENVIRONMENT"
echo "=========================================="

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Error: .env file not found!"
    echo "Please create .env file from .env.example"
    exit 1
fi

# Load environment variables
set -a
source .env
set +a

echo "Step 1: Pulling latest image from registry..."
docker compose -f $COMPOSE_FILE pull

# Migrations run when the container starts (entrypoint), so back up first.
if docker compose -f $COMPOSE_FILE ps --status running --services | grep -qx mysql; then
    echo "Step 2: Backing up the database..."
    mkdir -p backups
    docker compose -f $COMPOSE_FILE exec -T mysql sh -c \
        'exec mysqldump --single-transaction --routines -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
        | gzip > "backups/mally-$(date +%Y%m%d-%H%M%S).sql.gz"
fi

echo "Step 3: Starting containers..."
docker compose -f $COMPOSE_FILE up -d

# The entrypoint migrates and caches config/routes/views; running them again
# here would race it. Wait until the app answers /up.
echo "Step 4: Waiting for the app to become healthy..."
status=starting
for i in $(seq 1 60); do
    status=$(docker inspect -f '{{.State.Health.Status}}' mally-app 2>/dev/null || echo starting)
    [ "$status" = "healthy" ] && break
    sleep 5
done
if [ "$status" != "healthy" ]; then
    echo "Error: the app did not become healthy!"
    docker compose -f $COMPOSE_FILE logs --tail=100 app
    exit 1
fi

echo "Step 5: Cleaning up old images..."
docker image prune -f

echo "=========================================="
echo "Deployment completed successfully!"
echo "=========================================="
echo ""
echo "Application is running at: http://localhost:${APP_PORT:-8080}"
echo ""
echo "Useful commands:"
echo "  - View logs: docker compose -f $COMPOSE_FILE logs -f"
echo "  - Stop app: docker compose -f $COMPOSE_FILE down"
echo "  - Restart: docker compose -f $COMPOSE_FILE restart"
echo "  - Shell access: docker compose -f $COMPOSE_FILE exec app sh"
echo ""
