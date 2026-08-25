# Fintech Wallet & Expense Tracker — Technical Documentation

A Laravel 12 / PHP 8.2 REST API backend for a personal finance app (wallets, transactions, transfers, budgeting categories, dashboard analytics, notifications), built as a learning project to pair with a Flutter Clean Architecture frontend.

This document is the technical reference for the backend: architecture, data model, business rules, and the full API surface.

---

## 1. Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.2 |
| Database | MySQL |
| Auth | Laravel Sanctum (access tokens) + a custom `refresh_tokens` table |
| API style | REST, versioned under `/api/v1`, JSON envelope `{data, message, meta?, links?}` |
| Localization | `Accept-Language` header (`en`/`ar`) |
| Testing | PHPUnit, Feature tests against a dedicated MySQL test database |

---

## 2. Architecture

Strict layering, enforced by convention across every feature:

```
Controller → Action → Service → Repository → Model
```

- **Controller** — resolves the request, calls one Action, wraps the result in a Resource + `ApiResponse`. No business logic ever lives here.
- **Action** — a single-purpose, invokable class (`__invoke`) per use case (e.g. `CreateTransactionAction`). Thin orchestration; usually just forwards to a Service.
- **Service** — where business rules live (balance math, default-wallet switching, OTP generation, notification triggering). Services can depend on other Services for cross-cutting concerns (e.g. `TransactionService` calls `NotificationService` after a successful create).
- **Repository** — the only place that talks to Eloquent for a given model's persistence/queries. Keeps query logic out of Services.
- **Model** — Eloquent models with enum casts, relationships, and (for a few) a `$attributes` default so a freshly-`create()`d instance already reflects its DB-level default in memory.

```
app/
├── Http/
│   ├── Controllers/Api/V1/{Auth,Wallet,Transaction,Transfer,Category,Dashboard,Notification}/
│   ├── Requests/Api/V1/{Auth,Wallet,Transaction,Transfer,Category,Dashboard,Notification}/
│   ├── Resources/
│   └── Middleware/SetLocaleFromHeader.php
├── Models/
├── Services/{Auth,Wallet,Transaction,Transfer,Category,Dashboard,Notification}/
├── Repositories/
├── Actions/{Auth,Wallet,Transaction,Transfer,Category,Dashboard,Notification}/
├── Enums/
├── Exceptions/{Api,Auth,Wallet,Transaction}/
└── Policies/
```

### Cross-cutting pieces

- **`App\Support\ApiResponse`** — builds the `{data, message, meta, links}` envelope for every response. Resolves a `JsonResource`'s payload via `->resolve()` (not by round-tripping through `response()->getData(true)['data']`, which breaks if the resource itself has a field named `data`).
- **`App\Http\Requests\Api\V1\ApiFormRequest`** — base Form Request; overrides `failedValidation()` to return the same JSON envelope (422, `errors` keyed by field) instead of Laravel's default shape.
- **`App\Exceptions\Api\ApiException`** — base for all domain exceptions. Implements `ShouldntReport`, so expected 4xx business failures (insufficient balance, invalid OTP, wrong credentials, …) never spam the error log — only genuine 5xx failures do.
- **`bootstrap/app.php`** — registers `api.php` routing, appends `SetLocaleFromHeader` to the `api` middleware group, calls `redirectGuestsTo(fn () => null)` (this is a pure API — an unauthenticated hit on a protected route must return 401 JSON, never attempt a redirect to a non-existent `login` route), and renders `ValidationException`/`AuthenticationException`/`NotFoundHttpException`/generic `Throwable` consistently as the same JSON envelope for any `api/*` request.

---

## 3. Data Model

All tables use `id` bigint PKs and `created_at`/`updated_at` timestamps unless noted. Soft-deleted tables are marked **(soft delete)**.

### users **(soft delete)**
| Column | Type | Notes |
|---|---|---|
| name, email, phone | string | `email`, `phone` unique |
| password | string | hashed cast |
| status | string | `UserStatus` enum |
| email_verified_at, phone_verified_at | timestamp, nullable | |
| remember_token | string | |

