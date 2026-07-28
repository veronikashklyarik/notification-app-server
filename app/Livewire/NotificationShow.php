<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class NotificationShow extends Component
{
    use AuthorizesRequests;

    public Notification $notification;

    public Collection $events;

    public Collection $recentEvents;

    public int $eventsPerPage = 5;

    public int $recentPerPage = 5;

    public int $eventsTotal = 0;

    public int $recentTotal = 0;

    public string $backUrl = '';

    public function mount(Notification $notification): void
    {
        $this->authorize('view', $notification);

        $this->notification = $notification;
        $back = request()->query('back', '');
        $this->backUrl = ($back && str_starts_with($back, url('/'))) ? $back : route('notifications.index');
        $this->loadEvents();
    }

    public function loadMoreEvents(): void
    {
        $this->eventsPerPage += 5;
        $this->loadEvents();
    }

    public function loadMoreRecent(): void
    {
        $this->recentPerPage += 5;
        $this->loadEvents();
    }

    public function toggleActive(): void
    {
        $this->authorize('update', $this->notification);

        $this->notification->update([
            'is_active' => ! $this->notification->is_active,
        ]);

        $backParam = $this->backUrl ? '?back='.urlencode($this->backUrl) : '';
        $this->redirect(route('notifications.show', $this->notification).$backParam);
    }

    public function confirmDelete(): void
    {
        $this->dispatch('show-delete-confirmation');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->notification);

        $this->notification->delete();

        session()->flash('success', __('Reminder deleted.'));

        $this->redirect(route('notifications.index'));
    }

    public function render(): View
    {
        return view('livewire.notification-show');
    }

    private function loadEvents(): void
    {
        $user = Auth::user();
        $tz = $user->timezone ?? 'UTC';
        $todayStart = Carbon::now($tz)->startOfDay()->utc();

        $this->eventsTotal = $this->notification->events()
            ->where('scheduled_at', '>=', $todayStart)
            ->where('status', EventStatus::Pending)
            ->count();

        $this->events = $this->notification->events()
            ->where('scheduled_at', '>=', $todayStart)
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->limit($this->eventsPerPage)
            ->get();

        $this->recentTotal = $this->notification->events()
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->count();

        $this->recentEvents = $this->notification->events()
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->orderByDesc('scheduled_at')
            ->limit($this->recentPerPage)
            ->get();
    }
}
