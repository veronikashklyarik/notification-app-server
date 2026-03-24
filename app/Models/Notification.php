<?php

namespace App\Models;

use App\Enums\ScheduleType;
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
    'next_due_at',
    'is_active',
])]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory, MassPrunable, SoftDeletes;

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
            'next_due_at' => 'datetime',
            'is_active' => 'boolean',
            'every_n_days' => 'integer',
            'cyclical_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Notification $notification) {
            // Get timezone from the notification's user, fallback to authenticated user, then UTC
            $userTimezone = $notification->user?->timezone
                ?? auth()->user()?->timezone
                ?? 'UTC';

            $fields = ['starts_at', 'ends_at', 'times', 'schedule_type', 'week_days', 'is_active', 'every_n_days', 'cyclical_value', 'cyclical_unit'];

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

            if ($notification->isDirty($fields)) {
                $notification->next_due_at = $notification->calculateNextDueAt($userTimezone);
            }
        });

        static::deleting(function (Notification $notification) {
            if (method_exists($notification, 'isForceDeleting') && ! $notification->isForceDeleting()) {
                $notification->history()->update(['notification_id' => null]);
            }
        });
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
     * Get the history entries for this notification.
     *
     * @return HasMany<NotificationHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(NotificationHistory::class);
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
     * Calculate the next due date after the current one, respecting times and ends_at.
     */
    public function calculateNextDueAt(string $timezone = 'UTC'): ?Carbon
    {
        if ($this->schedule_type === ScheduleType::AsNeeded) {
            return null;
        }

        $now = now($timezone);
        $start = $this->starts_at->copy()->setTimezone($timezone)->startOfDay();

        $base = $now->gt($start) ? $now : $start;
        $times = $this->getEffectiveTimes();

        // Check if today is a valid day for the schedule
        if ($this->isValidScheduleDay($base)) {
            // Try to find a time today that's in the future
            foreach ($times as $time) {
                $candidate = $this->applyTime($base->copy(), $time);

                if ($candidate->gt($now)) {
                    return $this->checkEndsAt($candidate, $timezone)?->utc();
                }
            }
        }

        // Today is not valid or all times today are past — get next eligible date
        $nextDate = $this->getNextOccurrenceDate($base, $timezone);
        if (! $nextDate) {
            return null;
        }

        $result = $this->applyTime($nextDate, $times[0]);

        return $this->checkEndsAt($result, $timezone)?->utc();
    }

    /**
     * Advance next_due_at to the next occurrence and persist.
     */
    public function advanceNextDueAt(string $timezone = 'UTC'): void
    {
        $nextAt = $this->calculateNextDueAt($timezone);
        if ($nextAt === null) {
            $this->is_active = false;
        }
        $this->next_due_at = $nextAt;
        $this->save();
    }

    /**
     * Check if a given date is valid for this notification's schedule type.
     */
    private function isValidScheduleDay(Carbon $date): bool
    {
        return match ($this->schedule_type) {
            ScheduleType::EveryDay => true,
            ScheduleType::WeekDays => in_array($date->isoWeekday(), $this->week_days ?? []),
            ScheduleType::EveryNDays => true, // Will be validated by getNextOccurrenceDate
            ScheduleType::Cyclical => true, // Will be validated by getNextOccurrenceDate
            ScheduleType::AsNeeded => false,
        };
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

    /**
     * Get the next eligible calendar date after $base for the current schedule type.
     */
    private function getNextOccurrenceDate(Carbon $base, string $timezone): ?Carbon
    {
        return match ($this->schedule_type) {
            ScheduleType::EveryDay => $base->copy()->addDay()->startOfDay(),
            ScheduleType::WeekDays => $this->nextWeekDay($base->copy()->addDay()->startOfDay()),
            ScheduleType::EveryNDays => $base->copy()->addDays($this->every_n_days ?? 1)->startOfDay(),
            ScheduleType::Cyclical => $this->nextCyclicalDate($base),
            default => null,
        };
    }

    /**
     * Find the next date that falls on a selected day of the week.
     */
    private function nextWeekDay(Carbon $from): ?Carbon
    {
        $days = $this->week_days ?? [];

        if (empty($days)) {
            return null;
        }

        for ($i = 0; $i < 8; $i++) {
            $candidate = $from->copy()->addDays($i)->startOfDay();

            if (in_array($candidate->isoWeekday(), $days)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Advance the date by the cyclical period.
     */
    private function nextCyclicalDate(Carbon $base): Carbon
    {
        $value = $this->cyclical_value ?? 1;

        return match ($this->cyclical_unit ?? 'weeks') {
            'days' => $base->copy()->addDays($value)->startOfDay(),
            'weeks' => $base->copy()->addWeeks($value)->startOfDay(),
            'months' => $base->copy()->addMonths($value)->startOfDay(),
            'years' => $base->copy()->addYears($value)->startOfDay(),
            default => $base->copy()->addWeeks($value)->startOfDay(),
        };
    }

    /**
     * Return null if the given date exceeds ends_at, otherwise return the date.
     */
    private function checkEndsAt(Carbon $date, string $timezone): ?Carbon
    {
        if ($this->ends_at === null) {
            return $date;
        }

        // ends_at is inclusive — occurrences on the last day are still allowed
        return $date->lte($this->ends_at->copy()->endOfDay()) ? $date : null;
    }

    /**
     * Return sorted list of times, defaulting to 09:00 if none are set.
     *
     * @return array<int, string>
     */
    private function getEffectiveTimes(): array
    {
        $times = $this->times ?? ['09:00'];
        sort($times);

        return $times;
    }

    /**
     * Set the time component of a Carbon date from an HH:MM string.
     */
    private function applyTime(Carbon $date, string $time): Carbon
    {
        [$h, $m] = explode(':', $time);

        return $date->setTime((int) $h, (int) $m, 0);
    }
}
