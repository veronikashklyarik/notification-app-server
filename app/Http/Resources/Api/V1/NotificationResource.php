<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'schedule_type' => $this->schedule_type->value,
            'week_days' => $this->week_days,
            'every_n_days' => $this->every_n_days,
            'cyclical_value' => $this->cyclical_value,
            'cyclical_unit' => $this->cyclical_unit,
            'times' => $this->times,
            'frequency_label' => $this->frequency_label,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'next_due_at' => $this->next_due_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
