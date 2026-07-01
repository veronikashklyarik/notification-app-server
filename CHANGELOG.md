# Changelog

All notable changes to this project will be documented in this file.

## [0.0.1.0] - 2026-07-01

### Added

- **Reminder push notifications** — users can now choose to receive repeat push notifications for events that remain pending. Configure the interval (every 15 min, 30 min, 1 hour, 2, 4, 8, or 24 hours) from the profile settings page. Reminders stop automatically once an event is marked done, cancelled, or postponed.
- New `app:send-reminder-notifications` Artisan command runs every minute and dispatches reminders respecting each user's configured interval.
- `reminder_interval` field on the user profile for storing the chosen reminder cadence.
- `reminded_at` field on notification events for tracking when the last reminder was sent.
- Composite index on `(status, reminded_at)` for efficient reminder queries at scale.

### Changed

- Pre-filter cutoff in the reminder command is now derived from the minimum value in the `REMINDER_INTERVALS` constant rather than a hardcoded literal, keeping the two in sync automatically.
