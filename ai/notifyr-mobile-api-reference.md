# Notifyr Mobile App - API Reference

Base URL: `https://notifyr.grinik.pl/api/v1`

This document describes the backend REST API for the Notifyr mobile application built with NativePHP Mobile. The mobile app is a separate Laravel project that communicates with this API via `Illuminate\Support\Facades\Http`.

---

## Authentication

Authentication uses **Laravel Sanctum** bearer tokens. Every authenticated request must include:

```
Authorization: Bearer {token}
```

### Token Lifecycle

- **Access token** — used for all API requests. Expires in **30 days**.
- **Refresh token** — used only to obtain a new token pair. Expires in **1 year**.

Both tokens are returned on login and registration. Store them in `SecureStorage` (device Keychain/Keystore).

### Token Refresh Flow

1. Make API requests with the access token.
2. If a request returns `401 Unauthorized`, the access token has expired.
3. Call `POST /auth/refresh` with the refresh token to get a new pair.
4. If the refresh token is also expired, redirect to the login screen.

### Rate Limiting

Auth endpoints (`/auth/register`, `/auth/login`, `/auth/refresh`) are rate-limited to **5 requests per minute** per IP.

---

## Endpoints

### Public (No Authentication)

#### Check App Version

```
GET /version/check?version={version}&platform={platform}
```

| Param      | Type   | Required | Values          |
|------------|--------|----------|-----------------|
| `version`  | string | yes      | e.g. `1.2.0`   |
| `platform` | string | yes      | `ios`, `android`|

**Response** `200`:
```json
{
    "current_version": "1.0.0",
    "minimum_version": "1.0.0",
    "recommended_version": "1.1.0",
    "latest_version": "1.2.0",
    "update_required": true,
    "force_update": false,
    "message": "A new version is available",
    "download_url": "https://..."
}
```

**Mobile app behavior:**
- `force_update: true` — block the app, show mandatory update screen.
- `update_required: true` and `force_update: false` — show a dismissible update warning.
- Both `false` — no action needed.

---

#### Register

```
POST /auth/register
```

| Field                   | Type   | Required | Rules                            |
|-------------------------|--------|----------|----------------------------------|
| `name`                  | string | yes      | max 255                          |
| `email`                 | string | yes      | valid email, unique              |
| `password`              | string | yes      | min 8, letters + numbers         |
| `password_confirmation` | string | yes      | must match `password`            |
| `device_name`           | string | yes      | max 255 (e.g. "iPhone 15 Pro")   |

**Response** `201`:
```json
{
    "user": { "id": 1, "name": "...", "email": "...", "avatar_url": null, "timezone": "UTC", "created_at": "..." },
    "token": "1|access-token-string...",
    "refresh_token": "2|refresh-token-string..."
}
```

---

#### Login

```
POST /auth/login
```

| Field         | Type   | Required | Rules                          |
|---------------|--------|----------|--------------------------------|
| `email`       | string | yes      | valid email                    |
| `password`    | string | yes      |                                |
| `device_name` | string | yes      | max 255                        |

**Response** `200`:
```json
{
    "user": { "id": 1, "name": "...", "email": "...", "avatar_url": null, "timezone": "UTC", "created_at": "..." },
    "token": "3|access-token-string...",
    "refresh_token": "4|refresh-token-string..."
}
```

**Error** `422` — invalid credentials:
```json
{
    "message": "The provided credentials are incorrect.",
    "errors": { "email": ["The provided credentials are incorrect."] }
}
```

---

### Authentication Management

#### Refresh Token

```
POST /auth/refresh
Authorization: Bearer {refresh_token}
```

No request body needed. Must authenticate with the **refresh token** (not the access token).

**Response** `200`:
```json
{
    "token": "5|new-access-token...",
    "refresh_token": "6|new-refresh-token..."
}
```

**Error** `403` — used an access token instead of a refresh token.

The old refresh token is revoked after use.

---

#### Logout

```
DELETE /auth/logout
Authorization: Bearer {access_token}
```

Revokes the current access token. Call `DELETE /device-tokens` first to unregister the FCM token.

