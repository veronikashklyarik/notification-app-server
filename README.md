# Notifyr

A Laravel server-side application for managing recurring notifications with a clean web UI and a public REST API for mobile clients (built with NativePHP).

---

## Tech Stack

- **PHP** 8.4 / **Laravel** 13
- **Laravel Sanctum** v4 — API token authentication
- **Tailwind CSS** v4 — via Vite
- **Alpine.js** v3 — via npm/Vite bundle
- **dedoc/Scramble** — auto-generated OpenAPI documentation at `/docs/api`
- **SQLite** (default) — swap via `.env`
- **MailHog** — local mail (SMTP on port 1025)

---

## Features

### Authentication (Web — session-based)
- Register / Login with email & password
- Email verification required after registration — users are blocked from the app until verified
- Forgot password / Reset password via signed email link
- Remember Me on login
- Show/hide password toggle on all password fields
- Password rules: min 8 characters, at least one letter and one number
- Logout

### User Profile
- Update name, email, avatar (JPG/PNG/GIF/WebP, max 2 MB)
- Change password (requires current password)
- Timezone selector — grouped by region, defaults to `UTC`

### Notifications
- Create recurring notifications with flexible scheduling:
  - **Every day** — fires every day at the specified time(s)
  - **Specific days of the week** — pick any combination of Mon–Sun
  - **Every N days** — configurable interval (1–365)
  - **Cyclical** — every N days / weeks / months / years
  - **As needed** — no automatic schedule, mark manually
- Multiple times per day (e.g. morning + evening)
- Optional start date (date-only, defaults to today) and end date (date-only, inclusive)
- `starts_at` and `ends_at` are stored as date-only values; `next_due_at` is a full UTC datetime
- Duration summary shown on the detail page when an end date is set
- Activate / deactivate notifications

### Notification History
- Full action history per notification: **Done**, **Cancelled**, **Postponed**
- Optional comment and postpone-until date per entry
- All timestamps displayed in the user's timezone

### Timezone Handling
- User's timezone is stored on the `users` table (default `UTC`)
- The `SetUserTimezone` middleware shares `$userTimezone` to all Blade views
- Dates are displayed in the user's timezone; stored values are not changed when timezone is updated

---

## REST API

Base URL: `/api/v1/`

Authentication: **Bearer token** (Laravel Sanctum). Each device login creates a named token scoped to that device. All devices share the same notifications and history data.

Interactive documentation (Swagger UI): **`/docs/api`**

### Token expiry

Token lifetime is determined at login/register based on the user's web Remember Me session:

| Condition | Token lifetime |
|-----------|---------------|
| User has an active Remember Me web session (`remember_token` is set) | **1 year** |
| No Remember Me session | **1 day** |

New registrations always receive a 1-day token (no Remember Me session exists yet).

### Auth endpoints (no token required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Create account and receive a token |
| POST | `/api/v1/auth/login` | Login and receive a token (requires `device_name`) |

### Protected endpoints (Bearer token required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| DELETE | `/api/v1/auth/logout` | Revoke the current device token |
| GET | `/api/v1/profile` | Get authenticated user (includes `timezone`) |
| GET | `/api/v1/notifications` | List notifications (supports `?active_only=1`) |
| POST | `/api/v1/notifications` | Create a notification |
| GET | `/api/v1/notifications/{id}` | Get a notification |
| PUT | `/api/v1/notifications/{id}` | Update a notification |
| DELETE | `/api/v1/notifications/{id}` | Delete a notification |
| POST | `/api/v1/notifications/{id}/actions` | Record Done/Cancelled/Postponed |
| GET | `/api/v1/history` | List all history entries (supports `?notification_id=`) |

### Notification payload fields

| Field | Type | Notes |
|-------|------|-------|
| `schedule_type` | string | `every_day`, `week_days`, `every_n_days`, `cyclical`, `as_needed` |
| `week_days` | int[] | ISO weekday numbers 1–7; required when `schedule_type=week_days` |
| `every_n_days` | int | 1–365; required when `schedule_type=every_n_days` |
| `cyclical_value` | int | Required when `schedule_type=cyclical` |
| `cyclical_unit` | string | `days`, `weeks`, `months`, `years`; required when `schedule_type=cyclical` |
| `times` | string[] | Array of `HH:MM` strings; omit for `as_needed` |
| `starts_at` | date | `YYYY-MM-DD`; defaults to today |
| `ends_at` | date | `YYYY-MM-DD`; optional, inclusive |
| `is_active` | bool | Update only |

`starts_at` and `ends_at` are returned as `YYYY-MM-DD` strings. `next_due_at` is returned as an ISO-8601 datetime.

---

## Project Structure

