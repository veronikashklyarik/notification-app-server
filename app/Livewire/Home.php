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
class Home extends Component
{
    public array $stats = [];

    public Collection $todayEvents;

    public int $todayPerPage = 10;

    public int $todayTotal = 0;

    public Collection $missedEvents;

    public int $missedPerPage = 10;

    public int $missedTotal = 0;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadMore(): void
    {
        $this->todayPerPage += 10;
        $this->loadEvents();
    }

    public function loadMoreMissed(): void
    {
        $this->missedPerPage += 10;
        $this->loadEvents();
    }

    public function refresh(): void
    {
        $this->todayPerPage = 10;
        $this->missedPerPage = 10;
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

    public function completeAllMissed(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $todayStart = Carbon::now($tz)->startOfDay()->utc();

        $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart)
            ->where('status', EventStatus::Pending)
            ->update([
                'status' => EventStatus::Done,
                'completed_at' => now(),
            ]);

        $this->redirect(route('home'));
    }

    public function skipAllMissed(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $todayStart = Carbon::now($tz)->startOfDay()->utc();

        $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart)
            ->where('status', EventStatus::Pending)
            ->update([
                'status' => EventStatus::Cancelled,
                'completed_at' => now(),
            ]);

        $this->redirect(route('home'));
    }

    public function render(): View
    {
        return view('livewire.home');
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

        $this->missedTotal = $user->notificationEvents()
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart)
            ->where('status', EventStatus::Pending)
            ->count();

        $this->stats = [
            'total_notifications' => $user->reminders()->count(),
            'active_notifications' => $user->reminders()->where('is_active', true)->count(),
            'today_events' => $this->todayTotal,
        ];

        $this->todayEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->limit($this->todayPerPage)
            ->get();

        $this->missedEvents = $user->notificationEvents()
            ->with('notification')
            ->whereHas('notification')
            ->where('scheduled_at', '<', $todayStart)
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at', 'desc')
            ->limit($this->missedPerPage)
            ->get();
    }
}
