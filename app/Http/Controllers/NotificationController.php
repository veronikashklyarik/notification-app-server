<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->reminders()
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create(): View
    {
        return view('notifications.create');
    }

    /**
     * Store a newly created notification in storage.
     */
    public function store(StoreNotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        Notification::query()->create($data);

        return redirect()->route('notifications.index')
            ->with('status', 'Notification created successfully.');
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): View
    {
        $this->authorizeNotification($notification);

        $pendingEvents = $notification->events()
            ->where('status', EventStatus::Pending)
            ->orderBy('scheduled_at')
            ->paginate(10, ['*'], 'pending_page');

        $processedEvents = $notification->events()
            ->where('status', '!=', EventStatus::Pending)
            ->orderByDesc('completed_at')
            ->paginate(10, ['*'], 'processed_page');

        return view('notifications.show', compact('notification', 'pendingEvents', 'processedEvents'));
    }

    /**
     * Show the form for editing the notification.
     */
    public function edit(Notification $notification): View
    {
        $this->authorizeNotification($notification);

        return view('notifications.edit', compact('notification'));
    }

    /**
     * Update the specified notification in storage.
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): RedirectResponse
    {
        $this->authorizeNotification($notification);

        $notification->update($request->validated());

        return redirect()->route('notifications.show', $notification)
            ->with('status', 'Notification updated successfully.');
    }

    /**
     * Remove the specified notification from storage.
     */
    public function destroy(Notification $notification): RedirectResponse
    {
        $this->authorizeNotification($notification);

        $notification->delete();

        return redirect()->route('notifications.index')
            ->with('status', 'Notification deleted.');
    }

    /**
     * Ensure the authenticated user owns the notification.
     */
    private function authorizeNotification(Notification $notification): void
    {
        abort_if(auth()->id() !== $notification->user_id, 403);
    }
}
