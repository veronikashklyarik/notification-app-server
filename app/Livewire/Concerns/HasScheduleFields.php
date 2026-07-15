<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

trait HasScheduleFields
{
    public string $schedule_type = 'every_day';

    /** @var array<int, array{day: int, times: array<int, string>}> */
    public array $week_days = [];

    /** @var array<int, array{date: string, times: array<int, string>}> */
    public array $specific_dates = [];

    public ?int $every_n_days = 2;

    public ?int $cyclical_value = 1;

    public string $cyclical_unit = 'days';

    /** @var array<int, int> */
    public array $cyclical_week_days = [];

    public string $cyclical_month_type = 'each';

    /** @var array<int, int> */
    public array $cyclical_month_days = [];

    public string $cyclical_month_position = 'first';

    public ?int $cyclical_month_weekday = 1;

    /** @var array<int, int> */
    public array $cyclical_year_months = [];

    public ?int $cyclical_year_day = null;

    public bool $cyclical_year_use_weekday = false;

    public ?int $cyclical_use_for = null;

    public ?int $cyclical_pause_for = null;

    /** @var array<int, string> */
    public array $times = ['08:00'];

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public bool $is_active = true;

    // -------------------------------------------------------------------------
    // Computed properties (used by schedule-fields.blade.php)
    // -------------------------------------------------------------------------

    #[Computed]
    public function scheduleDescription(): string
    {
        $allTimes = ! empty($this->times)
            ? (' '.__('at :times', ['times' => implode(', ', $this->times)]))
            : '';

        return match ($this->schedule_type) {
            'every_day' => __('Every day').$allTimes,
            'week_days' => $this->weekDaysDescription(),
            'cyclical' => $this->cyclicalDescription($allTimes),
            'specific_dates' => empty($this->specific_dates)
                ? __('No dates added yet')
                : trans_choice(':count date selected|:count dates selected', count($this->specific_dates), ['count' => count($this->specific_dates)]),
            'as_needed' => __('Logged manually — no automatic reminders'),
            default => '',
        };
    }

    /**
     * Returns the 3 upcoming active/pause cycle phases for the days-with-cycle preview.
     *
     * @return array<int, array{active_start: Carbon, active_end: Carbon, pause_start?: Carbon, pause_end?: Carbon}>
     */
    #[Computed]
    public function cyclicPhases(): array
    {
        if (! $this->cyclical_use_for || ! $this->cyclical_pause_for) {
            return [];
        }

        $useFor = max(1, (int) $this->cyclical_use_for);
        $pauseFor = max(0, (int) $this->cyclical_pause_for);
        $cycleLength = $useFor + $pauseFor;
        $startDate = $this->starts_at
            ? Carbon::parse($this->starts_at)->startOfDay()
            : now()->startOfDay();
        $today = now()->startOfDay();
        $endsAt = $this->ends_at ? Carbon::parse($this->ends_at)->endOfDay() : null;

        $daysSince = max(0, (int) $startDate->diffInDays($today, false));
        $phaseStart = $startDate->copy()->addDays((int) floor($daysSince / $cycleLength) * $cycleLength);

        $phases = [];
        $cursor = $phaseStart->copy();

        for ($i = 0; $i < 3; $i++) {
            if ($endsAt && $cursor->gt($endsAt)) {
                break;
            }

            $activeEnd = $cursor->copy()->addDays($useFor - 1);
            if ($endsAt && $activeEnd->gt($endsAt)) {
                $activeEnd = $endsAt->copy()->startOfDay();
            }

            $phase = ['active_start' => $cursor->copy(), 'active_end' => $activeEnd->copy()];

            if ($pauseFor > 0) {
                $pauseStart = $cursor->copy()->addDays($useFor);
                $pauseEnd = $cursor->copy()->addDays($cycleLength - 1);
                if (! $endsAt || $pauseStart->lte($endsAt)) {
                    if ($endsAt && $pauseEnd->gt($endsAt)) {
                        $pauseEnd = $endsAt->copy()->startOfDay();
                    }
                    $phase['pause_start'] = $pauseStart;
                    $phase['pause_end'] = $pauseEnd;
                }
            }

            $phases[] = $phase;
            $cursor->addDays($cycleLength);
        }

        return $phases;
    }

    /**
     * Returns upcoming dates with their associated notification times.
     *
     * @return array<int, array{date: Carbon, times: array<int, string>}>
     */
    #[Computed]
    public function upcomingDates(): array
    {
        $upFrom = $this->starts_at
            ? Carbon::parse($this->starts_at)->startOfDay()
            : now()->startOfDay();
        $upEnd = $this->ends_at ? Carbon::parse($this->ends_at)->endOfDay() : null;

        return array_map(
            fn (Carbon $date) => ['date' => $date, 'times' => $this->timesForDate($date)],
            $this->computeUpcomingDates($upFrom, $upEnd)
        );
    }

