<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Livewire\Concerns\FormatsDuration;
use App\Models\NotificationEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Home extends Component
{
    use FormatsDuration;

    #[Url(as: 'event')]
    public ?string $highlightEventId = null;

    public Collection $todayEvents;

    public int $todayTotalCount = 0;

    public int $todayDoneCount = 0;

    public int $todaySkippedCount = 0;

    public ?int $nextReminderDays = null;

    public ?string $nextReminderName = null;

    public ?string $nextReminderTime = null;

    public int $missedTotal = 0;

    public int $weekDoneCount = 0;

    public int $weekMarksCount = 0;

    public int $weekCleanDays = 0;

    /** @var array<int, array{date: string, marks: int, ratio: float|null, isToday: bool, initial: string}> */
    public array $weekBars = [];

    public Collection $activeReminders;

    /** @var array{eventId: string, previousStatus: string, label: string}|null */
    public ?array $undoState = null;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function refresh(): void
    {
        $this->loadEvents();
    }

    public function markDone(string $eventId): void
    {
        $event = NotificationEvent::with('notification')->findOrFail($eventId);
        $this->authorize('update', $event);
        $previousStatus = $event->status;

        $event->update([
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->dispatch('dismiss-push-notification', eventId: $event->id);
        $this->undoState = [
            'eventId' => $event->id,
            'previousStatus' => $previousStatus->value,
            'label' => __(':name marked done', ['name' => $event->notification->name]),
        ];
        $this->loadEvents();
    }

    public function markPostponed(string $eventId): void
    {
        $event = NotificationEvent::with('notification')->findOrFail($eventId);
        $this->authorize('update', $event);
        $previousStatus = $event->status;

        $snoozeUntil = now()->addMinutes(15);
        $history = $event->postpone_history ?? [];
        $history[] = [
            'from' => $event->scheduled_at->toIso8601String(),
            'to' => $snoozeUntil->toIso8601String(),
            'at' => now()->toIso8601String(),
        ];

        $event->update([
            'status' => EventStatus::Postponed,
            'postponed_until' => $snoozeUntil,
            'postpone_history' => $history,
        ]);

        $this->dispatch('dismiss-push-notification', eventId: $event->id);
        $this->undoState = [
            'eventId' => $event->id,
            'previousStatus' => $previousStatus->value,
            'label' => __(':name — reminding again in 15 min', ['name' => $event->notification->name]),
        ];
        $this->loadEvents();
    }

    public function markCancelled(string $eventId): void
    {
        $event = NotificationEvent::with('notification')->findOrFail($eventId);
        $this->authorize('update', $event);
        $previousStatus = $event->status;

        $event->update([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        $this->dispatch('dismiss-push-notification', eventId: $event->id);
        $this->undoState = [
            'eventId' => $event->id,
            'previousStatus' => $previousStatus->value,
            'label' => __(':name skipped', ['name' => $event->notification->name]),
        ];
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

    public function restoreStatus(string $eventId, string $status): void
    {
        $event = NotificationEvent::findOrFail($eventId);
        $this->authorize('update', $event);

        $restoredStatus = EventStatus::tryFrom($status);

        if (! $restoredStatus) {
            return;
        }

        $event->update([
            'status' => $restoredStatus,
            'completed_at' => $restoredStatus === EventStatus::Pending ? null : ($event->completed_at ?? now()),
            'postponed_until' => null,
        ]);

        $this->undoState = null;
        $this->loadEvents();
    }

    public function render(): View
    {
        return view('livewire.home');
    }

    private function loadEvents(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';

        $todayStart = Carbon::now($tz)->startOfDay();
        $todayEnd = Carbon::now($tz)->endOfDay();

        $this->todayEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$todayStart->copy()->utc(), $todayEnd->copy()->utc()])
            ->orderBy('scheduled_at')
            ->get();

        $this->todayTotalCount = $this->todayEvents->count();
        $this->todayDoneCount = $this->todayEvents->where('status', EventStatus::Done)->count();
        $this->todaySkippedCount = $this->todayEvents->where('status', EventStatus::Cancelled)->count();

        $this->missedTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart->copy()->utc())
            ->where('status', EventStatus::Pending)
            ->count();

        $nextEvent = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->where('scheduled_at', '>', $todayEnd->copy()->utc())
            ->orderBy('scheduled_at')
            ->first();

        $this->nextReminderDays = $nextEvent
            ? (int) $todayStart->diffInDays($nextEvent->scheduled_at->copy()->setTimezone($tz)->startOfDay())
            : null;
        $this->nextReminderName = $nextEvent?->notification->name;
        $this->nextReminderTime = $nextEvent?->scheduled_at->copy()->setTimezone($tz)->format('H:i');

        $this->weekBars = [];
        $this->weekDoneCount = 0;
        $this->weekMarksCount = 0;
        $this->weekCleanDays = 0;

        // Deliberately not shared with EventList::aggregateRange(): this is a glanceable
        // dashboard mini-chart, not the detailed History record. "Missed" here means
        // "anything that isn't Done or still-Pending" (so Cancelled and Postponed both
        // count as a miss), and it ignores overdue-but-still-Pending events entirely —
        // History's definition is the precise one (Cancelled tracked separately, overdue
        // Pending = missed). The two charts can show different numbers for the same week
        // by design. Postponed events aren't surfaced by either chart's feed today.
        $weekStart = $todayStart->copy()->subDays(6);
        $weekStatusesByDay = $user->notificationEvents()
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$weekStart->copy()->utc(), $todayEnd->copy()->utc()])
            ->get(['status', 'scheduled_at'])
            ->groupBy(fn ($event) => $event->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d'));

        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $todayStart->copy()->subDays($i);
            $dayStatuses = ($weekStatusesByDay->get($dayStart->format('Y-m-d')) ?? collect())->pluck('status');

            $done = $dayStatuses->filter(fn ($status) => $status === EventStatus::Done)->count();
            $missed = $dayStatuses->filter(fn ($status) => $status !== EventStatus::Done && $status !== EventStatus::Pending)->count();
            $marks = $done + $missed;

            $this->weekDoneCount += $done;
            $this->weekMarksCount += $marks;
            if ($marks > 0 && $missed === 0) {
                $this->weekCleanDays++;
            }

            $this->weekBars[] = [
                'date' => $dayStart->format('Y-m-d'),
                'marks' => $marks,
                'ratio' => $marks > 0 ? $done / $marks : null,
                'isToday' => $i === 0,
                'initial' => mb_substr($dayStart->translatedFormat('D'), 0, 1),
            ];
        }

        $this->activeReminders = $user->reminders()
            ->where('is_active', true)
            ->latest()
            ->get()
            ->reject(fn ($notification) => $notification->isEnded())
            ->take(5)
            ->values();

        $nextEvents = NotificationEvent::query()
            ->whereIn('notification_id', $this->activeReminders->pluck('id'))
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('notification_id');

        $this->activeReminders->each(function ($notification) use ($nextEvents) {
            $notification->setRelation('nextUpcomingEvent', $nextEvents->get($notification->id)?->first());
        });
    }
}
