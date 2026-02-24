# Spark Awards Competition

Web application for managing Spark Design Awards accounts, submissions, payments, judging, and admin workflows.

## Stack

- PHP 8.1+ (project currently running on PHP 8.3 in development)
- CodeIgniter 4
- MySQL/MariaDB
- Authorize.Net (payments)
- Duo Universal Prompt (2FA for elevated roles)

## Features

- Login, registration, forgot/reset password
- Forced password expiry (default: 6 months)
- Password history checks (prevents reuse)
- Role-based access control (`admin`, `editor`, `judge`, `user`)
- 2FA gate for `admin` and `editor`
- Entry submission and judging workflows
- Admin management for users, competitions, coupons, notifications, scoring, and exports

## Security Highlights

- Passwords stored with `password_hash()` (`PASSWORD_DEFAULT`)
- Legacy salt/hash migration path retained for login verification and automatic rehashing
- Password reset uses selector + hashed verifier token model
- Reset tokens are one-time use (`used_at`)
- Auth endpoint throttling (login/register/forgot/reset/forced-password-update)
- CSRF globally enabled (except payment webhook endpoint)
- Secure headers filter enabled globally
- Session regeneration on login and impersonation transitions
- Cookie hardening defaults:
  - `HttpOnly = true`
  - `SameSite = Lax`
  - `Secure = true` in production
- Service-layer role enforcement prevents non-admin role elevation even if controller checks are bypassed

## Project Layout

- `app/Controllers` HTTP endpoints
- `app/Services` business logic
- `app/Models` persistence models
- `app/Views` templates
- `app/Config` framework and app configuration
- `app/Database/Migrations` schema migrations
- `tests` unit/database tests

## Local Setup

1. Clone and install dependencies:

```bash
composer install
```

2. Create/update environment file:

- Use `.env`
- Set at least:
  - `CI_ENVIRONMENT=development`
  - `app.baseURL`
  - database credentials
  - Duo credentials (if testing elevated-role login)
  - Authorize.Net sandbox credentials

3. Run migrations:

```bash
php spark migrate
```

4. Start the app (example):

```bash
php spark serve
```

or use your MAMP/Apache virtual host.

## Environment Variables

### Auth Policy

- `AUTH_ENFORCE_PASSWORD_EXPIRY=true|false`
- `AUTH_PASSWORD_EXPIRY_MONTHS=6`
- `AUTH_PASSWORD_HISTORY_LIMIT=24`

### Authorize.Net

- `ANET_MODE=auto|sandbox|production`
- `ANET_SANDBOX_API_LOGIN_ID`
- `ANET_SANDBOX_TRANSACTION_KEY`
- `ANET_SANDBOX_SIGNATURE_KEY`
- `ANET_PRODUCTION_API_LOGIN_ID`
- `ANET_PRODUCTION_TRANSACTION_KEY`
- `ANET_PRODUCTION_SIGNATURE_KEY`

`ANET_MODE=auto` uses sandbox outside production and live credentials in production.

### Email

Outbound mail is read from site settings, not `.env` SMTP fallback.

## Database Notes

Recent hardening expects:

- `comp_users.password_changed_at` (`DATETIME`, nullable)
- `comp_user_password_history` table
- `comp_password_resets` table with:
  - `token` (unique)
  - `selector` (unique)
  - `token_hash`
  - `expires_at`
  - `used_at`
  - `created_at`

Migration `2026-02-22-000001_HardenAuthSchema` codifies these requirements for MySQL.

## Running Tests

Run all tests:

```bash
vendor/bin/phpunit
```

Note: database tests use SQLite in the `tests` DB group. Ensure the PHP `sqlite3` extension is enabled locally.

Run only security tests:

```bash
vendor/bin/phpunit tests/unit/Security
```

## CI

GitHub Actions workflow: `.github/workflows/ci.yml`

It runs:

- `composer validate --strict`
- `composer install`
- `composer audit`
- PHP lint across tracked `.php` files
- `vendor/bin/phpunit --no-coverage`

## Deployment Checklist

1. Set `CI_ENVIRONMENT=production`.
2. Rotate all production secrets before go-live:
   - SMTP credentials
   - Authorize.Net production keys
   - Duo credentials
   - DB credentials
   - encryption key
3. Ensure HTTPS termination and trusted proxy config are correct.
4. Verify production cron/jobs if you add scheduled cleanup tasks.
5. Confirm payment webhook route is reachable and signature-validated.

## Important

- Never commit real secrets.
- Current `.env` includes a TODO reminder for final production secret rotation.
