# Changelog

All notable changes to this project will be documented in this file.

## [0.0.2.0] - 2026-07-15

### Added

- **UPCOMING preview for all schedule types** — the schedule form now shows a live "Upcoming" block for every schedule type: every-day, every-N-days, specific days of the week, cyclical (weekly, monthly each/on-the, yearly, and days with use/pause cycle), and specific dates. The block reacts immediately to any field change.
- **Duplicate time prevention** — adding a new time slot (via the "+ Time" button) always picks the next unused hour instead of blindly appending `08:00`. Works across global times, per-weekday times, and per-date times.
- **Duplicate time correction on direct edit** — if a user manually types a time that already exists in the same group, the value is automatically replaced with the next free hourly slot. Backed by Livewire lifecycle hooks (`updatedTimes`, `updatedWeekDays`, `updatedSpecificDates`) in both Create and Edit components.

### Fixed

- Monthly "on the…" and yearly "on specific weekday" selects now use `wire:model.live` so the UPCOMING preview updates immediately when position or weekday is changed.
- `cyclical_year_day` was missing from the schedule-regeneration field list in `NotificationEvent`, causing yearly-by-day events not to regenerate on edit.
- Global times section was incorrectly visible for `week_days` and `specific_dates` schedule types.
- Various design and UX polish: sorted time display, real dates in frequency labels, animated transitions, ARIA labels on interactive controls.
- Timezone bug in yearly event generation.
- Weekly upcoming preview and always-visible day hint.

## [0.0.1.0] - 2026-07-01

### Added

- **Reminder push notifications** — users can now choose to receive repeat push notifications for events that remain pending. Configure the interval (every 15 min, 30 min, 1 hour, 2, 4, 8, or 24 hours) from the profile settings page. Reminders stop automatically once an event is marked done, cancelled, or postponed.
- New `app:send-reminder-notifications` Artisan command runs every minute and dispatches reminders respecting each user's configured interval.
- `reminder_interval` field on the user profile for storing the chosen reminder cadence.
- `reminded_at` field on notification events for tracking when the last reminder was sent.
- Composite index on `(status, reminded_at)` for efficient reminder queries at scale.

### For contributors

- Pre-filter cutoff in the reminder command is derived from `min(array_keys(REMINDER_INTERVALS))` rather than a hardcoded literal, keeping the two in sync automatically.
