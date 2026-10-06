<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\NotificationEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The History page. Three pieces of state drive everything rendered:
 *
 *   period        'week' | 'month'            — which card shape is shown
 *   anchorDate    Y-m-d | null                 — null = current period; set = navigated away from it
 *   selectedDay   Y-m-d | null                 — null = aggregate view; set = single-day drill-down
 *
 * All three are #[Locked] — only the action methods below may change them, never
 * a direct client-side property write (see the EventListTest Locked-property tests).
 *
 *   mount() ──┬─ no ?day= ──────────────────────────────► period=week, anchorDate=null, selectedDay=null
 *             └─ ?day=YYYY-MM-DD (validated via parseDay) ─► selectedDay=that day (period/anchorDate untouched —
 *                                                             see the TODOS.md entry on this coupling)
 *
 *   setPeriod('week'|'month') ──► period=arg, anchorDate=null, selectedDay=null   (always resets to "now")
 *   prevPeriod() / nextPeriod() ──► anchorDate=<new period boundary>, selectedDay=null
 *   selectDay($date) ──► selectedDay = (selectedDay===$date ? null : $date)       (tap again = toggle off)
 *   clearSelectedDay() ──► selectedDay=null
 *
 * Every mutator above also dispatches 'history-day-changed', which the Alpine root
 * listens for to reset the client-only `historyFilter` — without that, a filter
 * chosen before drilling into a day stayed silently applied after leaving it.
 *
 * Feed scope (loadFeed()) follows selectedDay first, then period:
 *   selectedDay set   → feed = that single day
 *   period === week   → feed = the displayed 7-day window
 *   period === month  → feed = the displayed calendar month
 */
#[Layout('components.layouts.app')]
class EventList extends Component
{
    #[Locked]
    public string $period = 'week';

    /** End-of-range anchor (week) or any-day-in-month anchor (month); null = current period. */
    #[Locked]
    public ?string $anchorDate = null;

    #[Locked]
    public ?string $selectedDay = null;

    public bool $hasAnyHistory = false;

    public Collection $events;

    public int $weekDoneCount = 0;

    public int $weekSkippedCount = 0;

    public int $weekMissedCount = 0;

    public int $weekMarksCount = 0;

    public int $weekCleanDays = 0;

    public string $weekRangeLabel = '';

    public bool $weekCanGoNext = false;

    /** @var array<int, array{date: string, ratio: float|null, marks: int, isToday: bool, initial: string}> */
    public array $weekBars = [];

    public int $monthDoneCount = 0;

    public int $monthMarksCount = 0;

    public string $monthLabel = '';

    public bool $monthCanGoNext = false;

    /** @var array<int, array{day: int, date: string, ratio: float|null, marks: int, isToday: bool}|null> */
    public array $monthDays = [];

    public function mount(?string $day = null): void
    {
        $day ??= Request::query('day');

        $this->selectedDay = $this->parseDay($day)?->format('Y-m-d');

        $this->loadEvents();
    }

    public function refresh(): void
    {
        $this->loadEvents();
    }

    public function setPeriod(string $period): void
    {
        if (! in_array($period, ['week', 'month'], true)) {
            return;
        }

        $this->period = $period;
        $this->anchorDate = null;
        $this->selectedDay = null;
        $this->dispatch('history-day-changed');
        $this->loadEvents();
    }

    public function prevPeriod(): void
    {
        $tz = $this->timezone();

        $this->anchorDate = $this->period === 'week'
            ? $this->weekEnd($tz)->subDays(7)->format('Y-m-d')
            : $this->monthStart($tz)->subMonthNoOverflow()->format('Y-m-d');

        $this->selectedDay = null;
        $this->dispatch('history-day-changed');
        $this->loadEvents();
    }

    public function nextPeriod(): void
    {
        $tz = $this->timezone();
        $today = Carbon::now($tz)->startOfDay();

        if ($this->period === 'week') {
            if (! $this->weekCanGoNext) {
                return;
            }
            $end = $this->weekEnd($tz)->addDays(7)->min($today);
            $this->anchorDate = $end->format('Y-m-d');
        } else {
            if (! $this->monthCanGoNext) {
                return;
            }
            $next = $this->monthStart($tz)->addMonthNoOverflow();
            $this->anchorDate = $next->format('Y-m-d');
        }

        $this->selectedDay = null;
        $this->dispatch('history-day-changed');
        $this->loadEvents();
    }