**Response** `200`:
```json
{ "message": "Logged out successfully." }
```

---

#### Change Password

```
PUT /auth/password
Authorization: Bearer {access_token}
```

| Field                   | Type   | Required | Rules                    |
|-------------------------|--------|----------|--------------------------|
| `current_password`      | string | yes      | must match current       |
| `password`              | string | yes      | min 8, letters + numbers |
| `password_confirmation` | string | yes      | must match `password`    |

All other tokens (other devices) are revoked after a successful password change.

**Response** `200`:
```json
{ "message": "Password changed successfully." }
```

---

#### Delete Account

```
DELETE /auth/account
Authorization: Bearer {access_token}
```

| Field      | Type   | Required | Rules              |
|------------|--------|----------|--------------------|
| `password` | string | yes      | must match current |

Deletes all tokens, device tokens, soft-deletes notifications, and soft-deletes the user.

**Response** `200`:
```json
{ "message": "Account deleted successfully." }
```

---

### Profile

#### Get Profile

```
GET /profile
Authorization: Bearer {access_token}
```

**Response** `200`:
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar_url": "https://notifyr.grinik.pl/storage/avatars/abc.jpg",
        "timezone": "Europe/Kyiv",
        "created_at": "2026-01-15T10:30:00.000000Z"
    }
}
```

---

#### Update Profile

```
PUT /profile
Authorization: Bearer {access_token}
Content-Type: multipart/form-data (if uploading avatar)
```

All fields are optional — only provided fields are updated.

| Field      | Type   | Required | Rules                             |
|------------|--------|----------|-----------------------------------|
| `name`     | string | no       | max 255                           |
| `timezone` | string | no       | valid timezone (e.g. `Europe/Kyiv`) |
| `avatar`   | file   | no       | image, max 2MB                    |

**Response** `200`:
```json
{
    "user": { "id": 1, "name": "...", "email": "...", "avatar_url": "...", "timezone": "...", "created_at": "..." }
}
```

---

### Device Tokens (FCM Push Notifications)

#### Register Device Token

```
POST /device-tokens
Authorization: Bearer {access_token}
```

| Field         | Type   | Required | Values            |
|---------------|--------|----------|-------------------|
| `token`       | string | yes      | FCM device token  |
| `platform`    | string | yes      | `ios`, `android`  |
| `device_name` | string | no       | max 255           |

Call this after login and whenever `TokenGenerated` event fires in NativePHP. If the token already exists, it is reassigned to the current user.

**Response** `201`:
```json
{ "message": "Device token registered successfully." }
```

---

#### Remove Device Token

```
DELETE /device-tokens
Authorization: Bearer {access_token}
```

| Field   | Type   | Required |
|---------|--------|----------|
| `token` | string | yes      |

Call before logout or when the user disables push notifications.

**Response** `200`:
```json
{ "message": "Device token removed successfully." }
```

---

### Notifications (CRUD)

#### List Notifications

```
GET /notifications
Authorization: Bearer {access_token}
```

Paginated (15 per page). Append `?page=2` for next page.

**Response** `200`:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Take vitamins",
            "description": "Daily morning vitamins",
            "schedule_type": "every_day",
            "week_days": null,
            "every_n_days": null,
            "cyclical_value": null,
            "cyclical_unit": null,
            "times": ["08:00", "20:00"],
            "frequency_label": "Every day",
            "starts_at": "2026-01-01",
            "ends_at": null,
            "is_active": true,
            "created_at": "...",
            "updated_at": "..."
        }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
}
```

---

#### Create Notification

```
POST /notifications
Authorization: Bearer {access_token}
```

| Field            | Type    | Required   | Rules / Notes                                          |
|------------------|---------|------------|--------------------------------------------------------|
| `name`           | string  | yes        | max 255                                                |
| `description`    | string  | no         | max 2000                                               |
| `schedule_type`  | string  | yes        | `every_day`, `week_days`, `every_n_days`, `cyclical`, `as_needed` |
| `week_days`      | array   | if `week_days` | Array of ints 1-7 (Mon=1, Sun=7)                  |
| `every_n_days`   | integer | if `every_n_days` | 1-365                                           |
| `cyclical_value` | integer | if `cyclical` | min 1                                              |
| `cyclical_unit`  | string  | if `cyclical` | `days`, `weeks`, `months`, `years`                 |
| `times`          | array   | no         | Array of `HH:mm` strings, e.g. `["08:00", "20:00"]`   |
| `starts_at`      | date    | no         | `YYYY-MM-DD`                                           |
| `ends_at`        | date    | no         | `YYYY-MM-DD`                                           |

