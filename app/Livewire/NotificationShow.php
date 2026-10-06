<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Livewire\Concerns\FormatsDuration;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class NotificationShow extends Component
{
    use AuthorizesRequests, FormatsDuration;

    public Notification $notification;

    public Collection $upcomingEvents;

    public Collection $recentEvents;

    public int $doneCount = 0;

    public int $skippedCount = 0;

    public int $missedCount = 0;

    public int $totalCount = 0;

    public ?int $avgMarkMinutes = null;

    public int $recentEventsLimit = 5;

    public string $backUrl = '';

    public bool $confirmingDelete = false;

    public function mount(Notification $notification): void
    {
        $this->authorize('view', $notification);

        $this->notification = $notification;
        $back = request()->query('back', '');
        $this->backUrl = ($back && str_starts_with($back, url('/'))) ? $back : route('notifications.index');
        $this->loadEvents();
    }

    public function toggleActive(): void
    {
        $this->authorize('update', $this->notification);

        $this->notification->update([
            'is_active' => ! $this->notification->is_active,
        ]);
    }

    public function markDone(string $eventId): void
    {
        $event = $this->notification->events()->findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->loadEvents();
    }

    public function markNow(): void
    {
        $this->authorize('update', $this->notification);

        $this->notification->events()->create([
            'user_id' => Auth::id(),
            'scheduled_at' => now(),
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->loadEvents();
    }

    public function markCancelled(string $eventId): void
    {
        $event = $this->notification->events()->findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        $this->loadEvents();
    }

    public function clearStatus(string $eventId): void
    {
        $event = $this->notification->events()->findOrFail($eventId);
        $this->authorize('update', $event);

        $event->update([
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);

        $this->loadEvents();
    }

    public function loadMoreRecent(): void
    {
        $this->recentEventsLimit += 10;
        $this->loadEvents();
    }

    public function repeatCourse(): void
    {
        $this->authorize('update', $this->notification);

        $clone = $this->notification->replicate();
        $clone->starts_at = now()->startOfDay();
        $clone->ends_at = $this->repeatCourseEndDate();
        $clone->is_active = true;
        $clone->save();

        $this->redirect(route('notifications.show', $clone));
    }

    public function repeatCourseEndDate(): ?Carbon
    {
        if (! $this->notification->starts_at || ! $this->notification->ends_at) {
            return null;
        }

        $durationDays = $this->notification->starts_at->diffInDays($this->notification->ends_at);

        return now()->startOfDay()->addDays($durationDays);
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
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
        $now = now();

        $this->upcomingEvents = $this->notification->events()
            ->where('scheduled_at', '>=', $todayStart)
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        $this->recentEvents = $this->notification->events()
            ->where(fn ($q) => $q
                ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled])
                ->orWhere(fn ($q) => $q->where('status', EventStatus::Pending)->where('scheduled_at', '<=', $now))
            )
            ->orderByDesc('scheduled_at')
            ->limit($this->recentEventsLimit)
            ->get();

        $this->totalCount = $this->notification->events()->count();
        $this->doneCount = $this->notification->events()->where('status', EventStatus::Done)->count();
        $this->skippedCount = $this->notification->events()->where('status', EventStatus::Cancelled)->count();
        $this->missedCount = $this->notification->events()
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '<=', $now)
            ->count();

        $doneEvents = $this->notification->events()
            ->where('status', EventStatus::Done)
            ->whereNotNull('completed_at')
            ->get(['scheduled_at', 'completed_at']);

        $this->avgMarkMinutes = $doneEvents->isNotEmpty()
            ? (int) round($doneEvents->avg(fn ($event) => abs($event->completed_at->diffInMinutes($event->scheduled_at))))
            : null;
    }
}