    public function selectDay(string $date): void
    {
        $parsed = $this->parseDay($date);
        if (! $parsed) {
            return;
        }

        $date = $parsed->format('Y-m-d');
        $this->selectedDay = $this->selectedDay === $date ? null : $date;
        $this->dispatch('history-day-changed');
        $this->loadEvents();
    }

    public function clearSelectedDay(): void
    {
        $this->selectedDay = null;
        $this->dispatch('history-day-changed');
        $this->loadEvents();
    }

    public function markDone(string $eventId): void
    {
        $event = NotificationEvent::findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->dispatch('dismiss-push-notification', eventId: $event->id);
        $this->loadEvents();
    }

    public function markCancelled(string $eventId): void
    {
        $event = NotificationEvent::findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        $this->dispatch('dismiss-push-notification', eventId: $event->id);
        $this->loadEvents();
    }

    public function clearStatus(string $eventId): void
    {
        $event = NotificationEvent::findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);

        $this->loadEvents();
    }

    public function render(): View
    {
        return view('livewire.event-list');
    }

    private function timezone(): string
    {
        return Auth::user()->timezone ?? 'UTC';
    }

    /**
     * Validates a Y-m-d date string against the real calendar (rejects things
     * like 2026-13-45 that a bare regex would let through and Carbon would
     * otherwise silently roll over), returning null for anything invalid.
     */
    private function parseDay(?string $date): ?Carbon
    {
        if (! $date || ! Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $date, $this->timezone())->startOfDay();
    }

    private function weekEnd(string $tz): Carbon
    {
        $today = Carbon::now($tz)->startOfDay();

        if ($this->anchorDate) {
            return Carbon::createFromFormat('Y-m-d', $this->anchorDate, $tz)->startOfDay()->min($today);
        }

        return $today;
    }

    private function monthStart(string $tz): Carbon
    {
        if ($this->anchorDate) {
            return Carbon::createFromFormat('Y-m-d', $this->anchorDate, $tz)->startOfMonth();
        }

        return Carbon::now($tz)->startOfMonth();
    }

    private function loadEvents(): void
    {
        $user = Auth::user();
        $tz = $this->timezone();
        $now = now();

        $this->hasAnyHistory = $user->notificationEvents()
            ->whereHas('notification')
            ->where(fn ($q) => $q
                ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled])
                ->orWhere(fn ($q) => $q->where('status', EventStatus::Pending)->where('scheduled_at', '<=', $now))
            )
            ->exists();

        if ($this->period === 'week') {
            $this->loadWeek($user, $tz);
        } else {
            $this->loadMonth($user, $tz);
        }

        $this->loadFeed($user, $tz);
    }

    private function loadWeek(User $user, string $tz): void
    {
        $end = $this->weekEnd($tz);
        $start = $end->copy()->subDays(6);
        $today = Carbon::now($tz)->startOfDay();

        $agg = $this->aggregateRange($user, $start, $end, $tz);

        $this->weekDoneCount = $agg['totals']['done'];
        $this->weekSkippedCount = $agg['totals']['skipped'];
        $this->weekMissedCount = $agg['totals']['missed'];
        $this->weekMarksCount = $agg['totals']['marks'];
        $this->weekCleanDays = collect($agg['days'])
            ->filter(fn (array $d) => $d['marks'] > 0 && $d['skipped'] === 0 && $d['missed'] === 0)
            ->count();

        $this->weekRangeLabel = $start->translatedFormat('j M').' – '.$end->translatedFormat('j M');
        $this->weekCanGoNext = $end->lt($today);

        $bars = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $day = $agg['days'][$key] ?? null;

            $bars[] = [
                'date' => $key,
                'ratio' => $day['ratio'] ?? null,
                'marks' => $day['marks'] ?? 0,
                'isToday' => $cursor->equalTo($today),
                'initial' => mb_substr($cursor->translatedFormat('D'), 0, 1),
            ];
            $cursor->addDay();
        }
        $this->weekBars = $bars;
    }

    private function loadMonth(User $user, string $tz): void
    {
        $monthStart = $this->monthStart($tz);
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
        $today = Carbon::now($tz)->startOfDay();

        $agg = $this->aggregateRange($user, $monthStart, $monthEnd, $tz);

        $this->monthDoneCount = $agg['totals']['done'];
        $this->monthMarksCount = $agg['totals']['marks'];
        $this->monthLabel = $monthStart->translatedFormat('F Y');
        $this->monthCanGoNext = $monthStart->lt($today->copy()->startOfMonth());

        $leadingBlanks = $monthStart->dayOfWeekIso - 1;
        $cells = array_fill(0, $leadingBlanks, null);

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $key = $cursor->format('Y-m-d');
            $day = $agg['days'][$key] ?? null;

            $cells[] = [
                'day' => $cursor->day,
                'date' => $key,
                'ratio' => $day['ratio'] ?? null,
                'marks' => $day['marks'] ?? 0,
                'isToday' => $cursor->equalTo($today),
            ];
            $cursor->addDay();
        }
        $this->monthDays = $cells;
    }

    /**
     * Fetches every event in [$startLocal, $endLocal] (inclusive, local days) in one
     * query and groups it by local date, so week bars and the month calendar are each
     * built from a single round trip.
     *
     * Deliberately not shared with Home::loadEvents()'s week-bar aggregation: this is
     * the precise definition (Cancelled tracked separately from overdue-Pending/missed)
     * used for the detailed History record, vs. Home's coarser "anything but Done or
     * still-Pending counts as a miss" used for its glanceable dashboard mini-chart. The
     * two can disagree on the same day by design. Postponed events aren't surfaced by
     * either feed today.
     *
     * @return array{days: array<string, array{done: int, skipped: int, missed: int, marks: int, ratio: float|null}>, totals: array{done: int, skipped: int, missed: int, marks: int}}
     */
    private function aggregateRange(User $user, Carbon $startLocal, Carbon $endLocal, string $tz): array
    {
        $now = now();

        $events = $user->notificationEvents()
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$startLocal->copy()->utc(), $endLocal->copy()->endOfDay()->utc()])
            ->get(['status', 'scheduled_at']);

        $byDay = $events->groupBy(fn ($e) => $e->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d'));

        $days = [];
        $totals = ['done' => 0, 'skipped' => 0, 'missed' => 0, 'marks' => 0];

        foreach ($byDay as $dateKey => $dayEvents) {
            $done = $dayEvents->filter(fn ($e) => $e->status === EventStatus::Done)->count();
            $skipped = $dayEvents->filter(fn ($e) => $e->status === EventStatus::Cancelled)->count();
            $missed = $dayEvents->filter(fn ($e) => $e->status === EventStatus::Pending && $e->scheduled_at <= $now)->count();
            $marks = $done + $skipped + $missed;

            $days[$dateKey] = [
                'done' => $done,
                'skipped' => $skipped,
                'missed' => $missed,
                'marks' => $marks,
                'ratio' => $marks > 0 ? $done / $marks : null,
            ];

            $totals['done'] += $done;
            $totals['skipped'] += $skipped;
            $totals['missed'] += $missed;
            $totals['marks'] += $marks;
        }

        return ['days' => $days, 'totals' => $totals];
    }

    private function loadFeed(User $user, string $tz): void
    {
        $now = now();

        if ($this->selectedDay) {
            $start = Carbon::createFromFormat('Y-m-d', $this->selectedDay, $tz)->startOfDay();
            $end = $start->copy()->endOfDay();
        } elseif ($this->period === 'week') {
            $end = $this->weekEnd($tz);
            $start = $end->copy()->subDays(6);
            $end = $end->copy()->endOfDay();
        } else {
            $start = $this->monthStart($tz);
            $end = $start->copy()->endOfMonth()->endOfDay();
        }

        $this->events = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$start->copy()->utc(), $end->copy()->utc()])
            ->where(fn ($q) => $q
                ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled])
                ->orWhere(fn ($q) => $q->where('status', EventStatus::Pending)->where('scheduled_at', '<=', $now))
            )
            ->orderByDesc('scheduled_at')
            ->get();
    }
}
