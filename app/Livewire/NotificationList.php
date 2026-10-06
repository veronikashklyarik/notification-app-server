<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\NotificationEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class NotificationList extends Component
{
    use AuthorizesRequests;

    public Collection $notifications;

    public int $allCount = 0;

    public int $activeCount = 0;

    public int $pausedCount = 0;

    public int $endedCount = 0;

    public ?int $pendingDeleteId = null;

    public ?string $pendingDeleteName = null;

    public int $pendingDeleteMarksCount = 0;

    /** @var array<int, string> */
    public array $notificationBuckets = [];

    /** @var array{notificationId: int, label: string}|null */
    public ?array $undoState = null;

    public bool $sheetOpen = false;

    public string $sheetMode = 'create';

    public ?int $sheetNotificationId = null;

    public ?string $sheetTemplate = null;

    public function mount(): void
    {
        $this->loadNotifications();

        $created = session('createdReminder');
        if (is_array($created) && isset($created['id'], $created['name'])) {
            $this->undoState = [
                'notificationId' => $created['id'],
                'label' => __(':name created', ['name' => $created['name']]),
            ];
        }
    }

    public function refresh(): void
    {
        $this->loadNotifications();
    }

    public function toggleActive(int $notificationId): void
    {
        $notification = Auth::user()->reminders()->find($notificationId);

        if (! $notification) {
            return;
        }

        $this->authorize('update', $notification);

        $notification->update([
            'is_active' => ! $notification->is_active,
        ]);

        $this->dispatch('swipe-reset', except: -1);

        $this->loadNotifications();
    }

    public function confirmDelete(int $notificationId): void
    {
        $notification = Auth::user()->reminders()->find($notificationId);

        if (! $notification) {
            return;
        }

        $this->pendingDeleteId = $notificationId;
        $this->pendingDeleteName = $notification->name;
        $this->pendingDeleteMarksCount = $notification->events()->count();
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
        $this->pendingDeleteName = null;
        $this->pendingDeleteMarksCount = 0;
    }

    public function delete(): void
    {
        if (! $this->pendingDeleteId) {
            return;
        }

        $notification = Auth::user()->reminders()->find($this->pendingDeleteId);

        if (! $notification) {
            $this->pendingDeleteId = null;
            $this->pendingDeleteName = null;
            $this->pendingDeleteMarksCount = 0;

            return;
        }

        $this->authorize('delete', $notification);

        $notification->delete();

        $this->pendingDeleteId = null;
        $this->pendingDeleteName = null;
        $this->pendingDeleteMarksCount = 0;
        $this->dispatch('swipe-reset', except: -1);
        $this->loadNotifications();
    }

    public function undoCreate(): void
    {
        if (! $this->undoState || ! isset($this->undoState['notificationId'])) {
            return;
        }

        $notification = Auth::user()->reminders()->find($this->undoState['notificationId']);

        if ($notification) {
            $this->authorize('delete', $notification);
            $notification->delete();
        }

        $this->undoState = null;
        $this->loadNotifications();
    }

    public function openCreateSheet(?string $template = null): void
    {
        $this->sheetMode = 'create';
        $this->sheetTemplate = $template;
        $this->sheetNotificationId = null;
        $this->sheetOpen = true;
    }

    public function openEditSheet(int $notificationId): void
    {
        if (! auth()->user()->reminders()->whereKey($notificationId)->exists()) {
            return;
        }

        $this->sheetMode = 'edit';
        $this->sheetNotificationId = $notificationId;
        $this->sheetTemplate = null;
        $this->sheetOpen = true;
        $this->dispatch('swipe-reset', except: -1);
    }

    #[On('reminder-created')]
    public function onReminderCreated(int $id, string $name): void
    {
        $this->sheetOpen = false;
        $this->undoState = [
            'notificationId' => $id,
            'label' => __(':name created', ['name' => $name]),
        ];
        $this->loadNotifications();
    }

    #[On('reminder-updated')]
    #[On('sheet-closed')]
    public function closeSheet(): void
    {
        $this->sheetOpen = false;
        $this->loadNotifications();
    }

    public function render(): View
    {
        return view('livewire.notification-list');
    }

    private function loadNotifications(): void
    {
        $this->notifications = Auth::user()->reminders()->latest()->get();

        $this->allCount = $this->notifications->count();
        $this->endedCount = $this->notifications->filter(fn ($n) => $n->isEnded())->count();
        $this->pausedCount = $this->notifications->where('is_active', false)->count();
        $this->activeCount = $this->allCount - $this->endedCount - $this->pausedCount;

        $nextEvents = NotificationEvent::query()
            ->whereIn('notification_id', $this->notifications->pluck('id'))
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('notification_id');

        $this->notifications->each(function ($notification) use ($nextEvents) {
            $notification->setRelation('nextUpcomingEvent', $nextEvents->get($notification->id)?->first());
        });

        $eventStats = NotificationEvent::query()
            ->whereIn('notification_id', $this->notifications->pluck('id'))
            ->selectRaw('notification_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done', [EventStatus::Done->value])
            ->groupBy('notification_id')
            ->get()
            ->keyBy('notification_id');

        $this->notifications->each(function ($notification) use ($eventStats) {
            $stats = $eventStats->get($notification->id);
            $notification->setAttribute('eventsTotal', (int) ($stats->total ?? 0));
            $notification->setAttribute('eventsDone', (int) ($stats->done ?? 0));
        });

        $this->notificationBuckets = $this->notifications->mapWithKeys(fn ($notification) => [
            $notification->id => $notification->isEnded() ? 'ended' : ($notification->is_active ? 'active' : 'paused'),
        ])->all();
    }
}
