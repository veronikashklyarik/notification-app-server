<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class NotificationList extends Component
{
    use AuthorizesRequests;

    public Collection $notifications;

    public int $perPage = 10;

    public int $total = 0;

    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function refresh(): void
    {
        $this->perPage = 10;
        $this->loadNotifications();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
        $this->loadNotifications();
    }

    public function confirmDelete(int $notificationId): void
    {
        $this->pendingDeleteId = $notificationId;
        $this->dispatch('show-delete-confirmation');
    }

    public function delete(): void
    {
        if (! $this->pendingDeleteId) {
            return;
        }

        $notification = Auth::user()->reminders()->find($this->pendingDeleteId);

        if (! $notification) {
            $this->pendingDeleteId = null;

            return;
        }

        $this->authorize('delete', $notification);

        $notification->delete();

        $this->pendingDeleteId = null;
        $this->dispatch('swipe-reset', except: -1);
        $this->loadNotifications();
    }

    public function render(): View
    {
        return view('livewire.notification-list');
    }

    private function loadNotifications(): void
    {
        $this->total = Auth::user()->reminders()->count();

        $this->notifications = Auth::user()
            ->reminders()
            ->latest()
            ->limit($this->perPage)
            ->get();
    }
}
