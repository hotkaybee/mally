# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Mally is a Laravel 12 + Filament 4 + Livewire 3 small-scale e-commerce app (cosmetics shop, Uzbekistan). Two halves:

- **Admin / back-office** — Filament panel at `/admin`: Customers, Brands, Categories, Products, Product receipts (stock batches), Orders (with `OrderProduct` lines), Reviews (moderation of storefront reviews).
- **Storefront** — public Livewire pages in `app/Livewire/Storefront` (catalog, product, cart, checkout, receipt, profile, contact), routed in `routes/web.php`.

`routes/api.php` holds a small JSON API used by the Telegram bot (`OrderController`, `ProductController`, `CategoryController`).

Requires **PHP ^8.2**. MySQL by default (`.env.example` → `DB_DATABASE=mally`), with sessions / cache / queues all backed by the database.

## Commands

```bash
# Full dev loop (server + queue listener + log tail + Vite, concurrently)
composer dev

# Tests — clears config first, then runs PHPUnit
composer test
php artisan test --filter=SomeTest          # single test class
php artisan test tests/Feature/ExampleTest.php::test_method  # single method

# Code style (Pint, Laravel defaults) — run before every push
./vendor/bin/pint

# Create an admin user for the Filament panel (required to log in)
php artisan make:filament-user

# After changing locale, translation files, or Filament config:
php artisan optimize:clear
```

## Architecture

### Admin panel

`app/Providers/Filament/AdminPanelProvider.php` mounts the panel at `/admin`, auto-discovers resources and widgets, and **explicitly registers dashboard widgets** — a new widget must be added to `->widgets([...])` there.

Each model's resource lives in `app/Filament/Resources/<Plural>/` split into `Pages/`, `Schemas/` (form + infolist), `Tables/`; the `*Resource.php` class stays thin. Mirror `Products/` when adding a resource.

### Storefront

- Livewire page components + Blade views in `resources/views/livewire/storefront` and `resources/views/components/storefront`.
- Shared session state (cart, favorites, locale, currency, logged-in customer) goes through `App\Services\StorefrontService`; components use it via the `InteractsWithStore` trait.
- Exchange rate (USD→UZS, from cbu.uz) comes from `App\Services\ExchangeRateService` and is cached.

### Stock

Stock lives in **product receipts** (`product_receipts.quantity_remaining`). `products.quantity` is only a cached total recalculated by `InventoryService::syncProductCaches()`. Creating/updating/deleting an `OrderProduct` consumes/releases receipt stock through `OrderProductObserver` → `InventoryService`. See `tests/Feature/InventoryTest.php`.

### i18n

English / Russian / Uzbek, **Russian is the default**. Every user-visible string: `__('Key')` + the key in **all three** of `lang/en.json`, `lang/ru.json`, `lang/uz.json`. Filament's own strings come from `lang/vendor/filament/*` — don't put app labels there. Don't introduce new parallel translation mechanisms (PHP arrays, `match ($locale)`, `$texts` in Blade). See `LANGUAGE_SETUP.md`.

House style is in `guidelines.md`: business logic in models/services (not in Filament resources or Livewire components), eager-load relations used by tables.

## Rules — do NOT

**Money**
- Prices and all stored order amounts are **USD** (admin uses `->money('USD')`). UZS is display-only, converted from the cached rate. Never store UZS in a USD column, never guess the currency from the size of a number, never recompute historical orders at today's rate.
- Totals, prices and discounts are computed on the server from the DB. Ignore any price/total sent by the client.

**Stock**
- Never decrement `products.quantity` by hand; stock is taken from receipts via `OrderProductObserver`/`InventoryService`.
- Never show customers the exact quantity — only "In stock" / "Sold out". Re-check availability on the server at add-to-cart, quantity change and order placement.

**Storefront / orders**
- The storefront only reads the catalog. Never create `Product`, `Brand` or `Category` from checkout or any customer action.
- Order number = `id + Order::NUMBER_OFFSET`, calculated, not stored. Never generate numbers with `max + 1`.
- Don't store values that can be calculated from other columns (order number, so'm total = `amount × exchange_rate`, paid = `amount_paid >= amount`). Calculate them in the model.
- Don't write addresses or anything else into `customers.instagram`.
- Don't let anyone see an order they don't own (no lookups by bare order number or phone).

**Security**
- Every `public` Livewire property and method is client-controllable. Mark server-owned state `#[Locked]` and re-verify on the server before acting.
- No hardcoded / universal / "dev" login codes. OTP codes: random, hashed server-side, expiring, rate-limited, single-use, never rendered or logged outside `local`.
- Registration with an existing phone or email is rejected. Never merge accounts or auto-link Telegram/email to an existing account.
- Never use `withoutVerifying()` / `verify => false`. Tests use `Http::fake()`.
- Never show `$e->getMessage()` to customers — show a generic message and `report($e)`.
- No custom `server.php` in the project root (it can serve `.env`).
- Public write endpoints need validation and rate limiting.

**Scope / config**
- Don't bring back React or add new UI frameworks, API layers or admin sections beyond the ticket.
- **Ownership.** Admin / back-end is owned by @hotkaybee: `app/Filament/**`, `app/Providers/Filament/**`, `database/seeders/**`, migrations of existing core tables (customers, products, product_receipts, orders, order_product, brands, categories), `InventoryService`, observers. Storefront tickets don't change these. If a storefront change needs one, ask first or say so in the PR description.
- Don't change shared settings (`composer dev` script, `vite.config.js`, `config/app.php` timezone — stays UTC, convert to `Asia/Tashkent` only when displaying) without team agreement.
- Don't commit secrets, `.env`, local DBs or diagnostic dumps.

**Tests**
- Tests never hit the network (`Http::fake()`), and call `$this->withoutVite()` in `setUp()`.
- Don't claim a fix works until a test for it passes.

**Git**
- Branch from current `main`, one ticket per PR, PR title `MAL-<n>: <ticket title>`. Small fixes without a ticket: branch `hotfix/<short-name>`, PR title `fix: <what>`.
- While a PR is not merged into `main`, change its own migrations directly instead of adding new ones; add a new migration only for tables/migrations that are already on `main`.
- No `git reset --hard`, force push, history rewrites, branch deletion or PR merges without explicit permission. Don't run destructive migrations or wipe data — except `migrate:fresh` on your own local database.

## Gotchas

- `composer install` fails on PHP < 8.2.
- After editing `config/*`, translation JSON or Filament resource registration, run `php artisan optimize:clear` — stale caches silently serve old values.
- Sessions/cache/queue live in MySQL, so `migrate` must run before the app boots.
- Product photos (`products.images`, a list of paths, first = main) live on the `public` disk under `products/`. Build links with `Product::imageUrls()` / `mainImageUrl()`, never `asset()`. Run `php artisan storage:link` once per machine (the Docker entrypoint does it on the server), and `APP_URL` must match the host you open, or the photos 404.
