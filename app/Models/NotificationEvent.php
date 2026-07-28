<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\NotificationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'notification_id',
    'user_id',
    'scheduled_at',
    'status',
    'postponed_until',
    'postpone_history',
    'comment',
    'completed_at',
    'notified_at',
    'reminded_at',
])]
class NotificationEvent extends Model
{
    /** @use HasFactory<NotificationEventFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'scheduled_at' => 'datetime',
            'postponed_until' => 'datetime',
            'postpone_history' => 'array',
            'completed_at' => 'datetime',
            'notified_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    /**
     * Get the notification this event belongs to.
     *
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class)->withTrashed();
    }

    /**
     * Get the user this event belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
