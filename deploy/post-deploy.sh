#!/usr/bin/env bash
#
# Post-deploy for 360 Tours Laravel API (Ubuntu + Apache)
#
# Runs automatically (no extra commands needed after push):
#   git pull
#   composer install --no-dev --optimize-autoloader
#   php artisan config:clear / cache:clear / route:clear / view:clear
#   php artisan migrate --force
#   php artisan storage:link --force
#   cp deploy/storage.htaccess → public/storage/.htaccess
#   php artisan queue:restart
#   php artisan optimize  (config + route + event cache)
#   chown www-data storage bootstrap/cache
#   a2enmod rewrite deflate headers expires (+ optional apache reload)
#
# Usage (on the server, from repo root or deploy folder):
#   chmod +x deploy/post-deploy.sh
#   bash deploy/post-deploy.sh
#
# Or:
#   APP_DIR=/var/www/html/naasei/projects/360-tour-backend \
#   SKIP_PULL=0 RUN_MIGRATE=1 RELOAD_APACHE=1 \
#   bash deploy/post-deploy.sh
#
# Apache API vhost DocumentRoot should point to: ${APP_DIR}/public
# Enable: sudo a2enmod rewrite deflate headers expires

set -euo pipefail

APP_DIR="${APP_DIR:-${1:-$(cd "$(dirname "$0")/.." && pwd)}}"
cd "${APP_DIR}"

BRANCH="${BRANCH:-main}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
SKIP_PULL="${SKIP_PULL:-0}"
RUN_MIGRATE="${RUN_MIGRATE:-1}"
RELOAD_APACHE="${RELOAD_APACHE:-0}"
PHP_CMD="${PHP_CMD:-php}"
COMPOSER_CMD="${COMPOSER_CMD:-composer}"

log()  { echo "[post-deploy] $*"; }
warn() { echo "[post-deploy] WARN: $*" >&2; }
fail() { echo "[post-deploy] ERROR: $*" >&2; exit 1; }

command -v "${PHP_CMD}" >/dev/null 2>&1 || fail "${PHP_CMD} not found"
command -v "${COMPOSER_CMD}" >/dev/null 2>&1 || fail "${COMPOSER_CMD} not found"
[[ -f "${APP_DIR}/artisan" ]] || fail "Not a Laravel app: ${APP_DIR}/artisan missing"
[[ -f "${APP_DIR}/.env" ]] || warn ".env missing — copy from .env.example and configure APP_URL"

log "App directory: ${APP_DIR}"

# ── Pull latest code ─────────────────────────────────────────────────────────
if [[ "${SKIP_PULL}" != "1" ]] && [[ -d "${APP_DIR}/.git" ]]; then
  log "Fetching ${GIT_REMOTE}/${BRANCH}..."
  git fetch "${GIT_REMOTE}" "${BRANCH}"
  git checkout "${BRANCH}"
  git pull "${GIT_REMOTE}" "${BRANCH}"
else
  log "Skipping git pull"
fi

# ── PHP upload limits (optional copy hint) ───────────────────────────────────
if [[ -f "${APP_DIR}/deploy/php/99-upload-limits.ini" ]]; then
  log "Upload limits reference: deploy/php/99-upload-limits.ini"
  log "  Copy to /etc/php/*/fpm/conf.d/ and reload php-fpm if uploads fail"
fi

# ── Composer ─────────────────────────────────────────────────────────────────
log "Installing PHP dependencies (production)..."
"${COMPOSER_CMD}" install --no-dev --optimize-autoloader --no-interaction

# ── Storage & cache directories ──────────────────────────────────────────────
log "Ensuring storage and bootstrap/cache directories..."
mkdir -p storage/app/public/uploads/images
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

# ── Clear stale caches (before migrate / link) ───────────────────────────────
log "Clearing stale caches..."
"${PHP_CMD}" artisan config:clear
"${PHP_CMD}" artisan cache:clear
"${PHP_CMD}" artisan route:clear
"${PHP_CMD}" artisan view:clear

# ── Database migrations ──────────────────────────────────────────────────────
if [[ "${RUN_MIGRATE}" == "1" ]]; then
  log "Running migrations..."
  "${PHP_CMD}" artisan migrate --force --no-interaction
