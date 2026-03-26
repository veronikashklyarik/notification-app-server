<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreNotificationRequest;
use App\Http\Requests\Api\V1\UpdateNotificationRequest;
use App\Http\Resources\Api\V1\NotificationEventResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
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
     * List events for a specific notification.
     */
    public function events(Request $request, Notification $notification): AnonymousResourceCollection
    {
        abort_if($request->user()->id !== $notification->user_id, 403);

        $events = $notification->events()
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status'))
            )
            ->orderBy('scheduled_at')
            ->paginate(20);

        return NotificationEventResource::collection($events);
    }
}