**Response** `201`: Single `NotificationResource` object.

---

#### Show Notification

```
GET /notifications/{id}
Authorization: Bearer {access_token}
```

**Response** `200`: Single `NotificationResource` object.
**Error** `403`: Not the owner.

---

#### Update Notification

```
PUT /notifications/{id}
Authorization: Bearer {access_token}
```

Same fields as Create, plus:

| Field       | Type    | Required | Notes                |
|-------------|---------|----------|----------------------|
| `is_active` | boolean | no       | Enable/disable       |

**Response** `200`: Updated `NotificationResource` object.

---

#### Delete Notification

```
DELETE /notifications/{id}
Authorization: Bearer {access_token}
```

Soft-deletes the notification.

**Response** `200`:
```json
{ "message": "Notification deleted." }
```

---

### Events

Events are auto-generated instances of notifications (up to 30 pending events per notification).

#### List All Events

```
GET /events
Authorization: Bearer {access_token}
```

| Query Param       | Type   | Required | Notes                                        |
|--------------------|--------|----------|----------------------------------------------|
| `status`           | string | no       | `pending`, `done`, `cancelled`, `postponed`  |
| `notification_id`  | int    | no       | Filter by notification                       |
| `page`             | int    | no       | Pagination (20 per page)                     |

**Response** `200`:
```json
{
    "data": [
        {
            "id": "9e2f3a4b-...",
            "notification_id": 1,
            "notification": { "id": 1, "name": "Take vitamins" },
            "scheduled_at": "2026-04-06T08:00:00+00:00",
            "status": "pending",
            "postponed_until": null,
            "postpone_history": null,
            "comment": null,
            "completed_at": null,
            "created_at": "...",
            "updated_at": "..."
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

#### List Events for a Notification

```
GET /notifications/{id}/events
Authorization: Bearer {access_token}
```

| Query Param | Type   | Required | Notes                                       |
|-------------|--------|----------|---------------------------------------------|
| `status`    | string | no       | `pending`, `done`, `cancelled`, `postponed` |
| `page`      | int    | no       | Pagination (20 per page)                    |

---

#### Update Event Status

```
PATCH /events/{event_uuid}
Authorization: Bearer {access_token}
```

| Field             | Type   | Required       | Rules                                  |
|-------------------|--------|----------------|----------------------------------------|
| `status`          | string | yes            | `done`, `cancelled`, `postponed` (not `pending`) |
| `comment`         | string | no             | max 1000                               |
| `postponed_until` | date   | if `postponed` | ISO 8601 datetime, must be in the future |

**Response** `200`: Updated `NotificationEventResource` with loaded notification.

---

## Schedule Types Reference

| Value          | Description                     | Required Fields                    |
|----------------|---------------------------------|------------------------------------|
| `every_day`    | Runs every day                  | `times` (optional)                 |
| `week_days`    | Runs on specific days of week   | `week_days` (1-7), `times`         |
| `every_n_days` | Runs every N days               | `every_n_days` (1-365), `times`    |
| `cyclical`     | Repeats on a cycle              | `cyclical_value`, `cyclical_unit`  |
| `as_needed`    | No automatic schedule           | none                               |

---

## Event Status Flow

```
pending --> done
pending --> cancelled
pending --> postponed --> (new pending event created at postponed_until)
```

---

## Error Responses

All validation errors return `422` with this structure:
```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email has already been taken."]
    }
}
```

Authentication errors return `401`:
```json
{ "message": "Unauthenticated." }
```

Authorization errors return `403`.

---

## Mobile App Integration Checklist

- [ ] Store access token and refresh token in `SecureStorage` after login/register
- [ ] Add `Authorization: Bearer {token}` header to all authenticated requests
- [ ] Handle `401` responses by attempting token refresh, then retry the original request
- [ ] Call `GET /version/check` on app launch to handle forced/recommended updates
- [ ] Register FCM device token via `POST /device-tokens` after login and on `TokenGenerated` event
- [ ] Remove FCM device token via `DELETE /device-tokens` before logout
- [ ] Set user timezone via `PUT /profile` after login (use device timezone)
- [ ] Check `Network::status()` before making API calls for offline handling
- [ ] Use queued jobs for heavy sync operations to avoid blocking the UI

---

## Deep Links

The backend serves well-known files for Universal Links (iOS) and App Links (Android):

- iOS: `https://notifyr.grinik.pl/.well-known/apple-app-site-association`
- Android: `https://notifyr.grinik.pl/.well-known/assetlinks.json`

