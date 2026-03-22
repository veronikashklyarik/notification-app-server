<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationHistoryResource extends JsonResource
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
            'action' => $this->action->value,
            'comment' => $this->comment,
            'postponed_until' => $this->postponed_until,
            'due_at' => $this->due_at,
            'created_at' => $this->created_at,
        ];
    }
}
