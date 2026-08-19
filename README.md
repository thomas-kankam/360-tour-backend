# 360 Tours Ghana API

Laravel 12 API backend for [360 Tours Ghana](https://360toursghana.com). Requires **PHP 8.4+** (including PHP 8.5).

## Actors

- **Clients** — register, book tours, pay online
- **Admins** — manage listings, bookings, payments, clients, contacts, roles, and other admins

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan passport:install
```

Configure Paystack, mail, and SMS in `.env`. API base URL defaults to `/api`.

## Server deploy

```bash
composer install --no-dev --optimize-autoloader
bash deploy/post-deploy.sh
php artisan migrate --force
```

Production `.env` must have `APP_DEBUG=false`. Dev tools like Ignition are not installed with `--no-dev`; debug mode on production causes broken error pages.

PHP on hardened VPS hosts may block PCRE JIT. Include in PHP-FPM config (`deploy/php/99-upload-limits.ini`):

```ini
pcre.jit=0
```

Then reload PHP-FPM. If errors persist, run `php artisan config:clear` before `config:cache`.

Ensure cron runs `php artisan schedule:run` every minute for the queue worker.

### Large tour payloads (images)

Tour create/update accepts image URLs. Prefer uploading files first:

```http
POST /api/admin/uploads/images
Content-Type: multipart/form-data

image: (file, max 10MB)
```

Response includes `data.url` — use that for `coverImageUrl` / `galleryImageUrls` instead of base64 in JSON.

If you still embed base64 in one JSON body, raise PHP and web server limits (recommended **64M**):

```ini
; php.ini
post_max_size = 64M
upload_max_filesize = 64M
```

```nginx
client_max_body_size 64M;
```

Reload PHP-FPM and nginx after changes.

## Main route groups

| Prefix | Guard | Purpose |
|--------|--------|---------|
| `/api/client/*` | `client-api` | Client auth, bookings, payments |
| `/api/admin/*` | `admin-api` | Admin auth and management |
| `/api/listings*` | public | Browse published tours |
| `/api/payment/*` | public | Paystack callback / webhook |
