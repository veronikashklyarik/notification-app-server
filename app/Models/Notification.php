<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\ScheduleType;
use App\Services\NotificationEventService;
use Database\Factories\NotificationFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'user_id',
    'name',
    'description',
    'schedule_type',
    'week_days',
    'every_n_days',
    'cyclical_value',
    'cyclical_unit',
    'times',
    'starts_at',
    'ends_at',
    'is_active',
])]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory, MassPrunable, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_type' => ScheduleType::class,
            'week_days' => 'array',
            'times' => 'array',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'every_n_days' => 'integer',
            'cyclical_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Notification $notification) {
            $userTimezone = $notification->user?->timezone
                ?? auth()->user()?->timezone
                ?? 'UTC';

            if ($notification->isDirty('starts_at') && $notification->starts_at) {
                $notification->starts_at = Carbon::parse($notification->starts_at, $userTimezone)
                    ->startOfDay()
                    ->utc();
            }

            if ($notification->isDirty('ends_at') && $notification->ends_at) {
                $notification->ends_at = Carbon::parse($notification->ends_at, $userTimezone)
                    ->endOfDay()
                    ->utc();
            }
        });

        static::saved(function (Notification $notification) {
            $scheduleFields = ['starts_at', 'ends_at', 'times', 'schedule_type', 'week_days', 'is_active', 'every_n_days', 'cyclical_value', 'cyclical_unit'];

            if ($notification->wasChanged($scheduleFields) || $notification->wasRecentlyCreated) {
                app(NotificationEventService::class)->regenerateEvents($notification);
            }
        });

        static::deleting(function (Notification $notification) {
            if (method_exists($notification, 'isForceDeleting') && ! $notification->isForceDeleting()) {
                $notification->events()->update(['notification_id' => null]);
            }
        });
    }

    /**
     * Determine if the notification's schedule has ended.
     */
    public function isEnded(): bool
    {
        $tz = Auth::user()?->timezone ?? 'UTC';

        return $this->is_active
            && $this->ends_at !== null
            && Carbon::parse($this->ends_at->format('Y-m-d'), $tz)->endOfDay()->isPast();
    }

    public function prunable(): Builder
    {
        return self::onlyTrashed()->where('deleted_at', '<=', now()->subMonth());
    }

    /**
     * Get the user that owns the notification.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the events for this notification.
     *
     * @return HasMany<NotificationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(NotificationEvent::class);
    }

    /**
     * Get the next pending event for this notification.
     */
    public function getNextEventAttribute(): ?NotificationEvent
    {
        return $this->events()
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->first();
    }

    /**
     * Get a human-readable description of the schedule including times.
     */
    public function getFrequencyLabelAttribute(): string
    {
        $schedule = match ($this->schedule_type) {
            ScheduleType::EveryDay => 'Every day',
            ScheduleType::WeekDays => $this->weekDaysLabel(),
            ScheduleType::EveryNDays => 'Every '.($this->every_n_days ?? 1).' '.(($this->every_n_days ?? 1) === 1 ? 'day' : 'days'),
            ScheduleType::Cyclical => 'Every '.($this->cyclical_value ?? 1).' '.($this->cyclical_unit ?? 'weeks'),
            ScheduleType::AsNeeded => 'As needed',
        };

        if ($this->schedule_type !== ScheduleType::AsNeeded && ! empty($this->times)) {
            $schedule .= ' at '.implode(', ', $this->times);
        }

        return $schedule;
    }

    /**
     * Build a label for the selected week days.
     */
    private function weekDaysLabel(): string
    {
        $days = $this->week_days ?? [];

        if (empty($days)) {
            return 'Specific days';
        }

        $names = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $sorted = $days;
        sort($sorted);

        return implode(', ', array_map(fn ($d) => $names[$d] ?? $d, $sorted));
    }
}
