# eCommerce Store (Laravel 13)

An online store built with **Laravel 13**, **React + TypeScript** and **Inertia**. The English-only UI. Storefront and admin panel.

## Stack

- Laravel 13, PHP 8.3+
- React 19 + TypeScript + Inertia 3 (Laravel React starter kit), Tailwind CSS 4, shadcn/ui
- Authentication: the starter kit's **Laravel Fortify** (login, registration, email verification, password reset,
  two-factor, passkeys). 
- MySQL 8 via Docker Compose, Pest for tests

## Getting started

```bash
cp .env.example .env            # already contains working local defaults
docker compose up -d            # MySQL on localhost:3307
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev                     # or `npm run build`
php artisan serve
```

The database port is `3307` (see `DB_PORT` in `.env`) so it does not clash with a MySQL already using 3306.

Seeded accounts (password `password`):

| Email                  | Role     |
|------------------------|----------|
| `admin@example.com`    | Admin    |
| `customer@example.com` | Customer |

The admin panel is at `/admin` (admins and moderators only). `php artisan migrate:fresh --seed` resets the demo data.

## What is included

**Storefront:** home page, popular products, category tree pages with price/attribute filters and sorting,
product pages (gallery, selectable variants with price modifiers, discounts, reviews, favorites), search with
autocomplete, cart with promo codes, product comparison, checkout (delivery/payment methods, stock reservation),
static pages, customer account (overview, orders, favorites, profile/security settings), maintenance mode.

**Admin:** dashboard with sales chart, orders (kanban board + table, status history, tracking numbers), products
(images in three sizes, discounts, variants, stock), categories, attributes, stock receipts/write-offs,
bulk price changes with undo, customers (bonus points, blocking, audit log), reviews moderation, promo codes,
static pages, currencies, delivery & payment methods, store settings and SEO templates.

## Tests

```bash
php artisan test
npm run types:check
```
