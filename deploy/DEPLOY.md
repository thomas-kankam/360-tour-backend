# Backend post-deploy (Ubuntu + Apache)

From the **360-tour-backend** repo root on the server:

```bash
chmod +x deploy/post-deploy.sh
bash deploy/post-deploy.sh
```

## What the script does

1. `git pull` (branch `main`)
2. `composer install --no-dev --optimize-autoloader`
3. Creates `storage/` and `bootstrap/cache` dirs, sets permissions
4. Clears config, cache, route, view caches
5. `php artisan migrate --force`
6. `php artisan storage:link --force`
7. Copies `deploy/storage.htaccess` → `public/storage/.htaccess` (1-year image cache)
8. `php artisan queue:restart` (if queue is configured)
9. `php artisan config:cache` + `route:cache`
10. `chown www-data` on `storage/` and `bootstrap/cache`
11. Warns if `APP_URL` is still localhost
12. `a2enmod rewrite deflate headers expires` (if available)
13. Optional Apache reload + `/up` health check

## Common overrides

```bash
APP_DIR=/var/www/html/naasei/projects/360-tour-backend \
RUN_MIGRATE=1 \
RELOAD_APACHE=1 \
bash deploy/post-deploy.sh
```

Skip pull or migrations:

```bash
SKIP_PULL=1 RUN_MIGRATE=0 bash deploy/post-deploy.sh
```

## Required `.env` on server

```env
APP_URL=https://api.360toursghana.com
APP_DEBUG=false
APP_ENV=production
```

After changing `.env`:

```bash
bash deploy/post-deploy.sh
```

## Apache vhost (once)

```apache
<VirtualHost *:443>
    ServerName api.360toursghana.com
    DocumentRoot /var/www/html/naasei/projects/360-tour-backend/public

    <Directory /var/www/html/naasei/projects/360-tour-backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Upload limits (if large images fail)

Copy `deploy/php/99-upload-limits.ini` to PHP-FPM conf.d, then reload php-fpm:

```bash
sudo cp deploy/php/99-upload-limits.ini /etc/php/8.4/fpm/conf.d/99-360tours-upload-limits.ini
sudo systemctl reload php8.4-fpm
```

## Verify storage

```bash
ls -la public/storage/uploads/images/
curl -I https://api.360toursghana.com/storage/uploads/images/YOUR-FILE.webp
```