else
  log "Skipping migrations (RUN_MIGRATE=0)"
fi

# ── Storage symlink + upload cache headers ───────────────────────────────────
log "Linking public/storage → storage/app/public..."
"${PHP_CMD}" artisan storage:link --force

if [[ -L "${APP_DIR}/public/storage" ]] || [[ -d "${APP_DIR}/public/storage" ]]; then
  if [[ -f "${APP_DIR}/deploy/storage.htaccess" ]]; then
    log "Installing storage/.htaccess (1-year image cache)..."
    cp "${APP_DIR}/deploy/storage.htaccess" "${APP_DIR}/public/storage/.htaccess"
  fi
else
  warn "public/storage not found after storage:link"
fi

# ── Queue workers (database driver) ──────────────────────────────────────────
if "${PHP_CMD}" artisan list 2>/dev/null | grep -q "queue:restart"; then
  log "Signaling queue workers to restart..."
  "${PHP_CMD}" artisan queue:restart 2>/dev/null || true
fi

# ── Rebuild production caches ────────────────────────────────────────────────
log "Optimizing Laravel (config + route + event cache)..."
if "${PHP_CMD}" artisan optimize --no-interaction 2>/dev/null; then
  log "php artisan optimize — OK"
else
  warn "optimize unavailable — falling back to config:cache + route:cache"
  "${PHP_CMD}" artisan package:discover --ansi
  "${PHP_CMD}" artisan config:cache
  "${PHP_CMD}" artisan route:cache
fi

# ── Permissions for Apache ───────────────────────────────────────────────────
if id www-data >/dev/null 2>&1; then
  log "Setting ownership for Apache (www-data) on storage & bootstrap/cache..."
  sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || \
    warn "Could not chown — run: sudo chown -R www-data:www-data storage bootstrap/cache"
fi

# ── .env sanity checks ───────────────────────────────────────────────────────
APP_URL_VAL=""
if [[ -f "${APP_DIR}/.env" ]]; then
  APP_URL_VAL="$(grep -E '^APP_URL=' .env | cut -d= -f2- | tr -d '"' | tr -d "'" || true)"
  if [[ "${APP_URL_VAL}" == *"127.0.0.1"* ]] || [[ "${APP_URL_VAL}" == *"localhost"* ]]; then
    warn "APP_URL looks local (${APP_URL_VAL}) — image URLs will break in production"
    warn "Set APP_URL=https://api.360toursghana.com then re-run this script"
  else
    log "APP_URL=${APP_URL_VAL:-<not set>}"
  fi

  if grep -q '^APP_DEBUG=true' .env 2>/dev/null; then
    warn "APP_DEBUG=true in production is not recommended"
  fi
fi

# ── Apache modules + reload ──────────────────────────────────────────────────
if command -v a2enmod >/dev/null 2>&1; then
  log "Ensuring Apache modules (rewrite, deflate, headers, expires)..."
  sudo a2enmod rewrite deflate headers expires 2>/dev/null || true
fi

if [[ "${RELOAD_APACHE}" == "1" ]]; then
  log "Reloading Apache..."
  sudo systemctl reload apache2
else
  log "Skipping Apache reload (set RELOAD_APACHE=1 to reload)"
fi

# ── Health check ─────────────────────────────────────────────────────────────
if command -v curl >/dev/null 2>&1 && [[ -n "${APP_URL_VAL:-}" ]]; then
  HEALTH_URL="${APP_URL_VAL%/}/up"
  log "Health check: ${HEALTH_URL}"
  curl -fsS "${HEALTH_URL}" >/dev/null && log "Health check OK" || warn "Health check failed — is the vhost up?"
fi

log ""
log "Done."
log "  DocumentRoot: ${APP_DIR}/public"
log "  Storage link: ${APP_DIR}/public/storage"
log "  Sitemap:      ${APP_URL_VAL:-https://api.360toursghana.com}/api/sitemap.xml"
log ""
log "Verify uploaded images:"
log "  ls -la ${APP_DIR}/public/storage/uploads/images/"
