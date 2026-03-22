<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationHistoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HistoryController extends Controller
{
    /**
     * Display the authenticated user's notification history.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $history = $request->user()
            ->notificationHistory()
            ->with('notification')
            ->when(
                $request->filled('notification_id'),
                fn ($query) => $query->where('notification_id', $request->integer('notification_id'))
            )
            ->latest()
            ->paginate(20);

        return NotificationHistoryResource::collection($history);
    }
}
