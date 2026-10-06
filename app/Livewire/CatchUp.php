<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\NotificationEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CatchUp extends Component
{
    public Collection $missedEvents;

    public int $missedPerPage = 10;

    public int $missedTotal = 0;

    public bool $confirmingSkipAll = false;

    /** @var array{eventId?: string, eventIds?: array<int, string>, previousStatus: string, label: string}|null */
    public ?array $undoState = null;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadMoreMissed(): void
    {
        $this->missedPerPage += 10;
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
        ]);

        $this->undoState = null;
        $this->missedPerPage = 10;
        $this->loadEvents();
    }

    /**
     * @param  array<int, string>  $eventIds
     */
    public function restoreBulk(array $eventIds, string $status): void
    {
        $restoredStatus = EventStatus::tryFrom($status);

        if (! $restoredStatus) {
            return;
        }

        $events = NotificationEvent::whereIn('id', $eventIds)->get();

        foreach ($events as $event) {
            $this->authorize('update', $event);
        }

        NotificationEvent::whereIn('id', $eventIds)->update([
            'status' => $restoredStatus,
            'completed_at' => $restoredStatus === EventStatus::Pending ? null : now(),
        ]);

        $this->undoState = null;
        $this->missedPerPage = 10;
        $this->loadEvents();
    }

    public function confirmSkipAll(): void
    {
        $this->confirmingSkipAll = true;
    }

    public function cancelSkipAll(): void
    {
        $this->confirmingSkipAll = false;
    }

    public function skipAllMissed(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $todayStart = Carbon::now($tz)->startOfDay()->utc();

        $eventIds = $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart)
            ->where('status', EventStatus::Pending)
            ->pluck('id');

        NotificationEvent::whereIn('id', $eventIds)->update([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        $this->confirmingSkipAll = false;
        $this->undoState = [
            'eventIds' => $eventIds->all(),
            'previousStatus' => EventStatus::Pending->value,
            'label' => trans_choice(':count reminder skipped|:count reminders skipped', $eventIds->count(), ['count' => $eventIds->count()]),
        ];
        $this->missedPerPage = 10;
        $this->loadEvents();
    }

    public function markDayDone(string $dateKey): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $dayStart = Carbon::createFromFormat('Y-m-d', $dateKey, $tz)->startOfDay()->utc();
        $dayEnd = Carbon::createFromFormat('Y-m-d', $dateKey, $tz)->endOfDay()->utc();

        $eventIds = $user->notificationEvents()
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->where('status', EventStatus::Pending)
            ->pluck('id');

        NotificationEvent::whereIn('id', $eventIds)->update([
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->undoState = [
            'eventIds' => $eventIds->all(),
            'previousStatus' => EventStatus::Pending->value,
            'label' => trans_choice(':count reminder marked done|:count reminders marked done', $eventIds->count(), ['count' => $eventIds->count()]),
        ];
        $this->missedPerPage = 10;
        $this->loadEvents();
    }

    public function render(): View
    {
        return view('livewire.catch-up');
    }

    private function loadEvents(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $todayStart = Carbon::now($tz)->startOfDay();

        $this->missedTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart->copy()->utc())
            ->where('status', EventStatus::Pending)
            ->count();

        $this->missedEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart->copy()->utc())
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at', 'desc')
            ->limit($this->missedPerPage)
            ->get();
    }
}