### user_profiles
1:1 with `users`. `first_name`, `last_name`, `avatar`, `date_of_birth`, `gender` (`Gender` enum), `address`, `city`, `country`.

### wallet_types
Lookup table. `name`, `code` (`WalletTypeCode` enum: `cash`/`bank`/`savings`/`credit`), `description`, `status` (`Status` enum). Seeded once via `WalletTypeSeeder`.

### wallets **(soft delete)**
| Column | Notes |
|---|---|
| user_id, wallet_type_id | FKs |
| name, currency (3-char), balance (decimal 15,2) | |
| status | `WalletStatus` enum |
| is_default | boolean — exactly one `true` per user, enforced in `WalletService` |

### categories
Self-referencing (`parent_id`) for subcategories. `name`, `type` (`TransactionType` enum — shared with transactions), `icon`, `color`, `status` (`Status` enum). Seeded via `CategorySeeder` (16 parents / 6 children).

### transactions **(soft delete)**
| Column | Notes |
|---|---|
| user_id, wallet_id | FKs |
| category_id | nullable FK |
| transfer_id | nullable FK to `transfers` — set only on the two mirror rows a transfer creates |
| type | `TransactionType` enum |
| amount, balance_before, balance_after | decimal(15,2) — the auditability trail |
| note, transaction_date | |
| status | `TransactionStatus` enum |

Indexes: `(wallet_id, transaction_date)`, `(user_id, type)`.

### transfers
`user_id`, `from_wallet_id`, `to_wallet_id`, `amount`, `note`, `status` (`TransactionStatus` enum, shared with transactions). Immutable via the API — no update/delete endpoint.

### notifications
`user_id`, `type` (`NotificationType` enum), `title`, `body`, `data` (json), `read_at` (nullable). This is a **custom** table, unrelated to Laravel's built-in database-notifications channel.

### devices
`user_id`, `fcm_token` (unique), `platform` (`DevicePlatform` enum). Scaffolded for future push-notification wiring; not yet consumed by any endpoint.

### refresh_tokens
`user_id`, `token` (SHA-256 hash, unique, indexed), `expires_at`, `revoked`. See §5.

### otps
`user_id`, `purpose` (`OtpPurpose` enum), `code` (bcrypt-hashed), `expires_at`, `consumed_at`. Not in the original spec — added because the `forgot-password`/`verify-otp` endpoints need somewhere to persist codes.

### personal_access_tokens
Standard Sanctum table (access tokens). Has an `expires_at` column, which is how per-token TTLs (§5) are enforced.

---

## 4. Enum Reference

| Enum | Values | Used by |
|---|---|---|
| `UserStatus` | active, inactive, blocked, pending | users.status |
| `WalletStatus` | active, inactive | wallets.status |
| `WalletTypeCode` | cash, bank, savings, credit (+ `allowsNegativeBalance()`, true only for `credit`) | wallet_types.code |
| `TransactionType` | income, expense | transactions.type **and** categories.type |
| `TransactionStatus` | completed, pending, failed | transactions.status **and** transfers.status |
| `NotificationType` | transaction, low_balance, payment_success, payment_failed | notifications.type (`payment_*` reserved for a future payment-gateway integration — not fired by anything yet) |
| `OtpPurpose` | registration, password_reset | otps.purpose |
| `Gender` | male, female, other | user_profiles.gender |
| `DevicePlatform` | ios, android, web | devices.platform |
| `Status` | active, inactive | generic — wallet_types.status, categories.status |

---

## 5. Authentication & Authorization

### Token model
Two tokens are issued together on login/register-verify/refresh:

