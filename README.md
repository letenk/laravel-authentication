# Laravel Auth System — Authentication Boilerplate Built with Production-Grade Patterns

A well-architected authentication boilerplate built with Laravel 13, designed to be the starting point for any PHP project that needs a solid auth foundation. The patterns and architecture here — typed request DTOs via spatie/laravel-data, custom query scopes per model, repository DTOs, generic OTP table, libphonenumber-based phone validation, token rotation — are the same patterns you'd reach for in production code.

This is a **foundation, not a finished production system**. Use it to skip the boilerplate; harden it for your production environment as you grow.

> Built as a personal boilerplate and portfolio project by [Rizky Darmawan](https://github.com/letenk).

---

## Why This Exists

Every new project needs authentication. Instead of rebuilding the same patterns from scratch, this boilerplate provides a well-structured, tested, and extensible auth system that can be dropped into any Laravel project.

**What's included out of the box:**
- Register, login (email or phone), logout, refresh token
- Multi-device session management
- Email verification via OTP
- Forgot & reset password via OTP
- User profile management
- Rate limiting on sensitive endpoints
- Soft delete support
- JWT authentication with configurable expiry
- Structured error handling
- Dockerized with health checks

---

## Tech Stack

| Layer | Library | Why |
|---|---|---|
| **Framework** | [Laravel 13](https://laravel.com) | Battle-tested PHP framework with great ecosystem |
| **Authentication** | [php-open-source-saver/jwt-auth](https://github.com/PHP-Open-Source-Saver/jwt-auth) | Stateless JWT access + refresh token |
| **Database** | PostgreSQL | Battle-tested, feature-rich relational DB |
| **ORM** | Eloquent | Built-in Laravel ORM with custom query builder (Scope) |
| **Validation / DTO** | [spatie/laravel-data](https://github.com/spatie/laravel-data) | Typed request objects with built-in validation |
| **Phone Validation** | [propaganistas/laravel-phone](https://github.com/Propaganistas/Laravel-Phone) | libphonenumber-based phone number validation |
| **Queue** | Laravel Queue (database driver) | Async email dispatch |
| **Testing** | PHPUnit 12 + Mockery | Feature tests hitting real database |

---

## Project Structure

```
.
├── app/
│   ├── DTOs/                        # Data Transfer Objects
│   │   ├── Auth/                    # Auth-related DTOs & request objects
│   │   └── User/                    # User-related DTOs & request objects
│   ├── Exceptions/                  # GeneralException — domain-level error type
│   ├── Http/
│   │   ├── Controllers/Api/         # API controllers (all extend BaseController)
│   │   ├── Requests/                # spatie/laravel-data request classes
│   │   │   ├── Auth/
│   │   │   ├── Builders/            # CustomDtoBuilder, CustomRequestBuilder
│   │   │   └── User/
│   │   └── Services/                # Business logic layer
│   ├── Mail/                        # Mailable classes (OtpMail)
│   ├── Models/
│   │   └── Scope/                   # Custom Eloquent query builders per model
│   ├── Repository/                  # Repository classes
│   ├── Rules/                       # Custom validation rules
│   └── Support/                     # BootValidator, BootRateLimiter
├── docker/
│   ├── nginx/                       # nginx.conf + default.conf (Laravel virtual host)
│   ├── php/                         # opcache.ini
│   ├── entrypoint.sh                # Container bootstrap script
│   └── supervisord.conf             # Process manager (php-fpm, nginx, queue, scheduler)
├── database/
│   ├── factories/
│   └── migrations/
├── resources/views/mail/            # Blade email templates
├── routes/
│   └── api.php
├── tests/Feature/                   # Feature tests per slice
├── .env.example
├── docker-compose.yml
└── Dockerfile                       # Multi-stage build (builder + production)
```

---

## API Endpoints

Base path: `/api/v1`

### Auth — `/auth`

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|---|
| `POST` | `/auth/register` | — | Register new user |
| `POST` | `/auth/login` | — | Login with email or phone · rate limited |
| `POST` | `/auth/refresh` | — | Refresh access token · rate limited |
| `POST` | `/auth/logout` | ✓ | Logout current session |
| `POST` | `/auth/logout-all` | ✓ | Logout all devices |
| `GET` | `/auth/me` | ✓ | Get current user |
| `DELETE` | `/auth/me` | ✓ | Delete account (soft delete) |
| `POST` | `/auth/forgot-password` | — | Send OTP to email · rate limited |
| `POST` | `/auth/reset-password` | — | Reset password with OTP · rate limited |
| `POST` | `/auth/email/send-otp` | ✓ | Send email verification OTP · rate limited |
| `POST` | `/auth/email/verify` | ✓ | Verify email with OTP · rate limited |

### User — `/user`

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|---|
| `GET` | `/user/sessions` | ✓ | List all active sessions |
| `DELETE` | `/user/sessions/:id` | ✓ | Revoke a specific session |
| `PUT` | `/user/profile` | ✓ | Update name / phone |
| `PUT` | `/user/password` | ✓ | Change password |

### Health

| Method | Endpoint | Description |
|--------|----------|---|
| `GET` | `/health` | Health check with DB connectivity |

---

## Getting Started

### Prerequisites

- PHP 8.3+
- PostgreSQL 16
- Composer
- Docker & Docker Compose (optional)

### 1. Clone and configure

```bash
git clone https://github.com/letenk/laravel-authentication.git
cd laravel-authentication
cp .env.example .env
# Edit .env with your database and SMTP credentials
```

### 2. Install dependencies

```bash
composer install
```

### 3. Generate JWT secret

```bash
php artisan jwt:secret
```

### 4. Run database migrations

```bash
php artisan migrate
```

### 5. Run the server

```bash
php artisan serve
```

### Run with Docker

```bash
docker compose up --build -d
```

> App will be available at `http://localhost:8000`.

---

## Development

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run queue worker (for OTP email sending)
php artisan queue:work

# Clear all caches
php artisan optimize:clear
```

---

## Environment Variables

Copy `.env.example` to `.env` and fill in the values.

```env
APP_NAME="Laravel Auth System"
APP_ENV=local
APP_PORT=8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=authentication_db_laravel
DB_USERNAME=postgres
DB_PASSWORD=secret

# JWT
JWT_SECRET=your-super-secret-jwt-key
JWT_TTL=15
JWT_REFRESH_TTL=20160

# OTP
OTP_LENGTH=5
OTP_TTL_MINUTES=10
OTP_MAX_ATTEMPT=5
OTP_NEXT_ATTEMPT_WAIT_MINUTES=1

# Mail (Mailtrap for dev, real SMTP for prod)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Database Schema

```
users
  id, name, email, phone, password
  is_verified, verified_at
  login_type, deleted_at
  created_at, updated_at

refresh_tokens
  id, user_id, token
  device_name, device_id, ip_address, user_agent
  expires_at, revoked_at, replaced_by_token

otps
  id, user_id, code
  channel (email | phone)
  purpose (email_verification | phone_verification | password_reset)
  submit_attempt, next_attempt_at
  expires_at, verified_at
```

---

## Architecture Decisions

**Controller → Service → Repository** — dependencies flow in one direction. Controllers handle HTTP, services handle business logic, repositories handle database access. No interface layer for repositories — kept simple intentionally.

**Custom Query Builder (Scope)** — each model has a typed `Builder` subclass (e.g. `UserScope`) with composable filter methods. This gives IDE autocomplete on filter chains and keeps query logic out of the service layer.

**Repository DTO** — repositories accept a typed DTO (`UserRepositoryDTO`) with `select`, `filters`, and `eagerLoadRelation` fields instead of arbitrary arrays. Explicit over magic.

**Generic OTP table** — a single `otps` table with `channel` + `purpose` enums covers email verification, phone verification, and password reset. No need for separate tables per purpose.

**Email enumeration protection** — `forgotPassword` always returns 200 regardless of whether the email exists. This prevents attackers from discovering registered emails.

**Async email** — OTP emails are queued (`ShouldQueue`) so the HTTP response returns immediately without waiting for SMTP.

**Rate limiting** — per-endpoint named rate limiters configured in `BootRateLimiter`, applied via `throttle:<name>` middleware. Returns consistent `429` JSON response.

**Soft delete** — users are soft-deleted (`deleted_at`). Soft-deleted accounts cannot log in and all their tokens are revoked on deletion.

---

## Roadmap

| Feature | Status |
|---------|--------|
| Register, Login (email + phone) | ✅ Done |
| Refresh token with rotation | ✅ Done |
| Logout / Logout all devices | ✅ Done |
| Multi-device session management | ✅ Done |
| User profile & password update | ✅ Done |
| Email OTP verification | ✅ Done |
| Forgot & reset password | ✅ Done |
| Rate limiting | ✅ Done |
| Soft delete | ✅ Done |
| Docker setup | ✅ Done |
| Feature tests (80 tests) | ✅ Done |
| Role system (RBAC) | 🔲 Planned |
| Phone OTP verification | 🔲 Planned |
| Cleanup expired tokens (scheduled) | 🔲 Planned |

---

## License

MIT License

Copyright (c) 2026 Rizky Darmawan

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
