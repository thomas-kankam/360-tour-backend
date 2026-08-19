#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

echo "==> Ensuring storage directories exist"
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "==> Clearing stale caches"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "==> Refreshing autoload and package discovery"
composer install --no-dev --optimize-autoloader
php artisan package:discover --ansi

echo "==> Linking public storage"
php artisan storage:link --force 2>/dev/null || true

echo "==> Rebuilding production caches"
php artisan config:cache
php artisan route:cache

echo "==> Done. Verify APP_DEBUG=false in .env and pcre.jit=0 in PHP-FPM."
