# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Mally is a Laravel 12 + Filament 4 admin/back-office app. The public site is just the default Laravel welcome page; all real functionality lives in the Filament admin panel at `/admin`. Domain is small-scale e-commerce: Customers, Brands, Categories, Products, Orders (with `OrderProduct` pivot).

Requires **PHP ^8.2**. The repo uses MySQL by default (`.env.example` → `DB_DATABASE=mally`), with sessions / cache / queues all backed by the database (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`).

## Commands

```bash
# Full dev loop (server + queue listener + log tail + Vite, concurrently)
  composer dev

# Tests — clears config first, then runs PHPUnit
composer test
php artisan test --filter=SomeTest          # single test class
php artisan test tests/Feature/ExampleTest.php::test_method  # single method

# Code style (Pint, configured via Laravel defaults)
./vendor/bin/pint

# Create an admin user for the Filament panel (required to log in)
php artisan make:filament-user

# After changing locale, translation files, or Filament config:
php artisan optimize:clear
```

`composer dev` is the canonical entry point — it runs `php artisan serve`, `queue:listen`, `pail` (live log tail), and `npm run dev` together. Don't run `php artisan serve` standalone unless you know you don't need the others.

## Architecture

### Filament panel is the application

`app/Providers/Filament/AdminPanelProvider.php` is the central wiring point. It:
- Mounts the panel at `/admin` with login enabled.
- Auto-discovers resources from `app/Filament/Resources` and widgets from `app/Filament/Widgets`.
- Explicitly registers the four dashboard widgets (`SalesOverviewWidget`, `MonthlyRevenueChart`, `RecentOrdersWidget`, `LowStockWidget`) — adding a new dashboard widget requires editing this provider, not just dropping a file.

There are **no controllers and no API routes** for the admin functionality. `routes/web.php` only renders `welcome`. CRUD, validation, and presentation all live inside Filament Resource classes.

### Resource layout convention

Each domain model has a Filament resource under `app/Filament/Resources/<Plural>/`. The pattern (see `Products/` as the reference) splits a resource across four sibling folders:

```
Products/
  ProductResource.php          # registration, navigation label, page map
  Pages/                       # ListX, CreateX, EditX, ViewX
  Schemas/                     # ProductForm, ProductInfolist (form + view layouts)
  Tables/                      # ProductsTable (columns, filters, actions)
```

`ProductResource` is thin — it just wires `Pages`, `Schemas`, and `Tables` together. Changes to form fields go in `Schemas/`; changes to table columns/filters go in `Tables/`. When adding a new resource, mirror this folder shape rather than putting everything in the resource class.

### i18n is load-bearing — translate every label

The app ships English / Russian / Uzbek, with **Russian as the default locale**. See `LANGUAGE_SETUP.md` for the full story. The rule when writing any user-visible string:

1. Wrap it in `__('Key')`.
2. Add the key to **all three** of `lang/en.json`, `lang/ru.json`, `lang/uz.json` — missing a file means that locale falls back to the raw key.
3. Filament's own UI strings come from published vendor files under `lang/vendor/filament/{en,ru,uz}/`; don't edit those for your own labels.

This applies to navigation labels, model labels, column/field labels, widget headings, and enum/status display values. See `ProductResource::getNavigationLabel()` for the standard pattern.

Switch locale via `APP_LOCALE=en|ru|uz` in `.env`, then `php artisan optimize:clear`.

### Frontend build

Vite + Tailwind 4 exist only to compile the assets Filament needs (`resources/css/app.css`, `resources/js/app.js`). There is no SPA, no React/Vue, no custom Blade UI beyond `welcome.blade.php`. `npm run dev` / `npm run build` are the only meaningful npm scripts.

## Conventions from `guidelines.md`

The repo has a `guidelines.md` capturing house style. The non-obvious / project-specific points:

- **Fat models, skinny controllers** — but in practice this app has no controllers; business logic belongs on the model or a Service class, not in the Filament Resource.
- **Move validation out of controllers into Request classes** when controllers do appear.
- **Prefer `chunk()` over `get()`** for data-heavy operations.
- **Eager-load with `with()`** to avoid N+1, especially in Filament tables that render related fields.

## Gotchas

- `composer install` will fail on PHP < 8.2 — check `php -v` before debugging weird Composer errors.
- After editing `config/app.php`, translation JSON, or any Filament resource registration, run `php artisan optimize:clear` — stale caches will silently serve old labels.
- Sessions/cache/queue all live in MySQL, so `migrate` must run before the app boots. The `post-create-project-cmd` script touches a SQLite file, but real config points at MySQL — don't trust it.
- New dashboard widget = also register it in `AdminPanelProvider::panel()->widgets([...])`, not just discovery.