These files contain placeholder values that must be updated with the actual Team ID, Bundle ID, package name, and SHA256 fingerprint when the mobile apps are configured.

---

## Design System

The mobile app should match the visual identity of the existing web application. The web app uses Tailwind CSS v4 with a clean, modern, minimalist aesthetic.

### Brand & Logo

- **App Icon**: Bell notification icon (Heroicon style) on an indigo rounded background
- **App Name**: "Notifyr" in bold sans-serif
- **Icon style**: Heroicons (stroke-based SVGs), sizes `16px`, `20px`, `24px`

### Font

- **Family**: `Instrument Sans` (from Bunny Fonts), fallback to system sans-serif
- **Weights**: 400 (regular), 500 (medium), 600 (semibold)

### Color Palette

**Primary (Indigo)**:
| Token | Hex | Usage |
|-------|-----|-------|
| `indigo-50` | `#eef2ff` | Active nav background, light highlights |
| `indigo-100` | `#e0e7ff` | Focus ring |
| `indigo-600` | `#4f46e5` | Primary buttons, links, logo background |
| `indigo-700` | `#4338ca` | Button hover, active nav text |

**Header gradient**: `linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)` (indigo to purple)

**Status Colors**:
| Status | Background | Text | Dot/Icon |
|--------|-----------|------|----------|
| Active / Done | `green-50` `#f0fdf4` | `green-700` `#15803d` | `green-500` `#22c55e` |
| Pending | `blue-50` `#eff6ff` | `blue-700` `#1d4ed8` | `blue-500` |
| Postponed | `amber-50` `#fffbeb` | `amber-600` `#d97706` | `yellow-500` |
| Cancelled / Inactive | `gray-100` `#f3f4f6` | `gray-600` `#4b5563` | `gray-400` |
| Error / Danger | `red-50` `#fef2f2` | `red-600` `#dc2626` | `red-500` |

**Neutrals (Gray)**:
| Token | Hex | Usage |
|-------|-----|-------|
| `gray-50` | `#f9fafb` | Page background |
| `gray-100` | `#f3f4f6` | Hover states, inactive badges |
| `gray-200` | `#e5e7eb` | Borders, dividers |
| `gray-300` | `#d1d5db` | Subtle borders, input borders |
| `gray-400` | `#9ca3af` | Muted text, placeholder, inactive icons |
| `gray-500` | `#6b7280` | Secondary text |
| `gray-700` | `#374151` | Labels, medium-weight text |
| `gray-900` | `#111827` | Headings, primary text |

**Surfaces**: `white` for cards and panels, `gray-50` for page background.

### Typography Scale

| Element | Size | Weight | Color |
|---------|------|--------|-------|
| Page heading | 24px (`text-2xl`) | semibold | `gray-900` |
| Section heading | 20px (`text-xl`) | semibold | `gray-900` |
| Body text | 14px (`text-sm`) | regular | `gray-600` - `gray-700` |
| Small/caption | 12px (`text-xs`) | medium | `gray-500` |
| Table headers | 12px (`text-xs`) | semibold, uppercase, tracking-wider | `gray-500` |
| Button text | 14px (`text-sm`) | bold (mobile), semibold (desktop) | varies |

### Spacing & Sizing

