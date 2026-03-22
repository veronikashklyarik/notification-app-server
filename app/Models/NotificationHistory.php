<?php

namespace App\Models;

use App\Enums\HistoryAction;
use Database\Factories\NotificationHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'notification_id',
    'user_id',
    'action',
    'comment',
    'postponed_until',
    'due_at',
])]
class NotificationHistory extends Model
{
    /** @use HasFactory<NotificationHistoryFactory> */
    use HasFactory;

    protected $table = 'notification_history';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => HistoryAction::class,
            'postponed_until' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    /**
     * Get the notification this history entry belongs to.
     *
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class)->withTrashed();
    }

    /**
     * Get the user this history entry belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
