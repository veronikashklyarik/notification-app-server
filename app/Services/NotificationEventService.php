<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\ScheduleType;
use App\Models\Notification;
use App\Models\NotificationEvent;
use Illuminate\Support\Carbon;

class NotificationEventService
{
    private const int MAX_PENDING_EVENTS = 30;

    private const int MAX_HISTORY_EVENTS = 90;

    public function regenerateEvents(Notification $notification): void
    {
        $notification->events()
            ->where('status', EventStatus::Pending)
            ->delete();

        if (! $notification->is_active || $notification->schedule_type === ScheduleType::AsNeeded) {
            return;
        }

        $this->generatePendingEvents($notification);
    }

    public function topUpEvents(Notification $notification): void
    {
        if (! $notification->is_active || $notification->schedule_type === ScheduleType::AsNeeded) {
            return;
        }

        $existingCount = $notification->events()
            ->where('status', EventStatus::Pending)
            ->count();

        if ($existingCount >= self::MAX_PENDING_EVENTS) {
            return;
        }

        $remaining = self::MAX_PENDING_EVENTS - $existingCount;

        $lastPending = $notification->events()
            ->where('status', EventStatus::Pending)
            ->orderByDesc('scheduled_at')
            ->first();

        $timezone = $notification->user?->timezone ?? 'UTC';
        $startFrom = $lastPending
            ? $lastPending->scheduled_at->copy()->setTimezone($timezone)
            : now($timezone);

        $this->generatePendingEvents($notification, $remaining, $startFrom);
    }

    public function pruneHistory(Notification $notification): void
    {
        $nonPendingCount = $notification->events()
            ->where('status', '!=', EventStatus::Pending)
            ->count();

        if ($nonPendingCount <= self::MAX_HISTORY_EVENTS) {
            return;
        }

        $cutoffEvent = $notification->events()
            ->where('status', '!=', EventStatus::Pending)
            ->orderByDesc('completed_at')
            ->offset(self::MAX_HISTORY_EVENTS)
            ->limit(1)
            ->first();

        if ($cutoffEvent) {
            $notification->events()
                ->where('status', '!=', EventStatus::Pending)
                ->where('completed_at', '<', $cutoffEvent->completed_at)
                ->delete();
        }
    }

    private function generatePendingEvents(
        Notification $notification,
        int $limit = self::MAX_PENDING_EVENTS,
        ?Carbon $startFrom = null,
    ): void {
        if ($notification->schedule_type === ScheduleType::SpecificDates) {
            $this->generateSpecificDateEvents($notification, $limit, $startFrom);

            return;
        }

        if ($notification->schedule_type === ScheduleType::Cyclical
            && $notification->cyclical_unit === 'days'
            && $notification->cyclical_use_for) {
            $this->generateCyclicalPauseEvents($notification, $limit, $startFrom);

            return;
        }

        $timezone = $notification->user?->timezone ?? 'UTC';
        $now = $startFrom ?? now($timezone);
        $start = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)->startOfDay()
            : now($timezone)->startOfDay();

        $base = $now->gt($start) ? $now : $start;
        $events = [];
        $completedScheduledAts = $this->getCompletedScheduledAts($notification);

