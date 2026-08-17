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
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Ensure cron runs `php artisan schedule:run` every minute for the queue worker.

## Main route groups

| Prefix | Guard | Purpose |
|--------|--------|---------|
| `/api/client/*` | `client-api` | Client auth, bookings, payments |
| `/api/admin/*` | `admin-api` | Admin auth and management |
| `/api/listings*` | public | Browse published tours |
| `/api/payment/*` | public | Paystack callback / webhook |
