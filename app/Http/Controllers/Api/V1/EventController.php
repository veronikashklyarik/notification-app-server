<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Resources\Api\V1\NotificationEventResource;
use App\Models\NotificationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    /**
     * Display a listing of the user's notification events.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $events = $request->user()
            ->notificationEvents()
            ->with('notification')
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status'))
            )
            ->when(
                $request->filled('notification_id'),
                fn ($query) => $query->where('notification_id', $request->integer('notification_id'))
            )
            ->orderBy('scheduled_at')
            ->paginate(20);

        return NotificationEventResource::collection($events);
    }

    /**
     * Update the status of a notification event.
     */
    public function update(UpdateEventRequest $request, NotificationEvent $event): JsonResponse
    {
        $data = $request->validated();
        $status = EventStatus::from($data['status']);

        $updateData = [
            'status' => $status,
            'comment' => $data['comment'] ?? $event->comment,
            'completed_at' => now(),
        ];

        if ($status === EventStatus::Postponed) {
            $postponeEntry = [
                'from' => $event->postponed_until?->toIso8601String() ?? $event->scheduled_at->toIso8601String(),
                'to' => $data['postponed_until'],
                'at' => now()->toIso8601String(),
            ];

            $history = $event->postpone_history ?? [];
            $history[] = $postponeEntry;

            $updateData['postponed_until'] = $data['postponed_until'];
            $updateData['postpone_history'] = $history;
        }

        $event->update($updateData);

        return response()->json(new NotificationEventResource($event->load('notification')));
    }
}
