<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecordNotificationActionRequest;
use App\Http\Requests\Api\V1\StoreNotificationRequest;
use App\Http\Requests\Api\V1\UpdateNotificationRequest;
use App\Http\Resources\Api\V1\NotificationHistoryResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->reminders()
            ->latest()
            ->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Store a newly created notification in storage.
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Dates will be converted to UTC in the model's booted() method
        // next_due_at will be calculated automatically in the model's booted() method
        $notification = Notification::query()->create($data);

        return response()->json(new NotificationResource($notification), 201);
    }

    /**
     * Display the specified notification.
     */
    public function show(Request $request, Notification $notification): JsonResponse
    {
        abort_if($request->user()->id !== $notification->user_id, 403);

        return response()->json(new NotificationResource($notification));
    }

    /**
     * Update the specified notification in storage.
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        // Dates converted to UTC and next_due_at recalculated automatically in model's booted() method
        $notification->update($request->validated());

        return response()->json(new NotificationResource($notification));
    }

    /**
     * Remove the specified notification from storage.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        abort_if($request->user()->id !== $notification->user_id, 403);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    /**
     * Record an action (done, cancel, postpone) on the notification.
     */
    public function recordAction(RecordNotificationActionRequest $request, Notification $notification): JsonResponse
    {
        $data = $request->validated();

        $historyEntry = NotificationHistory::query()->create([
            'notification_id' => $notification->id,
            'user_id' => $request->user()->id,
            'action' => $data['action'],
            'comment' => $data['comment'] ?? null,
            'postponed_until' => $data['postponed_until'] ?? null,
            'due_at' => $notification->next_due_at,
        ]);

        $userTimezone = $request->user()->timezone ?? 'UTC';

        if (isset($data['postponed_until'])) {
            $notification->next_due_at = Carbon::parse($data['postponed_until']);
            $notification->save();
        } else {
            $notification->advanceNextDueAt($userTimezone);
        }

        return response()->json(new NotificationHistoryResource($historyEntry->load('notification')), 201);
    }
}
