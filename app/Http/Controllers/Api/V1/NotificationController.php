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
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->reminders()
//            ->when($request->boolean('active_only', false), fn ($query) => $query->where('is_active', true))
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
        $userTimezone = $request->user()->timezone ?? 'UTC';
        $data['user_id'] = $request->user()->id;

        $startsAt = isset($data['starts_at'])
            ? Carbon::parse($data['starts_at'], $userTimezone)->startOfDay()
            : now($userTimezone)->startOfDay();

        $data['starts_at'] = $startsAt->toDateString();

        if (isset($data['ends_at'])) {
            $data['ends_at'] = Carbon::parse($data['ends_at'])->toDateString();
        }

        // Set next_due_at to the start date at the first scheduled time
        $times = ! empty($data['times']) ? $data['times'] : ['09:00'];
        sort($times);
        [$h, $m] = explode(':', $times[0]);
        $data['next_due_at'] = $startsAt->copy()->setTime((int) $h, (int) $m, 0)->utc();

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
        $data = $request->validated();

        if (isset($data['starts_at'])) {
            $data['starts_at'] = Carbon::parse($data['starts_at'])->toDateString();
        }

        if (isset($data['ends_at'])) {
            $data['ends_at'] = Carbon::parse($data['ends_at'])->toDateString();
        }

        $notification->update($data);

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