        if ($this->isValidScheduleDay($notification, $base, $timezone)) {
            foreach ($this->getTimesForDate($notification, $base) as $time) {
                $candidate = $this->applyTime($base->copy(), $time);

                if ($candidate->gt($now) && $this->checkEndsAt($notification, $candidate, $timezone)) {
                    $utc = $candidate->copy()->utc();

                    if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                        $events[] = $utc;
                    }

                    if (count($events) >= $limit) {
                        break;
                    }
                }
            }
        }

        $currentDate = $base->copy();

        while (count($events) < $limit) {
            $nextDate = $this->getNextOccurrenceDate($notification, $currentDate, $timezone);

            if (! $nextDate) {
                break;
            }

            foreach ($this->getTimesForDate($notification, $nextDate) as $time) {
                $candidate = $this->applyTime($nextDate->copy(), $time);

                if (! $this->checkEndsAt($notification, $candidate, $timezone)) {
                    break 2;
                }

                $utc = $candidate->copy()->utc();

                if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                    $events[] = $utc;
                }

                if (count($events) >= $limit) {
                    break 2;
                }
            }

            $currentDate = $nextDate;
        }

        foreach ($events as $scheduledAt) {
            NotificationEvent::query()->create([
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'scheduled_at' => $scheduledAt,
                'status' => EventStatus::Pending,
            ]);
        }
    }

    private function generateSpecificDateEvents(
        Notification $notification,
        int $limit,
        ?Carbon $startFrom,
    ): void {
        $timezone = $notification->user?->timezone ?? 'UTC';
        $now = $startFrom ?? now($timezone);
        $globalTimes = $this->getEffectiveTimes($notification);
        $specificDates = $notification->specific_dates ?? [];

        if (empty($specificDates)) {
            return;
        }

        usort($specificDates, function ($a, $b) {
            $da = is_array($a) ? ($a['date'] ?? '') : $a;
            $db = is_array($b) ? ($b['date'] ?? '') : $b;

            return strcmp($da, $db);
        });

        $completedScheduledAts = $this->getCompletedScheduledAts($notification);
        $events = [];

        foreach ($specificDates as $entry) {
            $dateStr = is_array($entry) ? ($entry['date'] ?? null) : $entry;
            $times = (is_array($entry) && ! empty($entry['times'])) ? $entry['times'] : $globalTimes;
            sort($times);

            if (! $dateStr) {
                continue;
            }

            $date = Carbon::createFromFormat('Y-m-d', $dateStr, $timezone)->startOfDay();

            foreach ($times as $time) {
                $candidate = $this->applyTime($date->copy(), $time);

                if ($candidate->gt($now)) {
                    $utc = $candidate->copy()->utc();

                    if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                        $events[] = $utc;
                    }

                    if (count($events) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        foreach ($events as $scheduledAt) {
            NotificationEvent::query()->create([
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'scheduled_at' => $scheduledAt,
                'status' => EventStatus::Pending,
            ]);
        }
    }

    private function generateCyclicalPauseEvents(
        Notification $notification,
        int $limit,
        ?Carbon $startFrom,
    ): void {
        $timezone = $notification->user?->timezone ?? 'UTC';
        $now = $startFrom ?? now($timezone);
        $useFor = max(1, $notification->cyclical_use_for ?? 1);
        $pauseFor = max(0, $notification->cyclical_pause_for ?? 0);
        $cycleLength = $useFor + $pauseFor;

        if ($cycleLength <= 0) {
            return;
        }

        $start = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)->startOfDay()
            : now($timezone)->startOfDay();

        $times = $this->getEffectiveTimes($notification);
        $completedScheduledAts = $this->getCompletedScheduledAts($notification);
        $events = [];

        $current = ($now->gt($start) ? $now->copy() : $start->copy())->startOfDay();
        $maxDays = $limit * $cycleLength + $cycleLength;

        for ($i = 0; $i < $maxDays && count($events) < $limit; $i++) {
            $daysSinceStart = (int) $start->startOfDay()->diffInDays($current);
            $posInCycle = $daysSinceStart % $cycleLength;

            if ($posInCycle < $useFor) {
                foreach ($times as $time) {
                    $candidate = $this->applyTime($current->copy(), $time);

                    if ($candidate->gt($now) && $this->checkEndsAt($notification, $candidate, $timezone)) {
                        $utc = $candidate->copy()->utc();

                        if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                            $events[] = $utc;
                        }

                        if (count($events) >= $limit) {
                            break;
                        }
                    }
                }
            }

            $current->addDay();
        }

        foreach ($events as $scheduledAt) {
            NotificationEvent::query()->create([
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'scheduled_at' => $scheduledAt,
                'status' => EventStatus::Pending,
            ]);
        }
    }

    private function isValidScheduleDay(Notification $notification, Carbon $date, string $timezone): bool
    {
        return match ($notification->schedule_type) {
            ScheduleType::EveryDay => true,
            ScheduleType::WeekDays => in_array(
                $date->isoWeekday(),
                array_map(fn ($e) => is_array($e) ? (int) ($e['day'] ?? 0) : (int) $e, $notification->week_days ?? []),
            ),
            ScheduleType::EveryNDays => true,
            ScheduleType::Cyclical => $this->isValidCyclicalDay($notification, $date, $timezone),
            default => false,
        };
    }

    private function isValidCyclicalDay(Notification $notification, Carbon $date, string $timezone): bool
    {
        $unit = $notification->cyclical_unit ?? 'days';

        if ($unit === 'weeks' && ! empty($notification->cyclical_week_days)) {
            if (! in_array($date->isoWeekday(), array_map('intval', $notification->cyclical_week_days))) {
                return false;
            }

            return $this->isActiveCyclicalWeek($notification, $date, $timezone);
        }

        if ($unit === 'months') {
            if ($notification->cyclical_month_type === 'each' && ! empty($notification->cyclical_month_days)) {
                return in_array($date->day, array_map('intval', $notification->cyclical_month_days));
            }

            if ($notification->cyclical_month_type === 'on_the'
                && $notification->cyclical_month_position
                && $notification->cyclical_month_weekday) {
                $target = $this->getNthWeekdayOfMonth(
                    $date->copy(),
                    $notification->cyclical_month_position,
                    (int) $notification->cyclical_month_weekday,
                );

                return $date->isSameDay($target);
            }
        }

        if ($unit === 'years' && ! empty($notification->cyclical_year_months)) {
            if (! in_array($date->month, array_map('intval', $notification->cyclical_year_months))) {
                return false;
            }

            if ($notification->cyclical_year_use_weekday
                && $notification->cyclical_month_position
                && $notification->cyclical_month_weekday) {
                $target = $this->getNthWeekdayOfMonth(
                    $date->copy(),
                    $notification->cyclical_month_position,
                    (int) $notification->cyclical_month_weekday,
                );

                return $date->isSameDay($target);
            }
        }

        return true;
    }

    private function isActiveCyclicalWeek(Notification $notification, Carbon $date, string $timezone): bool
    {
        $cyclicalValue = max(1, $notification->cyclical_value ?? 1);
        $anchor = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)->startOfWeek(Carbon::MONDAY)
            : $date->copy()->startOfWeek(Carbon::MONDAY);

        $weeksSinceAnchor = (int) $anchor->diffInWeeks($date->copy()->startOfWeek(Carbon::MONDAY));

        return $weeksSinceAnchor % $cyclicalValue === 0;
    }

    private function getNextOccurrenceDate(Notification $notification, Carbon $base, string $timezone): ?Carbon
    {
        return match ($notification->schedule_type) {
            ScheduleType::EveryDay => $base->copy()->addDay()->startOfDay(),
            ScheduleType::WeekDays => $this->nextWeekDay($notification, $base->copy()->addDay()->startOfDay()),
            ScheduleType::EveryNDays => $base->copy()->addDays($notification->every_n_days ?? 1)->startOfDay(),
            ScheduleType::Cyclical => $this->nextCyclicalDate($notification, $base, $timezone),
            default => null,
        };
    }

    private function nextWeekDay(Notification $notification, Carbon $from): ?Carbon
    {
        $weekDays = $notification->week_days ?? [];

        if (empty($weekDays)) {
            return null;
        }

        $days = array_map(fn ($e) => is_array($e) ? (int) ($e['day'] ?? 0) : (int) $e, $weekDays);

        for ($i = 0; $i < 8; $i++) {
            $candidate = $from->copy()->addDays($i)->startOfDay();

            if (in_array($candidate->isoWeekday(), $days)) {
                return $candidate;
            }
        }

        return null;
    }

    private function nextCyclicalDate(Notification $notification, Carbon $base, string $timezone): ?Carbon
    {
        $value = max(1, $notification->cyclical_value ?? 1);
        $unit = $notification->cyclical_unit ?? 'weeks';

        if ($unit === 'weeks' && ! empty($notification->cyclical_week_days)) {
            return $this->nextCyclicalWeeklyDate($notification, $base, $timezone);
        }

        if ($unit === 'months') {
            return $this->nextCyclicalMonthlyDate($notification, $base, $value);
        }

        if ($unit === 'years') {
            return $this->nextCyclicalYearlyDate($notification, $base, $value, $timezone);
        }

        return match ($unit) {
            'days' => $base->copy()->addDays($value)->startOfDay(),
            'weeks' => $base->copy()->addWeeks($value)->startOfDay(),
            default => $base->copy()->addWeeks($value)->startOfDay(),
        };
    }

    private function nextCyclicalWeeklyDate(Notification $notification, Carbon $base, string $timezone): ?Carbon
    {
        $days = array_map('intval', $notification->cyclical_week_days ?? []);
        $cyclicalValue = max(1, $notification->cyclical_value ?? 1);
        $anchor = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)->startOfWeek(Carbon::MONDAY)
            : $base->copy()->startOfWeek(Carbon::MONDAY);

        $maxSearch = ($cyclicalValue * 7) + 7;
        $candidate = $base->copy()->addDay()->startOfDay();

        for ($i = 0; $i < $maxSearch; $i++, $candidate->addDay()) {
            if (in_array($candidate->isoWeekday(), $days)) {
                $weeksSinceAnchor = (int) $anchor->diffInWeeks($candidate->copy()->startOfWeek(Carbon::MONDAY));

                if ($weeksSinceAnchor % $cyclicalValue === 0) {
                    return $candidate->copy()->startOfDay();
                }
            }
        }

        return null;
    }

    private function nextCyclicalMonthlyDate(Notification $notification, Carbon $base, int $value): Carbon
    {
        if ($notification->cyclical_month_type === 'each' && ! empty($notification->cyclical_month_days)) {
            $targetDays = array_map('intval', $notification->cyclical_month_days);
            sort($targetDays);

            foreach ($targetDays as $day) {
                $candidate = $base->copy()->day(min($day, $base->daysInMonth))->startOfDay();

                if ($candidate->gt($base->copy()->startOfDay())) {
                    return $candidate;
                }
            }

            $next = $base->copy()->addMonths($value)->startOfMonth();

            return $next->day(min($targetDays[0], $next->daysInMonth))->startOfDay();
        }

        if ($notification->cyclical_month_type === 'on_the'
            && $notification->cyclical_month_position
            && $notification->cyclical_month_weekday) {
            $candidateThisMonth = $this->getNthWeekdayOfMonth(
                $base->copy(),
                $notification->cyclical_month_position,
                (int) $notification->cyclical_month_weekday,
            );

            if ($candidateThisMonth->gt($base->copy()->startOfDay())) {
                return $candidateThisMonth;
            }

            $next = $base->copy()->addMonths($value);

            return $this->getNthWeekdayOfMonth(
                $next,
                $notification->cyclical_month_position,
                (int) $notification->cyclical_month_weekday,
            );
        }

        return $base->copy()->addMonths($value)->startOfDay();
    }

    private function nextCyclicalYearlyDate(Notification $notification, Carbon $base, int $value, string $timezone): Carbon
    {
        $months = array_map('intval', $notification->cyclical_year_months ?? []);
        sort($months);

        if (empty($months)) {
            return $base->copy()->addYears($value)->startOfDay();
        }

        $anchor = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)
            : $base;

        foreach ($months as $month) {
            $candidate = $this->getYearlyMonthTarget($notification, $base->year, $month, $anchor);

            if ($candidate && $candidate->gt($base->copy()->startOfDay())) {
                return $candidate;
            }
        }

        $nextYear = $base->year + $value;

        foreach ($months as $month) {
            $candidate = $this->getYearlyMonthTarget($notification, $nextYear, $month, $anchor);

            if ($candidate) {
                return $candidate;
            }
        }

        return $base->copy()->addYears($value)->startOfDay();
    }

    private function getYearlyMonthTarget(Notification $notification, int $year, int $month, Carbon $anchor): ?Carbon
    {
        $baseMonth = Carbon::create($year, $month, 1)->startOfDay();

        if ($notification->cyclical_year_use_weekday
            && $notification->cyclical_month_position
            && $notification->cyclical_month_weekday) {
            return $this->getNthWeekdayOfMonth(
                $baseMonth,
                $notification->cyclical_month_position,
                (int) $notification->cyclical_month_weekday,
            );
        }

        $day = $notification->cyclical_year_day
            ? min((int) $notification->cyclical_year_day, $baseMonth->daysInMonth)
            : min($anchor->day, $baseMonth->daysInMonth);

        return $baseMonth->copy()->day($day)->startOfDay();
    }

    private function getNthWeekdayOfMonth(Carbon $base, string $position, int $weekday): Carbon
    {
        if ($position === 'last') {
            $candidate = $base->copy()->endOfMonth()->startOfDay();

            while ($candidate->isoWeekday() !== $weekday) {
                $candidate->subDay();
            }

            return $candidate;
        }

        $positions = ['first' => 0, 'second' => 1, 'third' => 2, 'fourth' => 3, 'fifth' => 4];
        $n = $positions[$position] ?? 0;

        $candidate = $base->copy()->startOfMonth()->startOfDay();

        while ($candidate->isoWeekday() !== $weekday) {
            $candidate->addDay();
        }

        $candidate->addWeeks($n);

        if ($candidate->month !== $base->month) {
            $candidate->subWeek();
        }

        return $candidate;
    }

    private function checkEndsAt(Notification $notification, Carbon $date, string $timezone): bool
    {
        if ($notification->ends_at === null) {
            return true;
        }

        return $date->lte($notification->ends_at->copy()->endOfDay());
    }

    /**
     * @return array<string, true>
     */
    private function getCompletedScheduledAts(Notification $notification): array
    {
        return $notification->events()
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => $dt->utc()->toDateTimeString())
            ->flip()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getEffectiveTimes(Notification $notification): array
    {
        $times = $notification->times ?? ['09:00'];
        sort($times);

        return $times;
    }

    /**
     * Returns times for a specific date — per-day for WeekDays type, global otherwise.
     *
     * @return array<int, string>
     */
    private function getTimesForDate(Notification $notification, Carbon $date): array
    {
        if ($notification->schedule_type === ScheduleType::WeekDays) {
            $isoDay = $date->isoWeekday();

            foreach ($notification->week_days ?? [] as $entry) {
                if (is_array($entry) && (int) ($entry['day'] ?? 0) === $isoDay) {
                    $times = $entry['times'] ?? [];

                    return ! empty($times) ? $times : $this->getEffectiveTimes($notification);
                }
            }

            return $this->getEffectiveTimes($notification);
        }

        return $this->getEffectiveTimes($notification);
    }

    private function applyTime(Carbon $date, string $time): Carbon
    {
        [$h, $m] = explode(':', $time);

        return $date->setTime((int) $h, (int) $m, 0);
    }
}
