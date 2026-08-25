# Fintech Wallet & Expense Tracker — API

A Laravel 12 REST API backend for a personal finance app: multiple wallets, income/expense tracking with a full balance audit trail, transfers between wallets, budgeting categories, dashboard analytics, and notifications. Built as a learning project to pair with a Flutter (Clean Architecture) frontend, but the API itself is framework-agnostic on the client side.

Full technical reference (architecture, data model, business rules, every endpoint): [docs/TECHNICAL_DOCUMENTATION.md](docs/TECHNICAL_DOCUMENTATION.md) or the standalone [docs/technical-documentation.html](docs/technical-documentation.html).

## Features

- **Auth** — registration with OTP verification, login/logout, Sanctum access tokens with a custom refresh-token rotation flow, forgot-password/reset via OTP, per-user profile.
- **Wallets** — multiple wallets per user (cash / bank / savings / credit), single-default-wallet enforcement, balance-guarded delete (can't delete a wallet with a non-zero balance).
- **Categories** — seeded income/expense category tree (parent + sub-categories) for organizing transactions.
- **Transactions** — income/expense entries with a full `balance_before`/`balance_after` audit trail computed with `bcmath` (no float rounding errors), a negative-balance guard (only credit wallets may go negative), category-type validation, pagination/filtering/search.
- **Transfers** — atomic money movement between a user's own wallets, with mirrored ledger entries in the transactions list (excluded from income/expense totals so they don't distort reports).
- **Dashboard** — balance/income/expense summary and a weekly/monthly chart.
- **Notifications** — auto-fired on new transactions, completed transfers, and low-balance threshold crossings.
- **Bilingual** — every response (validation errors, success/error messages) respects `Accept-Language: en` or `ar`.
- **Tested** — 29 automated Feature/Unit tests covering the critical paths (auth flow, balance math, transfers, cross-user authorization).

## Tech Stack

| | |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL |
| Auth | Laravel Sanctum + custom refresh tokens |
| API | REST, versioned under `/api/v1`, unified JSON envelope |

## Requirements

- PHP >= 8.2 with the usual Laravel extensions (`pdo_mysql`, `mbstring`, `bcmath`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)
- Composer
- MySQL 8+ (or MariaDB)

## Getting Started (fresh clone)

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy the environment file and generate an app key
cp .env.example .env
php artisan key:generate

# 3. Point .env at your MySQL database
#    (create the database first, e.g. `CREATE DATABASE fintech_wallet;`)
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=fintech_wallet
#    DB_USERNAME=root
#    DB_PASSWORD=your_password

# 4. Run migrations and seed reference data (wallet types + categories)
php artisan migrate --seed

# 5. Start the dev server
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api/v1`.

> **Note:** in `local`/`testing` environments, the register and forgot-password responses include the plain OTP code in `meta.debug_otp_code` — there's no real SMS/mail provider wired up yet, so this is how you retrieve the verification code without digging through logs. Do not enable this in a production `APP_ENV`.

### Trying it out

- Import `postman/Fintech-Wallet-API.postman_collection.json` into Postman. It drives the full register → verify-otp → login → wallets → transactions → transfers → dashboard → notifications flow automatically (tokens and ids are captured by each request's test script — no manual copy/pasting).
- Or just hit the endpoints directly; see the [API reference](docs/TECHNICAL_DOCUMENTATION.md#7-api-reference) for the full list.

## Running the Tests

Tests run against a **separate** MySQL database so `php artisan test` never touches your dev data.

```bash
# 1. Create a dedicated test database
mysql -u root -p -e "CREATE DATABASE fintech_wallet_testing;"

# 2. Create .env.testing (gitignored — not committed) with that database's credentials:
cat > .env.testing <<'EOF'
APP_KEY=base64:REPLACE_WITH_YOUR_APP_KEY
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fintech_wallet_testing
DB_USERNAME=root
DB_PASSWORD=your_password
EOF
#    (copy the APP_KEY value from your main .env)

# 3. Run the suite
php artisan test
```

29 tests should pass, covering the auth flow, wallet rules, transaction balance math, and transfer atomicity — see [docs/TECHNICAL_DOCUMENTATION.md §9](docs/TECHNICAL_DOCUMENTATION.md#9-testing) for what's covered and a gotcha worth knowing before extending them.

## Project Structure

Controller → Action → Service → Repository → Model, enforced across every feature (no business logic in controllers):

```
app/
├── Http/Controllers/Api/V1/{Auth,Wallet,Transaction,Transfer,Category,Dashboard,Notification}/
├── Http/Requests/Api/V1/...      # Form Request validation, per endpoint
├── Http/Resources/                # API response shaping
├── Actions/...                    # one invokable class per use case
├── Services/...                   # business logic lives here
├── Repositories/...                # the only layer that talks to Eloquent
├── Enums/                          # PHP 8.1+ backed enums for every fixed status/type
├── Exceptions/                     # domain exceptions (422/403/etc, not 500s)
└── Policies/                      # ownership checks (Wallet/Transaction/Transfer/Notification)
```

See [docs/TECHNICAL_DOCUMENTATION.md §2](docs/TECHNICAL_DOCUMENTATION.md#2-architecture) for the full breakdown.

## Documentation

| Doc | What it covers |
|---|---|
| [docs/TECHNICAL_DOCUMENTATION.md](docs/TECHNICAL_DOCUMENTATION.md) | Full technical reference: architecture, data model, enums, auth, business logic, every endpoint |
| [docs/technical-documentation.html](docs/technical-documentation.html) | Same content, standalone styled page |
| [docs/architecture-diagram.html](docs/architecture-diagram.html) | Controller → Action → Service → Repository layering, visually |
| [docs/user-journey-flow.html](docs/user-journey-flow.html) | Step-by-step: what gets created, in order, from signup through a transfer |
| [docs/client-walkthrough.html](docs/client-walkthrough.html) | Arabic, non-technical walkthrough of how the app's pieces fit together |
| [docs/عرض-تفصيلي-للمشروع.docx](docs/عرض-تفصيلي-للمشروع.docx) | Arabic Word doc: full scope of work + a proposed 17-screen app map |
| [postman/Fintech-Wallet-API.postman_collection.json](postman/Fintech-Wallet-API.postman_collection.json) | Ready-to-run Postman collection, auto-chained end-to-end |

## License

Built on the [Laravel](https://laravel.com) framework, MIT licensed.
