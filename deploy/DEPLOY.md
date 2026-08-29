# Backend post-deploy (Ubuntu + Apache)

**One command on the server** (everything else is inside the script):

```bash
cd /var/www/html/naasei/projects/360-tour-backend
RELOAD_APACHE=1 bash deploy/post-deploy.sh
```

## Commands the script runs for you

| Step | Command |
|------|---------|
| 1 | `git pull origin main` |
| 2 | **`composer install --no-dev --optimize-autoloader`** |
| 3 | Create `storage/` + `bootstrap/cache` dirs, `chmod` |
| 4 | **`php artisan config:clear`** |
| 5 | **`php artisan cache:clear`** |
| 6 | **`php artisan route:clear`** |
| 7 | **`php artisan view:clear`** |
| 8 | **`php artisan migrate --force`** (skip with `RUN_MIGRATE=0`) |
| 9 | **`php artisan storage:link --force`** |
| 10 | **`cp deploy/storage.htaccess → public/storage/.htaccess`** |
| 11 | **`php artisan queue:restart`** (if queue configured) |
| 12 | **`php artisan optimize`** (or `config:cache` + `route:cache` fallback) |
| 13 | `chown www-data` on `storage/` + `bootstrap/cache` |
| 14 | Warn if `APP_URL` is localhost |
| 15 | `a2enmod rewrite deflate headers expires` |
| 16 | `systemctl reload apache2` (when `RELOAD_APACHE=1`) |
| 17 | Health check `GET {APP_URL}/up` |

You do **not** need to run artisan or composer commands separately after a normal deploy.

## Overrides

```bash
SKIP_PULL=1 RUN_MIGRATE=0 bash deploy/post-deploy.sh
```

```bash
APP_DIR=/var/www/html/naasei/projects/360-tour-backend \
RELOAD_APACHE=1 \
bash deploy/post-deploy.sh
```

## Required `.env`

```env
APP_URL=https://api.360toursghana.com
APP_ENV=production
APP_DEBUG=false
```

## After deploy, verify

```bash
curl -I https://api.360toursghana.com/up
curl -I https://api.360toursghana.com/api/sitemap.xml
ls -la public/storage/uploads/images/
```

## Upload limits (one-time, if large uploads fail)

```bash
sudo cp deploy/php/99-upload-limits.ini /etc/php/8.4/fpm/conf.d/99-360tours-upload-limits.ini
sudo systemctl reload php8.4-fpm
```
