# Mally

Small cosmetics shop for Uzbekistan: Laravel 12 + Livewire 3 storefront and a
Filament 4 back office. No React, no separate SPA — pages are Livewire
components rendered by Blade. Project rules for contributors (and AI agents)
are in `CLAUDE.md`; code style in `guidelines.md`; translations in
`LANGUAGE_SETUP.md`.

## Setup

Requires PHP ^8.2, Composer, Node, MySQL (sessions, cache and queue live in
the database, so `migrate` must run before the app boots).

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan make:filament-user && php artisan admin:grant you@example.com
composer dev            # server + queue listener + logs + Vite
```

`composer dev` runs `scripts/dev.php`. On Windows it leaves out the Pail log
tail (needs the `pcntl` extension) and starts `artisan serve --no-reload`
(without it the server fails with "Failed to listen ... (reason: ?)"); after
editing `.env` restart it by hand.

## Routes

| Path | What |
|------|------|
| `/` `/catalog` `/products/{slug}` | Home, catalogue (filters, 18/36/72 per page), product page |
| `/cart` `/checkout` | Session cart; checkout needs a signed-in shopper |
| `/checkout/{orderNumber}/{orderHash?}` | Receipt: visible to the owner or with the secret hash |
| `/favorites` `/profile` `/returns` `/contact` | Favourites, orders + settings + reviews, static returns page, contact form |
| `/admin` | Filament panel, only for `users.is_admin` |
| `GET /api/exchange-rate`, `/api/categories`, `/api/products(/{slug})` | Read-only JSON for the Telegram bot (60/min) |
| `POST /api/orders` | Bot orders: `X-Api-Token: ORDERS_API_TOKEN`, 10/min, optional `Idempotency-Key` |
| `GET /api/orders/track?order=&hash=` | Order status, needs the order's secret hash (20/min) |
| `POST /api/telegram/webhook` | Login bot updates, needs `X-Telegram-Bot-Api-Secret-Token` |

## Key flows

**Money.** Prices and every stored order amount are USD (`selling_price`,
`orders.amount`, `order_product.price`). UZS is display-only, converted with
the CBU rate (`ExchangeRateService`, cached, refreshed hourly by
`exchange-rate:refresh`). Each order stores `exchange_rate` and `amount_uzs` as
a snapshot; correcting `amount` later recomputes `amount_uzs` at that stored
rate, never today's. Totals, discounts and prices are always computed on the
server from the DB; anything the browser or bot sends is ignored. The promo
code comes from `STORE_PROMO_CODE` / `STORE_PROMO_PERCENT`.

**Orders.** Checkout (`CheckoutPage`) and the bot API (`OrderController`) both
call `OrderService::placeOrder()`: one transaction, product and receipt rows
locked, max 50 units per line, order number = 1000 + id. A double submit of one
checkout page is ignored (locked per-page token); a bot retry with the same
`Idempotency-Key` returns the first order. Bot orders reuse only customers that
don't belong to a storefront account. Statuses today: `processing`,
`awaiting_delivery`, `delivered`; payment is manual (card transfer).

**Stock.** Stock lives in product receipts (`quantity_remaining`), sold FIFO by
`received_at`, `id`. `products.quantity` is only a cache. Order lines draw and
return stock through `OrderProductObserver` → `InventoryService`; deleting an
order returns its stock. Customers never see a stock count; a product that
can be added to the cart is available, and only sold-out products are
labelled "Sold out" (there is no "In stock" label). The
cart's + button and quantity changes are checked on the server against live
receipts (capped at 50), asking for more keeps the current quantity, and
availability is checked again at checkout and inside the order transaction.

**Accounts.** A shopper is a `users` row (`is_admin = false`) owning one
`customers` row. Sign-in: email or phone + password (email confirmed by a
code), or Telegram. Accounts are never merged; an existing phone/email blocks
registration. Failed logins are limited per account+IP and per IP.

**Telegram login** (`TelegramAuthService`): the site creates a one-time
request bound to the browser session and opens `t.me/<bot>?start=login_<token>`
→ the bot asks the user to share **their own** contact (a forwarded contact is
refused) → the bot sends a 6-digit code (hashed, 5 min, 5 attempts) → the user
types it on the site → login, registration, or linking to the signed-in
account. An account already linked to that Telegram signs in without a
password; a new one only gives a name (no password — it always signs in
through the bot). If the phone belongs to an account not yet linked to this
Telegram, its password is asked once and Telegram is linked.

The bot answers only while updates are fetched: either Telegram calls the
webhook, or the `telegram:auth-poll` worker long-polls (it runs in
`composer dev` and in the Docker supervisor, and idles when a webhook secret
is set). Without either, the bot replies only when an open login window polls
the site, which stalls as soon as the browser goes to the background.

**Reviews** need a delivered order of your own containing the product, one per
order and product, and wait for moderation in the admin.

## Environment

| Variable | Purpose |
|----------|---------|
| `TELEGRAM_AUTH_BOT_TOKEN`, `TELEGRAM_AUTH_BOT_USERNAME` | Customer login bot |
| `TELEGRAM_AUTH_WEBHOOK_SECRET` | Webhook secret; leave empty to use `php artisan telegram:auth-poll` |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_ADMIN_CHAT_IDS`, `TELEGRAM_ADMIN_USERNAME` | Admin bot (contact form messages, shop contact) |
| `ORDERS_API_TOKEN` | Shared secret of the ordering bot; empty = `POST /api/orders` closed |
| `STORE_CARD_NUMBER`, `STORE_CARD_HOLDER` | Payment details on the receipt |
| `STORE_PROMO_CODE`, `STORE_PROMO_PERCENT` | The single promo code |
| `MAIL_*` | SMTP for email codes |

Contact-form messages go through the queue: run a worker in production.
Never commit `.env`, local databases or diagnostic dumps.

## Telegram bot: manual check

Automated tests fake Telegram (`Http::fake()`); before release check with the
real bot:

1. Set the token/username. Either register the webhook
   (`setWebhook?url=<APP_URL>/api/telegram/webhook&secret_token=<secret>`) or,
   without a secret, make sure `php artisan telegram:auth-poll` is running.
2. On the site: "Continue with Telegram" → bot opens → share contact → code
   arrives → type it → you are signed in (a new account only asks for a name).
3. Negative cases: forward someone else's contact (refused), use the same link
   twice or after 10 min (refused), enter 5 wrong codes (request burned), open
   the link from a different browser session (code is useless there).
4. In the profile of an email account: "Confirm via Telegram" links Telegram
   and locks the phone.

## Tests and checks

```bash
composer test                                   # full suite (SQLite in memory)
php artisan test --filter=OrderPlacementTest    # one class
./vendor/bin/pint --test                        # code style
npm run build
```

Tests never hit the network and run on SQLite, where `lockForUpdate` is a
no-op: concurrency (two buyers for the last unit) is protected by row locks
on MySQL but is not exercised by the test suite.

## Known limitations

- No order cancellation status or payment confirmation flow; deleting an order
  is the only way to return its stock.
- Login/registration messages and some storefront texts are still Russian-only
  or come from `StorefrontService::t()` (see `LANGUAGE_SETUP.md`).
- An unverified phone typed at email registration counts as "taken" until its
  owner confirms it — the real owner of that number can't register with it.
- No SMS provider; phone numbers are verified only through Telegram.