    // -------------------------------------------------------------------------
    // Schedule actions (shared between Create and Edit)
    // -------------------------------------------------------------------------

    public function addTime(): void
    {
        $next = $this->nextAvailableTime($this->times);
        if ($next !== null) {
            $this->times[] = $next;
        }
    }

    public function updatedTimes(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)$/', $key, $m)) {
            return;
        }
        $timeIndex = (int) $m[1];
        $counts = array_count_values($this->times);
        if (($counts[$value] ?? 0) > 1) {
            $others = $this->times;
            unset($others[$timeIndex]);
            $next = $this->nextAvailableTime(array_values($others));
            if ($next !== null) {
                $this->times[$timeIndex] = $next;
            }
        }
    }

    public function removeTime(int $index): void
    {
        if (count($this->times) > 1) {
            unset($this->times[$index]);
            $this->times = array_values($this->times);
        }
    }

    public function toggleWeekDay(int $day): void
    {
        foreach ($this->week_days as $index => $entry) {
            if ((int) $entry['day'] === $day) {
                array_splice($this->week_days, $index, 1);

                return;
            }
        }

        $this->week_days[] = ['day' => $day, 'times' => ['09:00']];
        usort($this->week_days, fn ($a, $b) => $a['day'] <=> $b['day']);
    }

    public function updatedWeekDays(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)\.times\.(\d+)$/', $key, $m)) {
            return;
        }
        $dayIndex = (int) $m[1];
        $timeIndex = (int) $m[2];
        $times = $this->week_days[$dayIndex]['times'] ?? [];
        $counts = array_count_values($times);
        if (($counts[$value] ?? 0) > 1) {
            $others = $times;
            unset($others[$timeIndex]);
            $next = $this->nextAvailableTime(array_values($others));
            if ($next !== null) {
                $this->week_days[$dayIndex]['times'][$timeIndex] = $next;
            }
        }
    }

    public function updatedSpecificDates(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)\.times\.(\d+)$/', $key, $m)) {
            return;
        }
        $index = (int) $m[1];
        $timeIndex = (int) $m[2];
        $times = $this->specific_dates[$index]['times'] ?? [];
        $counts = array_count_values($times);
        if (($counts[$value] ?? 0) > 1) {
            $others = $times;
            unset($others[$timeIndex]);
            $next = $this->nextAvailableTime(array_values($others));
            if ($next !== null) {
                $this->specific_dates[$index]['times'][$timeIndex] = $next;
            }
        }
    }

    public function addWeekDayTime(int $dayIndex): void
    {
        $existing = $this->week_days[$dayIndex]['times'] ?? [];
        $next = $this->nextAvailableTime($existing);
        if ($next !== null) {
            $this->week_days[$dayIndex]['times'][] = $next;
        }
    }

    public function removeWeekDayTime(int $dayIndex, int $timeIndex): void
    {
        if (count($this->week_days[$dayIndex]['times'] ?? []) > 1) {
            array_splice($this->week_days[$dayIndex]['times'], $timeIndex, 1);
        }
    }

    public function addSpecificDate(string $date): void
    {
        $existing = array_column($this->specific_dates, 'date');
        if ($date && ! in_array($date, $existing)) {
            $this->specific_dates[] = ['date' => $date, 'times' => ['09:00']];
            usort($this->specific_dates, fn ($a, $b) => strcmp($a['date'], $b['date']));
        }
    }

    public function removeSpecificDate(int $index): void
    {
        array_splice($this->specific_dates, $index, 1);
    }

    public function addTimeToDate(int $index): void
    {
        $existing = $this->specific_dates[$index]['times'] ?? [];
        $next = $this->nextAvailableTime($existing);
        if ($next !== null) {
            $this->specific_dates[$index]['times'][] = $next;
        }
    }

    public function removeTimeFromDate(int $index, int $timeIndex): void
    {
        if (count($this->specific_dates[$index]['times'] ?? []) > 1) {
            array_splice($this->specific_dates[$index]['times'], $timeIndex, 1);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function weekDaysDescription(): string
    {
        if (empty($this->week_days)) {
            return __('Choose days of the week below');
        }

        $dayNames = $this->dayNameMap();
        $sorted = $this->week_days;
        usort($sorted, fn ($a, $b) => (int) ($a['day'] ?? 0) <=> (int) ($b['day'] ?? 0));

        return implode(', ', array_map(function ($e) use ($dayNames) {
            $label = $dayNames[(int) ($e['day'] ?? 0)] ?? (int) ($e['day'] ?? 0);
            $times = $e['times'] ?? [];
            if (! empty($times)) {
                $label .= ' '.implode('/', $times);
            }

            return $label;
        }, $sorted));
    }

    private function cyclicalDescription(string $allTimes): string
    {
        $n = $this->cyclical_value ?? 1;
        $unit = $this->cyclical_unit ?? 'weeks';
        $dayNames = $this->dayNameMap();
        $monthAbbr = $this->monthAbbrMap();
        $posLabels = $this->positionLabelMap();

        if ($unit === 'days' && $this->cyclical_use_for) {
            return __('Active for :use days, off for :pause days', [
                'use' => $this->cyclical_use_for,
                'pause' => $this->cyclical_pause_for ?? 0,
            ]);
        }

        if ($unit === 'weeks') {
            $base = trans_choice('Every :count week|Every :count weeks', $n, ['count' => $n]);
            if (! empty($this->cyclical_week_days)) {
                $sorted = $this->cyclical_week_days;
                sort($sorted);
                $base .= ' '.__('on').' '.implode(', ', array_map(fn ($d) => $dayNames[(int) $d] ?? $d, $sorted));
            }

            return $base.$allTimes;
        }

        if ($unit === 'months') {
            $base = trans_choice('Every :count month|Every :count months', $n, ['count' => $n]);
            if ($this->cyclical_month_type === 'each' && ! empty($this->cyclical_month_days)) {
                $sorted = $this->cyclical_month_days;
                sort($sorted);
                $dayStr = $this->andJoin(array_map(fn ($d) => $this->ordinal((int) $d), $sorted));

                return $base.' '.__('on the').' '.$dayStr.$allTimes;
            }
            if ($this->cyclical_month_type === 'on_the' && $this->cyclical_month_position && $this->cyclical_month_weekday) {
                $pos = $posLabels[$this->cyclical_month_position] ?? $this->cyclical_month_position;
                $day = $dayNames[(int) $this->cyclical_month_weekday] ?? $this->cyclical_month_weekday;

                return $base.' '.__('on the :pos :day', ['pos' => $pos, 'day' => $day]).$allTimes;
            }

            return $base.$allTimes;
        }

        if ($unit === 'years') {
            $base = trans_choice('Every :count year|Every :count years', $n, ['count' => $n]);
            if (! empty($this->cyclical_year_months)) {
                $sorted = $this->cyclical_year_months;
                sort($sorted);
                $base .= ' '.__('in').' '.implode(', ', array_map(fn ($m) => $monthAbbr[(int) $m] ?? $m, $sorted));
            }
            if ($this->cyclical_year_use_weekday && $this->cyclical_month_position && $this->cyclical_month_weekday) {
                $pos = $posLabels[$this->cyclical_month_position] ?? $this->cyclical_month_position;
                $day = $dayNames[(int) $this->cyclical_month_weekday] ?? $this->cyclical_month_weekday;
                $base .= ' '.__('on the :pos :day', ['pos' => $pos, 'day' => $day]);
            } elseif (! $this->cyclical_year_use_weekday && $this->cyclical_year_day) {
                $base .= ' '.__('on the :day', ['day' => $this->ordinal((int) $this->cyclical_year_day)]);
            }

            return $base.$allTimes;
        }

        return trans_choice('Every :count day|Every :count days', $n, ['count' => $n]).$allTimes;
    }

    /**
     * @return array<int, Carbon>
     */
    private function computeUpcomingDates(Carbon $upFrom, ?Carbon $upEnd): array
    {
        $dates = [];

        if ($this->schedule_type === 'every_day') {
            $c = $upFrom->copy();
            for ($i = 0; $i < 4; $i++) {
                if ($upEnd && $c->gt($upEnd)) {
                    break;
                }
                $dates[] = $c->copy();
                $c->addDay();
            }
        } elseif ($this->schedule_type === 'week_days' && ! empty($this->week_days)) {
            $sel = array_map(fn ($e) => is_array($e) ? (int) ($e['day'] ?? 0) : (int) $e, $this->week_days);
            $c = $upFrom->copy();
            for ($a = 0; count($dates) < 4 && $a < 100; $a++, $c->addDay()) {
                if ($upEnd && $c->gt($upEnd)) {
                    break;
                }
                if (in_array((int) $c->dayOfWeekIso, $sel)) {
                    $dates[] = $c->copy();
                }
            }
        } elseif ($this->schedule_type === 'specific_dates' && ! empty($this->specific_dates)) {
            $today = now()->startOfDay();
            $allDates = array_filter(array_map(fn ($e) => is_array($e) ? ($e['date'] ?? '') : $e, $this->specific_dates));
            sort($allDates);
            foreach ($allDates as $d) {
                if (! $d) {
                    continue;
                }
                $date = Carbon::createFromFormat('Y-m-d', $d)->startOfDay();
                if ($date->lt($today) || ($upEnd && $date->gt($upEnd))) {
                    continue;
                }
                $dates[] = $date;
                if (count($dates) >= 4) {
                    break;
                }
            }
        } elseif ($this->schedule_type === 'cyclical') {
            $dates = $this->computeCyclicalUpcoming($upFrom, $upEnd);
        }

        return $dates;
    }

    /**
     * @return array<int, Carbon>
     */
    private function computeCyclicalUpcoming(Carbon $upFrom, ?Carbon $upEnd): array
    {
        $dates = [];
        $n = max(1, (int) ($this->cyclical_value ?? 1));
        $unit = $this->cyclical_unit ?? 'days';

        if ($unit === 'days' && ! $this->cyclical_use_for) {
            $c = $upFrom->copy();
            for ($i = 0; $i < 4; $i++) {
                if ($upEnd && $c->gt($upEnd)) {
                    break;
                }
                $dates[] = $c->copy();
                $c->addDays($n);
            }
        } elseif ($unit === 'days' && $this->cyclical_use_for) {
            $useFor = max(1, (int) $this->cyclical_use_for);
            $pauseFor = max(0, (int) ($this->cyclical_pause_for ?? 0));
            $cycleLength = $useFor + $pauseFor;
            $c = $upFrom->copy();
            for ($a = 0; count($dates) < 4 && $a < 10000; $a++, $c->addDay()) {
                if ($upEnd && $c->gt($upEnd)) {
                    break;
                }
                if ((int) $upFrom->diffInDays($c, true) % $cycleLength < $useFor) {
                    $dates[] = $c->copy();
                }
            }
        } elseif ($unit === 'weeks') {
            if (! empty($this->cyclical_week_days)) {
                $sel = array_map('intval', $this->cyclical_week_days);
                $fromWeek = $upFrom->copy()->startOfWeek(Carbon::MONDAY);
                $c = $upFrom->copy();
                for ($a = 0; count($dates) < 4 && $a < 200; $a++, $c->addDay()) {
                    if ($upEnd && $c->gt($upEnd)) {
                        break;
                    }
                    $wDiff = (int) abs($fromWeek->diffInWeeks($c->copy()->startOfWeek(Carbon::MONDAY)));
                    if ($wDiff % $n === 0 && in_array((int) $c->dayOfWeekIso, $sel)) {
                        $dates[] = $c->copy();
                    }
                }
            } else {
                $c = $upFrom->copy();
                for ($i = 0; $i < 4; $i++) {
                    if ($upEnd && $c->gt($upEnd)) {
                        break;
                    }
                    $dates[] = $c->copy();
                    $c->addWeeks($n);
                }
            }
        } elseif ($unit === 'months' && $this->cyclical_month_type === 'each' && ! empty($this->cyclical_month_days)) {
            $mDays = array_map('intval', $this->cyclical_month_days);
            $fromBase = $upFrom->year * 12 + ($upFrom->month - 1);
            $c = $upFrom->copy();
            for ($a = 0; count($dates) < 4 && $a < 1500; $a++, $c->addDay()) {
                if ($upEnd && $c->gt($upEnd)) {
                    break;
                }
                $mDiff = ($c->year * 12 + ($c->month - 1)) - $fromBase;
                if ($mDiff >= 0 && $mDiff % $n === 0 && in_array((int) $c->day, $mDays)) {
                    $dates[] = $c->copy();
                }
            }
        } elseif ($unit === 'months' && $this->cyclical_month_type === 'on_the' && $this->cyclical_month_position && $this->cyclical_month_weekday) {
            $posMap = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'last' => -1];
            $occurrence = $posMap[$this->cyclical_month_position] ?? 1;
            $targetDow = (int) $this->cyclical_month_weekday;
            $fromBase = $upFrom->year * 12 + ($upFrom->month - 1);
            $c = Carbon::create($upFrom->year, $upFrom->month, 1)->startOfDay();
            for ($a = 0; count($dates) < 4 && $a < 200; $a++, $c->addMonth()) {
                $mDiff = ($c->year * 12 + ($c->month - 1)) - $fromBase;
                if ($mDiff < 0 || $mDiff % $n !== 0) {
                    continue;
                }
                $date = $this->nthWeekdayOfMonth($c->year, $c->month, $targetDow, $occurrence);
                if (! $date || $date->lt($upFrom) || ($upEnd && $date->gt($upEnd))) {
                    continue;
                }
                $dates[] = $date;
            }
        } elseif ($unit === 'years' && ! empty($this->cyclical_year_months)) {
            $posMap = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'last' => -1];
            $yearMonths = array_map('intval', $this->cyclical_year_months);
            sort($yearMonths);
            $fromYear = $upFrom->year;
            for ($yr = $fromYear; count($dates) < 4 && $yr <= $fromYear + $n * 8; $yr++) {
                if (($yr - $fromYear) % $n !== 0) {
                    continue;
                }
                foreach ($yearMonths as $month) {
                    if (count($dates) >= 4) {
                        break;
                    }
                    if ($this->cyclical_year_use_weekday && $this->cyclical_month_position && $this->cyclical_month_weekday) {
                        $occurrence = $posMap[$this->cyclical_month_position] ?? 1;
                        $date = $this->nthWeekdayOfMonth($yr, $month, (int) $this->cyclical_month_weekday, $occurrence);
                        if (! $date) {
                            continue;
                        }
                    } else {
                        $day = $this->cyclical_year_day ? (int) $this->cyclical_year_day : 1;
                        $lastDay = Carbon::create($yr, $month)->endOfMonth()->day;
                        $date = Carbon::create($yr, $month, min($day, $lastDay))->startOfDay();
                    }
                    if ($date->lt($upFrom) || ($upEnd && $date->gt($upEnd))) {
                        continue;
                    }
                    $dates[] = $date;
                }
            }
        }

        return $dates;
    }

    private function nthWeekdayOfMonth(int $year, int $month, int $targetDow, int $occurrence): ?Carbon
    {
        if ($occurrence === -1) {
            $date = Carbon::create($year, $month)->endOfMonth()->startOfDay();
            while ((int) $date->dayOfWeekIso !== $targetDow) {
                $date->subDay();
            }

            return $date;
        }

        $date = Carbon::create($year, $month, 1)->startOfDay();
        $count = 0;
        while ($date->month === $month) {
            if ((int) $date->dayOfWeekIso === $targetDow) {
                $count++;
                if ($count === $occurrence) {
                    return $date;
                }
            }
            $date->addDay();
        }

        return null;
    }

    /** @return array<int, string> */
    private function timesForDate(Carbon $date): array
    {
        if ($this->schedule_type === 'week_days') {
            $iso = (int) $date->dayOfWeekIso;
            $entry = collect($this->week_days)->first(fn ($e) => (int) ($e['day'] ?? 0) === $iso);
            $times = $entry['times'] ?? [];
        } elseif ($this->schedule_type === 'specific_dates') {
            $dateStr = $date->format('Y-m-d');
            $entry = collect($this->specific_dates)->first(fn ($e) => is_array($e) ? ($e['date'] ?? '') === $dateStr : $e === $dateStr);
            $times = is_array($entry) ? ($entry['times'] ?? []) : [];
        } else {
            $times = $this->times ?? [];
        }

        sort($times);

        return $times;
    }

    private function nextAvailableTime(array $existing): ?string
    {
        for ($h = 9; $h <= 23; $h++) {
            $t = sprintf('%02d:00', $h);
            if (! in_array($t, $existing)) {
                return $t;
            }
        }
        for ($h = 0; $h <= 8; $h++) {
            $t = sprintf('%02d:00', $h);
            if (! in_array($t, $existing)) {
                return $t;
            }
        }

        return null;
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

    private function andJoin(array $items): string
    {
        if (count($items) <= 1) {
            return implode('', $items);
        }
        $last = array_pop($items);

        return implode(', ', $items).' '.__('and').' '.$last;
    }

    /** @return array<int, string> */
    private function dayNameMap(): array
    {
        return [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
    }

    /** @return array<int, string> */
    private function monthAbbrMap(): array
    {
        return [1 => __('Jan'), 2 => __('Feb'), 3 => __('Mar'), 4 => __('Apr'), 5 => __('May'), 6 => __('Jun'), 7 => __('Jul'), 8 => __('Aug'), 9 => __('Sep'), 10 => __('Oct'), 11 => __('Nov'), 12 => __('Dec')];
    }

    /** @return array<string, string> */
    private function positionLabelMap(): array
    {
        return ['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('last')];
    }
}
