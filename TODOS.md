# TODOS

## Deferred from autoplan review (2026-06-24)

### Phase 2 (post PWA migration)
- [x] Avatar upload (profile settings) **Completed:** 2026-06-26
- [x] E2E browser tests (Pest v4) for critical paths: login → home, create notification, mark event done

### Future scope
- [x] Web Push API notifications **Completed:** 2026-06-29
- [ ] Real-time Livewire broadcasts (upgrade from wire:poll)
- [ ] Dark mode support
- [ ] Accessibility (WCAG compliance)
- [ ] Offline queue/sync (service worker background sync)

## Deferred from /plan-eng-review (2026-10-06)

### Account deletion's restore promise is false
- **What:** `Profile::deleteAccount()`'s confirmation dialog says "Sign in within 30 days to restore them — after that they're deleted for good." Neither half is true.
- **Why:** `User::delete()` soft-deletes, but Laravel's `EloquentUserProvider::retrieveByCredentials()` (used by `Auth::attempt()`) respects the model's `SoftDeletingScope`, so a soft-deleted user categorically cannot sign back in — there is no restore path anywhere in the app (`grep -rn 'restore(\|withTrashed\|onlyTrashed' app/` only hits `Notification`/`NotificationEvent`). And nothing ever hard-deletes the row either: only `Notification` has a `prunable()` schedule, `User` doesn't — so the account becomes a permanent zombie that also blocks the email from ever being reused.
- **Pros:** Either direction fixes a real false promise to users about their own data; building the restore flow also gives a genuine "oops, changed my mind" grace period.
- **Cons:** The real fix (restore-on-login + `User::prunable()`, mirroring `Notification`'s existing pattern) is ~2-3h of work, not a one-liner. The cheap fix (rewrite the copy to say deletion is immediate) takes minutes but removes the grace-period feature from the product entirely.
- **Context:** `Notification::prunable()` (`app/Models/Notification.php:172`) is the existing pattern to mirror if building the real flow — same `MassPrunable` + `SoftDeletes` combo, just needs a restore-on-login hook added somewhere in the login flow (`LoginController::store()`) since that doesn't exist for any model today.
- **Depends on / blocked by:** None.

### Time-chip wire:key is index-based, shifts on removal
- **What:** `resources/views/livewire/partials/schedule-type-dates.blade.php` and `schedule-sheet-form.blade.php` key per-time `<input type="time">` chips by array index (`wire:key="...-time-{{ $timeIndex }}"`). Removing a time mid-list (`array_splice` in `HasScheduleFields::removeTimeFromDate()`/`removeTime()`) shifts every later index.
- **Why:** Displayed values always stay correct (server state re-renders fresh every request), so this isn't a data-correctness bug — but native `<input type="time">` carries browser-level focus/selection state that Livewire's morph treats specially, and a shifted key could misattribute that state to the wrong chip right after a removal.
- **Pros:** Keying on the time value instead of index would close this cleanly and match the stable-key pattern used elsewhere this session (day-of-week number, raw date string).
- **Cons:** Not a drop-in swap — duplicate times are a valid *transient* state while a user is actively editing (before the duplicate-time validation fires), so the key can't simply be the raw time value without a plan for that case (e.g. value + a stable per-row UUID generated on add).
- **Context:** Surfaced during the outside-voice pass of the 2026-10-06 eng review, alongside the (already-fixed) unkeyed-sibling morph bug in the same area — same root cause family (Livewire DOM morphing + unstable identity), different manifestation.
- **Depends on / blocked by:** None.

### Home→History `day=` deep link doesn't set `anchorDate`
- **What:** Home's week-bar links go to `/events?day=YYYY-MM-DD`; `EventList::mount()` sets `selectedDay` from that query param but never sets `anchorDate`. It only works today because Home's fixed "last 7 days" window happens to match History's default week window.
- **Why:** A stale/hand-edited URL — or any future change to either window's definition — could land on a day outside the displayed week/month, rendering every bar dimmed with none highlighted and no obvious way to tell why.
- **Pros:** Small, contained fix once prioritized: in `mount()`, when a `day` param resolves, also derive and set `anchorDate` so the displayed period always contains the selected day.
- **Cons:** Low value to fix proactively — requires a stale bookmark or tampered URL to hit, and the `#[Locked]` attribute added this review already closes the security-relevant angle (can't be exploited to read another user's data, just a confusing empty-looking view for your own).
- **Context:** `app/Livewire/EventList.php:58` (`mount()`), `weekEnd()`/`monthStart()` around line 190-205 for how `anchorDate` currently drives the displayed range.
- **Depends on / blocked by:** None.