```
app/
├── Enums/
│   ├── HistoryAction.php       # Done, Cancelled, Postponed
│   └── ScheduleType.php        # EveryDay, WeekDays, EveryNDays, Cyclical, AsNeeded
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── RegisterController.php
│   │   │   ├── LogoutController.php
│   │   │   ├── ForgotPasswordController.php
│   │   │   ├── ResetPasswordController.php
│   │   │   ├── EmailVerificationNoticeController.php
│   │   │   ├── EmailVerificationController.php
│   │   │   └── ResendVerificationEmailController.php
│   │   ├── Api/V1/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   └── LogoutController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── HistoryController.php
│   │   │   └── ProfileController.php
│   │   ├── NotificationController.php
│   │   ├── HistoryController.php
│   │   └── ProfileController.php
│   ├── Middleware/
│   │   └── SetUserTimezone.php
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── Api/V1/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   ├── StoreNotificationRequest.php
│   │   │   ├── UpdateNotificationRequest.php
│   │   │   └── RecordNotificationActionRequest.php
│   │   ├── StoreNotificationRequest.php
│   │   ├── UpdateNotificationRequest.php
│   │   └── UpdateProfileRequest.php
│   └── Resources/Api/V1/
│       ├── UserResource.php
│       ├── NotificationResource.php
│       └── NotificationHistoryResource.php
├── Models/
│   ├── User.php
│   ├── Notification.php        # calculateNextDueAt(), advanceNextDueAt()
│   └── NotificationHistory.php
└── Providers/
    └── AppServiceProvider.php  # Password defaults, HTTPS in production, Scramble config

config/
└── scramble.php                # OpenAPI docs configuration

database/migrations/
├── 0001_01_01_000000_create_users_table.php
├── 2026_03_22_000001_add_avatar_to_users_table.php
├── 2026_03_22_000002_create_personal_access_tokens_table.php
├── 2026_03_22_000003_create_notifications_table.php
├── 2026_03_22_000004_create_notification_history_table.php
├── 2026_03_22_000005_add_timezone_to_users_table.php
├── 2026_03_22_000006_update_notifications_schedule.php
└── 2026_03_22_000007_change_date_columns_on_notifications.php

resources/views/
├── components/
│   ├── layouts/
│   │   ├── app.blade.php           # Sticky navbar, user dropdown (Alpine), flash messages
│   │   └── guest.blade.php         # Centered card layout
│   └── password-input.blade.php    # Show/hide toggle (Alpine)
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   ├── reset-password.blade.php
│   └── verify-email.blade.php
├── profile/
│   └── edit.blade.php
├── notifications/
│   ├── index.blade.php             # Includes "API Docs" link
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php
│   └── _form_script.blade.php      # Shared Alpine.js notificationForm() component
└── history/
    └── index.blade.php
```

---

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

### Local mail (MailHog)

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@notifyr.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Start MailHog and open `http://localhost:8025` to view outgoing emails (verification, password reset).

### Development server

```bash
composer run dev
# or separately:
php artisan serve
npm run dev
```

---

## Key Implementation Notes

### Notification table name
`NotificationHistory` model uses `protected $table = 'notification_history'` to prevent Laravel from auto-pluralizing to `notification_histories`.

### Alpine.js
Bundled via npm (`alpinejs` package), imported in `resources/js/app.js` and started with `Alpine.start()`. Available on both guest and authenticated layouts. `[x-cloak]` is defined in `app.css`.

### Email verification
`User` implements `MustVerifyEmail`. After registration the `Registered` event fires the verification email. All app routes (profile, notifications, history) require `verified` middleware. Verification, resend, and logout routes only require `auth`.

### Timezone storage
All datetimes are stored in UTC. The `SetUserTimezone` middleware runs on all web routes and shares `$userTimezone` to Blade views. `next_due_at` is stored and returned as UTC; `starts_at` and `ends_at` are date-only and have no timezone component.

### Notification scheduling
`Notification::calculateNextDueAt(string $timezone)` advances `next_due_at` by first checking for a later time slot on the same date, then finding the next eligible date according to the schedule type. `ends_at` is treated as inclusive — occurrences on the last day are allowed (checked via `endOfDay()`).

### OpenAPI documentation
`dedoc/scramble` auto-generates the OpenAPI spec from route definitions, Form Requests, and Eloquent Resources. Configured in `AppServiceProvider` to cover only `api/v1/*` routes with a global Bearer token security scheme. Documentation UI is available at `/docs/api` (restricted to non-production by default via `RestrictedDocsAccess` middleware).

### API token expiry
Sanctum token lifetime is set at creation time using `createToken($name, ['*'], $expiresAt)`. The expiry is derived from the user's `remember_token` — 1 year if set (active web Remember Me session), 1 day otherwise. New registrations always receive a 1-day token.
