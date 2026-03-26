<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notification_id,
            'notification' => $this->whenLoaded('notification', fn () => [
                'id' => $this->notification->id,
                'name' => $this->notification->name,
            ]),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status->value,
            'postponed_until' => $this->postponed_until?->toIso8601String(),
            'postpone_history' => $this->postpone_history,
            'comment' => $this->comment,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