- **Card padding**: `24px` (mobile), `16px`-`24px` (desktop)
- **Input height**: `44px` (padding `10px 14px`)
- **Button height**: `48px` on mobile (large touch targets), `40px` on desktop
- **Gaps between elements**: `24px` (sections), `12px` (related items), `8px` (tight groups)
- **Page horizontal padding**: `16px` (mobile), `24px`+ (desktop)

### Border Radius

| Element | Radius |
|---------|--------|
| Cards (mobile) | `24px` (`rounded-3xl`) |
| Cards (desktop) | `16px` (`rounded-2xl`) |
| Buttons / Inputs | `8px` (`rounded-lg`) |
| Logo / Tab icons | `12px`-`16px` (`rounded-xl` / `rounded-2xl`) |
| Badges / Pills | full (`rounded-full`) |

### Shadows

- **Cards**: `0 1px 3px rgba(0,0,0,0.05), 0 10px 25px -5px rgba(99,102,241,0.08), 0 0 0 1px rgba(0,0,0,0.02)` (subtle indigo-tinted shadow)
- **Primary buttons**: `0 1px 3px 0 rgba(79,70,229,0.3), 0 1px 2px -1px rgba(79,70,229,0.3)` (indigo shadow)
- **Elevated elements**: standard `shadow-sm` or `shadow-lg`

### Component Patterns

**Buttons**:
- Primary: `bg-indigo-600`, white text, indigo shadow. Hover: `bg-indigo-700`.
- Secondary: white background, `border gray-300`, `text-gray-700`. Hover: `bg-gray-50`.
- Danger: `text-red-600`. Hover: `bg-red-50`.
- Mobile buttons: full-width, `48px` height, bold font, `active:scale(0.98)` press effect.

**Cards**:
- White background, light border (`gray-100` mobile / `gray-200` desktop), subtle shadow.
- Generous padding (24px). Larger radius on mobile (24px) vs desktop (16px).

**Badges / Status Pills**:
- Rounded full, small padding (`px-2.5 py-1`).
- Active badge uses gradient: `from-green-500 to-emerald-500` with white text + shimmer animation.
- Inactive badge: `bg-gray-100 border-gray-200 text-gray-600`.

**Form Inputs**:
- Border: `gray-300`, background: white.
- Focus: `border-indigo-400` with `ring-2 ring-indigo-100`.
- Error: `border-red-300 bg-red-50`, error text in `red-600` below input.

**Lists / Tables**:
- Mobile: vertical card stack with `gap-12px`.
- Desktop: table with `hover:bg-gray-50/60` row highlighting, `divide-y divide-gray-50`.

**Navigation**:
- Top bar: sticky, white background, `border-bottom gray-100`, `64px` height.
- Mobile bottom tabs: fixed, white, `border-top gray-200`, safe area padding.
- Active tab: indigo gradient icon background with shadow. Inactive: `gray-400` icon.

**Empty States**:
- Centered card with icon, heading, description, and CTA button.
- Icon in a light indigo circle (`bg-indigo-50`).

### Animations

- **Slide up**: Fade in + translate from bottom (0.5s ease). Used for page content on load.
- **Shimmer**: Moving light gradient across badges (2.5s infinite). Used on active status badges.
- **Button pulse**: Expanding indigo box-shadow (2s infinite). Used on primary CTAs.
- **Press feedback**: `active:scale(0.98)` on mobile buttons and cards.
- **Transitions**: `200ms ease` for color, background, opacity, and transform changes.

### Dark Mode

Not implemented. The app uses a light-only theme. Consider adding dark mode support for the mobile app as a future enhancement.

### Key Design Principles

1. **Clean & minimal** — generous whitespace, no visual clutter.
2. **Touch-friendly** — minimum 48px tap targets, full-width buttons on mobile.
3. **Status at a glance** — color-coded badges communicate state instantly (green=active, blue=pending, amber=postponed, gray=inactive).
4. **Subtle depth** — light shadows with indigo tint for brand consistency.
5. **Micro-interactions** — press scale, shimmer, and slide-up animations for a polished feel.
6. **Mobile-first** — cards over tables, larger radii, bolder shadows on small screens.
