<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\NotificationEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class EventList extends Component
{
    public Collection $todayEvents;

    public Collection $upcomingEvents;

    public Collection $recentEvents;

    public int $todayPerPage = 10;

    public int $upcomingPerPage = 10;

    public int $recentPerPage = 10;

    public int $todayTotal = 0;

    public int $upcomingTotal = 0;

    public int $recentTotal = 0;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function refresh(): void
    {
        $this->todayPerPage = 10;
        $this->upcomingPerPage = 10;
        $this->recentPerPage = 10;
        $this->loadEvents();
    }

    public function loadMoreToday(): void
    {
        $this->todayPerPage += 10;
        $this->loadEvents();
    }

    public function loadMoreUpcoming(): void
    {
        $this->upcomingPerPage += 10;
        $this->loadEvents();
    }

    public function loadMoreRecent(): void
    {
        $this->recentPerPage += 10;
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

        $this->loadEvents();
    }

    public function render(): View
    {
        return view('livewire.event-list');
    }

    private function loadEvents(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';

        $todayStart = Carbon::now($tz)->startOfDay()->utc();
        $todayEnd = Carbon::now($tz)->endOfDay()->utc();

        $this->todayTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->where('status', EventStatus::Pending)
            ->count();

        $this->todayEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->limit($this->todayPerPage)
            ->get();

        $this->upcomingTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '>', $todayEnd)
            ->where('status', EventStatus::Pending)
            ->count();

        $this->upcomingEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->where('scheduled_at', '>', $todayEnd)
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->limit($this->upcomingPerPage)
            ->get();

        $this->recentTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->count();

        $this->recentEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->orderByDesc('scheduled_at')
            ->limit($this->recentPerPage)
            ->get();
    }
}
