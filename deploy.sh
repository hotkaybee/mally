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

echo "Step 2: Starting containers..."
docker compose -f $COMPOSE_FILE up -d

echo "Step 3: Waiting for services to be healthy..."
sleep 10

# Check if containers are running
if ! docker compose -f $COMPOSE_FILE ps | grep -q "Up"; then
    echo "Error: Containers failed to start!"
    docker compose -f $COMPOSE_FILE logs
    exit 1
fi

echo "Step 4: Running post-deployment tasks..."
docker compose -f $COMPOSE_FILE exec -T app php artisan migrate --force
docker compose -f $COMPOSE_FILE exec -T app php artisan config:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan route:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan view:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan filament:optimize

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
