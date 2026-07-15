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

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property ScheduleType $schedule_type
 * @property array<array{day: int, times: array<int, string>}>|null $week_days
 * @property array<array{date: string, times: array<int, string>}>|null $specific_dates
 * @property int|null $every_n_days
 * @property int|null $cyclical_value
 * @property string|null $cyclical_unit
 * @property array<int>|null $cyclical_week_days
 * @property string|null $cyclical_month_type
 * @property array<int>|null $cyclical_month_days
 * @property string|null $cyclical_month_position
 * @property int|null $cyclical_month_weekday
 * @property array<int>|null $cyclical_year_months
 * @property int|null $cyclical_year_day
 * @property bool $cyclical_year_use_weekday
 * @property int|null $cyclical_use_for
 * @property int|null $cyclical_pause_for
 * @property array<int, string>|null $times
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
#[Fillable([
    'user_id',
    'name',
    'description',
    'schedule_type',
    'week_days',
    'specific_dates',
    'every_n_days',
    'cyclical_value',
    'cyclical_unit',
    'cyclical_week_days',
    'cyclical_month_type',
    'cyclical_month_days',
    'cyclical_month_position',
    'cyclical_month_weekday',
    'cyclical_year_months',
    'cyclical_year_day',
    'cyclical_year_use_weekday',
    'cyclical_use_for',
    'cyclical_pause_for',
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
            'specific_dates' => 'array',
            'cyclical_week_days' => 'array',
            'cyclical_month_days' => 'array',
            'cyclical_month_weekday' => 'integer',
            'cyclical_year_months' => 'array',
            'cyclical_year_day' => 'integer',
            'cyclical_year_use_weekday' => 'boolean',
            'cyclical_use_for' => 'integer',
            'cyclical_pause_for' => 'integer',
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
            $scheduleFields = ['starts_at', 'ends_at', 'times', 'schedule_type', 'week_days', 'specific_dates', 'is_active', 'every_n_days', 'cyclical_value', 'cyclical_unit', 'cyclical_week_days', 'cyclical_month_type', 'cyclical_month_days', 'cyclical_month_position', 'cyclical_month_weekday', 'cyclical_year_months', 'cyclical_year_day', 'cyclical_year_use_weekday', 'cyclical_use_for', 'cyclical_pause_for'];

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
        $n = $this->every_n_days ?? 1;
        $schedule = match ($this->schedule_type) {
            ScheduleType::EveryDay => __('Every day'),
            ScheduleType::WeekDays => $this->weekDaysLabel(),
            ScheduleType::EveryNDays => trans_choice('Every :count day|Every :count days', $n, ['count' => $n]),
            ScheduleType::Cyclical => $this->cyclicalLabel(),
            ScheduleType::SpecificDates => $this->specificDatesLabel(),
            ScheduleType::AsNeeded => __('As needed'),
        };

        if (! in_array($this->schedule_type, [ScheduleType::AsNeeded, ScheduleType::SpecificDates, ScheduleType::WeekDays]) && ! empty($this->times)) {
            $schedule .= ' '.__('at :times', ['times' => implode(', ', $this->times)]);
        }

        return $schedule;
    }

    private function weekDaysLabel(): string
    {
        $days = $this->week_days ?? [];

        if (empty($days)) {
            return __('Specific days');
        }

        $names = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
        usort($days, fn ($a, $b) => (int) $a['day'] <=> (int) $b['day']);

        return implode(', ', array_map(function ($e) use ($names) {
            $label = $names[(int) $e['day']] ?? (int) $e['day'];
            $times = $e['times'] ?? [];
            if (! empty($times)) {
                $label .= ' '.implode('/', $times);
            }

            return $label;
        }, $days));
    }

    private function cyclicalLabel(): string
    {
        $value = $this->cyclical_value ?? 1;
        $unit = $this->cyclical_unit ?? 'weeks';
        $dayNames = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];

        if ($unit === 'days' && $this->cyclical_use_for) {
            return __('Active for :use days, off for :pause days', [
                'use' => $this->cyclical_use_for,
                'pause' => $this->cyclical_pause_for ?? 0,
            ]);
        }

        if ($unit === 'weeks' && ! empty($this->cyclical_week_days)) {
            $sorted = $this->cyclical_week_days ?? [];
            sort($sorted);
            $names = implode(', ', array_map(fn ($d) => $dayNames[(int) $d] ?? $d, $sorted));

            return trans_choice('Every :count week on :days|Every :count weeks on :days', $value, ['count' => $value, 'days' => $names]);
        }

        if ($unit === 'months' && $this->cyclical_month_type === 'each' && ! empty($this->cyclical_month_days)) {
            $sorted = $this->cyclical_month_days ?? [];
            sort($sorted);
            $ordinals = array_map(fn ($d) => $this->ordinal((int) $d), $sorted);
            $last = array_pop($ordinals);
            $dayList = empty($ordinals)
                ? $last
                : implode(', ', $ordinals).' '.__('and').' '.$last;

            return trans_choice('Every :count month on the :days|Every :count months on the :days', $value, ['count' => $value, 'days' => $dayList]);
        }

        if ($unit === 'months' && $this->cyclical_month_type === 'on_the' && $this->cyclical_month_position && $this->cyclical_month_weekday) {
            $positions = ['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('last')];
            $pos = $positions[$this->cyclical_month_position] ?? $this->cyclical_month_position;
            $day = $dayNames[(int) $this->cyclical_month_weekday] ?? $this->cyclical_month_weekday;

            return trans_choice('Every :count month on the :pos :day|Every :count months on the :pos :day', $value, ['count' => $value, 'pos' => $pos, 'day' => $day]);
        }

        if ($unit === 'years' && ! empty($this->cyclical_year_months)) {
            $monthNames = [1 => __('Jan'), 2 => __('Feb'), 3 => __('Mar'), 4 => __('Apr'), 5 => __('May'), 6 => __('Jun'), 7 => __('Jul'), 8 => __('Aug'), 9 => __('Sep'), 10 => __('Oct'), 11 => __('Nov'), 12 => __('Dec')];
            $sorted = $this->cyclical_year_months ?? [];
            sort($sorted);
            $months = implode(', ', array_map(fn ($m) => $monthNames[(int) $m] ?? $m, $sorted));
            $base = trans_choice('Every :count year in :months|Every :count years in :months', $value, ['count' => $value, 'months' => $months]);

            $yearPosition = $this->cyclical_month_position;
            $yearWeekday = $this->cyclical_month_weekday;
            if ($this->cyclical_year_use_weekday && $yearPosition !== null && $yearWeekday !== null) {
                $positions = ['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('last')];
                $pos = $positions[$yearPosition] ?? $yearPosition;
                $day = $dayNames[$yearWeekday] ?? $yearWeekday;
                $base .= ' '.__('on the :pos :day', ['pos' => $pos, 'day' => $day]);
            } elseif ($this->cyclical_year_day && ! $this->cyclical_year_use_weekday) {
                $base .= ' '.__('on the :day', ['day' => $this->ordinal((int) $this->cyclical_year_day)]);
            }

            return $base;
        }

        return match ($unit) {
            'weeks' => trans_choice('Every :count week|Every :count weeks', $value, ['count' => $value]),
            'months' => trans_choice('Every :count month|Every :count months', $value, ['count' => $value]),
            'years' => trans_choice('Every :count year|Every :count years', $value, ['count' => $value]),
            default => trans_choice('Every :count day|Every :count days', $value, ['count' => $value]),
        };
    }

    private function ordinal(int $n): string
    {
        $locale = app()->getLocale();
        if ($locale !== 'en') {
            return $n.__('ordinal_suffix');
        }
        $mod100 = $n % 100;
        $mod10 = $n % 10;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $n.'th';
        }

        return $n.match ($mod10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function specificDatesLabel(): string
    {
        $dates = $this->specific_dates ?? [];

        if (empty($dates)) {
            return __('On specific dates');
        }

        $dateParts = array_map(fn ($e) => $e['date'], $dates);
        $dateParts = array_filter($dateParts);
        sort($dateParts);

        $count = count($dateParts);
        $preview = array_slice($dateParts, 0, 3);
        $previewLabels = array_map(fn ($d) => Carbon::parse($d)->translatedFormat('M j'), $preview);
        $label = implode(', ', $previewLabels);

        if ($count > 3) {
            $label .= '…';
        }

        return $label;
    }
}