- **Access token** — a Sanctum personal access token, `Bearer`-authenticated, **60 minute** TTL (enforced via Sanctum's own `expires_at` column — no manual revocation needed for expiry).
- **Refresh token** — a 64-char random string, **30 day** TTL, stored **SHA-256 hashed** (not bcrypt — it's a high-entropy random value, not a user-chosen secret, so a fast deterministic hash gives an indexed O(1) lookup, the same approach Sanctum itself uses for its own tokens).

`POST /auth/refresh` **rotates**: the old refresh token is revoked and a brand-new access+refresh pair is issued. Reusing a spent refresh token returns 401.

### OTP flow
- 6-digit numeric code, bcrypt-hashed, **10 minute** TTL.
- Generating a new OTP invalidates any pending one for the same `(user, purpose)` pair first.
- **Registration**: account starts `pending`; `verify-otp` with `purpose=registration` activates it (`status → active`) and stamps `email_verified_at`/`phone_verified_at`.
- **Password reset**: `forgot-password` issues an OTP; `verify-otp` with `purpose=password_reset` requires `new_password`/`new_password_confirmation` in the same call and updates the password directly (no separate reset-password endpoint).
- **Development convenience**: register/forgot-password responses include the plain code in `meta.debug_otp_code` **only** when `APP_ENV` is `local` or `testing` — there's no real SMS/mail provider wired up, and this is what both the Postman collection and the automated tests read instead of scraping logs.

### Authorization
Ownership is enforced via Laravel Policies (auto-discovered by naming convention, no manual registration needed on Laravel 11+): `WalletPolicy`, `TransactionPolicy`, `TransferPolicy` (view-only — transfers have no update/delete), `NotificationPolicy` (update-only, for marking read). Each just checks `$model->user_id === $user->id`. Cross-user access returns 403; cross-user *references* in a request body (e.g. someone else's `wallet_id`) are rejected at validation time via a scoped `Rule::exists(...)->where('user_id', ...)`, so they read as "doesn't exist" rather than leaking that the resource belongs to someone else.

### Rate limiting
`login`, `forgot-password`, and `verify-otp` are each throttled to 6 attempts/minute — with **distinct** limiter keys (`throttle:6,1,login` etc.). Laravel's default `throttle:max,decay` middleware keys solely by IP when no third argument is given, so without the distinct suffixes all three routes would share one bucket and a user rate-limited out of login attempts would also be locked out of requesting a password-reset OTP.

---

## 6. Business Logic Deep Dives

### 6.1 Transaction balance math (`TransactionService`)
All arithmetic uses `bcadd`/`bcsub`/`bcmul`/`bccomp` on string amounts — never float — to avoid floating-point drift on money.

**Create**: locks the wallet row (`SELECT ... FOR UPDATE`, inside `DB::transaction`), reads `balance_before`, computes `balance_after` (income adds, expense subtracts), guards against a negative result (§6.2), writes the transaction with both snapshots, then updates the wallet's live balance.

**Update**: the tricky one. If `amount`/`type`/`wallet_id` changes:
- *Same wallet*: reverse the old transaction's signed delta from the wallet's **current** balance, then reapply the new delta. Only the *final* result is guarded — an intermediate dip is fine since it's never persisted.
- *Different wallet*: locks **both** wallets (`lockManyByIds`, ascending order to avoid deadlocks), reverses the old effect on the old wallet, applies the new effect on the new wallet, guards both independently.

**Delete**: reverses the transaction's effect on its wallet's current balance; also guarded (removing an income that's since been partly spent could otherwise drive the balance negative).

A transaction with `transfer_id !== null` (one of a transfer's two mirror rows) can't be updated or deleted through these endpoints at all — `TransferLinkedTransactionException` (422) — because editing one side of a transfer without the other would corrupt the ledger.

### 6.2 The negative-balance guard
```php
if (bccomp($balanceAfter, '0', 2) < 0 && ! $wallet->walletType->code->allowsNegativeBalance()) {
    throw new InsufficientBalanceException();
}
```
Only `credit`-type wallets are allowed to go negative. This single guard is reused by transaction create/update/delete and by transfers.

### 6.3 Wallet default-switching (`WalletService`)
Exactly one wallet per user has `is_default = true` at all times:
- A user's first wallet is always forced default, regardless of what's passed in.
- Setting `is_default: true` on any wallet clears it from every other wallet for that user, inside one transaction.
- Deleting the default wallet (only possible once its balance is 0 — see below) auto-promotes the oldest remaining wallet to default, so a user is never left with none.
- A wallet with a non-zero balance can't be deleted (`WalletHasBalanceException`, 422) — prevents silently discarding a live balance.

### 6.4 Transfers (`TransferService`)
A transfer is one atomic operation across three tables:
1. Lock both wallets (ascending id order).
2. Debit `from_wallet`, guard against negative.
3. Credit `to_wallet`.
4. Insert the `transfers` row.
5. Insert **two mirror `transactions` rows** — an `expense` on `from_wallet` and an `income` on `to_wallet`, both carrying `transfer_id` — so the transfer shows up naturally in a "recent activity" transactions feed, with `balance_before`/`balance_after` snapshots on each side.

Validation rejects `to_wallet_id === from_wallet_id` (`different:from_wallet_id`) and any wallet id not owned by the requesting user.

### 6.5 Notifications are triggered by real events, not just CRUD on the notifications table
`TransactionService::create()` and `TransferService::create()` both call into `NotificationService` (inside the same DB transaction, so a rolled-back financial operation never leaves an orphan notification):
- Every created transaction fires a `transaction` notification ("New Income"/"New Expense").
- Every completed transfer fires one `transaction`-type "Transfer Completed" notification (not two — a single transfer isn't two events from the user's point of view).
- **Low-balance crossing**: if a wallet's balance was *above* 100.00 and the operation brings it *to or below* 100.00, a one-time `low_balance` notification fires. It only fires on the crossing (checks `balanceBefore > 100 && balanceAfter <= 100`), not on every subsequent transaction while the wallet stays low — otherwise a user near the threshold would get spammed on every purchase.

### 6.6 Dashboard numbers exclude transfers (`DashboardRepository` / `DashboardService`)
`total_income`/`total_expense` and the chart series explicitly `whereNull('transfer_id')`. Moving money between your own wallets is a zero-sum reallocation, not real income or spending — including the mirror transactions would inflate both numbers every time a user moved money internally. `total_balance` (a straight sum across wallets) is unaffected by transfers either way, since it's zero-sum by construction.

The dashboard repository queries via `DB::table('transactions')` rather than the Eloquent model — deliberately, so grouped/aggregated rows (`SUM(amount) as total`) don't get the model's `type` enum cast applied to a raw aggregate column, and so soft-deleted rows are excluded explicitly (`whereNull('deleted_at')`) since bypassing Eloquent also bypasses its global soft-delete scope.

---

## 7. API Reference

All routes are under `/api/v1`. 🔒 = requires `Authorization: Bearer <access_token>`.

### Auth
| Method | Path | Auth | Notes |
|---|---|---|---|
| POST | `/auth/register` | – | Creates user (pending) + profile + default cash wallet; sends registration OTP |
| POST | `/auth/login` | – | Throttled 6/min. Returns access+refresh token pair |
| POST | `/auth/logout` | 🔒 | Revokes the current access token |
| POST | `/auth/refresh` | – | Body: `refresh_token`. Rotates the pair |
| GET | `/auth/me` | 🔒 | Current user + profile |
| POST | `/auth/forgot-password` | – | Throttled 6/min. Issues a password_reset OTP |
| POST | `/auth/verify-otp` | – | Throttled 6/min. `purpose: registration\|password_reset` |

### Wallets 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/wallets` | List the user's wallets |
| POST | `/wallets` | `balance` is never accepted here — only Transactions/Transfers move it |
| GET | `/wallets/{wallet}` | |
| PUT | `/wallets/{wallet}` | |
| DELETE | `/wallets/{wallet}` | Soft delete; 422 if balance ≠ 0 |

### Transactions 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/transactions` | Filters: `type`, `category_id`, `wallet_id`, `date_from`, `date_to`, `search` (matches `note`), `per_page` (max 100) |
| POST | `/transactions` | `category_id`'s type must match the transaction's `type` |
| GET | `/transactions/{transaction}` | |
| PUT | `/transactions/{transaction}` | 422 if the transaction is transfer-linked |
| DELETE | `/transactions/{transaction}` | Soft delete; reverses the balance effect; 422 if transfer-linked |

### Transfers 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/transfers` | |
| POST | `/transfers` | `from_wallet_id` ≠ `to_wallet_id`, both owned by the requester |
| GET | `/transfers/{transfer}` | No update/delete — transfers are immutable |

### Categories 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/categories` | Filter: `type=income\|expense`. Read-only reference data, no ownership |
| GET | `/categories/{category}` | |

### Dashboard 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/dashboard/summary` | `total_balance`, `total_income`, `total_expense`. Optional `date_from`/`date_to` (default: current month) |
| GET | `/dashboard/chart` | `period=weekly\|monthly` (default weekly). Gap-filled series — days/months with no activity return zeros |

### Notifications 🔒
| Method | Path | Notes |
|---|---|---|
| GET | `/notifications` | Filter: `unread_only=1` |
| POST | `/notifications/{notification}/read` | |
| POST | `/notifications/read-all` | |

---

## 8. Response Envelope & Localization

Every response is `{ "data": ..., "message": "...", "meta"?: {...}, "links"?: {...} }`. Errors are `{ "message": "...", "errors"?: {...} }`.

Send `Accept-Language: ar` to get Arabic messages — this covers Laravel's own validation strings (fully translated in `lang/ar/validation.php`, `auth.php`, `passwords.php`) as well as this app's own domain messages (`lang/{en,ar}/api.php`). Locale is resolved by `SetLocaleFromHeader` middleware, restricted to `en`/`ar`, silently falling back to the app default (`en`) for anything else.

---

## 9. Testing

29 tests across Unit + Feature suites (`tests/Feature/{Auth,Wallet,Transaction,Transfer}`), covering: register → OTP → login → refresh-rotation → logout; wallet default-switching and the funded-wallet delete guard; transaction balance math (create/update/delete) including the category-mismatch and insufficient-balance guards; transfer atomicity, mirror transactions, and the transfer-linked-transaction edit/delete lock; and cross-user 403/isolation checks throughout.

**Setup**: tests run against a **dedicated MySQL database** (`test_fintech_app_testing`, configured in `phpunit.xml`), separate from the dev database, so `php artisan test` never touches your local data. `RefreshDatabase` wraps each test in a transaction; `WalletTypeSeeder`/`CategorySeeder` are seeded per-test via a shared `CreatesAuthenticatedUser` trait, which also drives the real register→verify-otp→login HTTP flow (reading the OTP from `meta.debug_otp_code`, never from logs).

```bash
php artisan test              # full suite
php artisan test --testsuite=Feature
```

**A note for anyone extending the tests**: Laravel's `AuthManager` caches the resolved Sanctum guard (and the guard caches its resolved user) for the lifetime of the test's application container. A single test that authenticates as two different users in sequence will otherwise silently keep returning the first user's identity on the second user's "authenticated" requests. The `authHeaders()` helper in `CreatesAuthenticatedUser` calls `$this->app['auth']->forgetGuards()` before every authenticated call specifically to defeat this — it's a test-harness artifact only (each real production request gets a fresh PHP process), but it will produce false negatives (or, worse, false positives on authorization tests) if omitted.

---

## 10. Getting Started

```bash
cp .env.example .env               # then set DB_* to your MySQL credentials
composer install
php artisan key:generate
php artisan migrate --seed         # seeds wallet types + categories
php artisan serve
```

Create a second MySQL database for tests (name must match `phpunit.xml`'s `DB_DATABASE`) — `CREATE DATABASE test_fintech_app_testing;` — then `php artisan test`.

A ready-to-import Postman collection lives at `postman/Fintech-Wallet-API.postman_collection.json` — it drives the full register → verify → login → wallets → transactions → transfers → dashboard → notifications flow end-to-end with auto-chained tokens/ids, no manual copy-pasting required. See its own in-app description for setup notes (`base_url`, `accept_language` variables).

---

## 11. Known Limitations / Notes for Future Work

- `NotificationType::PaymentSuccess`/`PaymentFailed` and the `devices` table (FCM tokens) are scaffolded but not wired to anything yet — there's no real payment gateway or push-notification dispatch in this project.
- `meta.debug_otp_code` is a deliberate development/testing convenience gated to non-production environments; it must not ship in a real deployment's `APP_ENV`.
- The low-balance threshold (100.00) and OTP TTL (10 min) / access-token TTL (60 min) / refresh-token TTL (30 days) are constants in their respective Services, not yet exposed via `config/`. Worth promoting to config if they ever need to vary per environment.
