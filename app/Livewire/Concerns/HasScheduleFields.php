<?php

namespace App\Livewire\Concerns;

use App\Enums\ScheduleType;
use App\Enums\WeekDayPreset;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait HasScheduleFields
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Give it a name so you know what it’s for.'),
            'week_days.required' => __('Pick at least one day.'),
            'week_days.min' => __('Pick at least one day.'),
            'specific_dates.required' => __('Add at least one date.'),
            'specific_dates.min' => __('Add at least one date.'),
        ];
    }

    public string $schedule_type = ScheduleType::EveryDay->value;

    /** @var array<int, array{day: int, times: array<int, string>}> */
    public array $week_days = [];

    public bool $week_days_per_day = false;

    /** @var array<int, string> */
    public array $week_days_shared_times = ['09:00'];

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
        return match ($this->schedule_type) {
            ScheduleType::EveryDay->value => $this->everyDayDescription(),
            ScheduleType::WeekDays->value => $this->weekDaysDescription(),
            ScheduleType::Cyclical->value => $this->cyclicalDescription(),
            ScheduleType::SpecificDates->value => $this->specificDatesDescription(),
            ScheduleType::AsNeeded->value => __('No schedule — it never notifies. Mark it from its page whenever you take one, and History keeps count.'),
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
     * Detects whether the loaded week_days entries share one common times
     * list or carry their own, and seeds week_days_per_day / shared_times
     * accordingly. Call after assigning $this->week_days from the model.
     */
    public function detectWeekDaysMode(): void
    {
        if (empty($this->week_days)) {
            return;
        }

        $first = $this->week_days[0]['times'] ?? ['09:00'];
        $allSame = collect($this->week_days)->every(fn ($entry) => ($entry['times'] ?? []) === $first);

        $this->week_days_per_day = ! $allSame;
        $this->week_days_shared_times = $allSame ? $first : ['09:00'];
    }

    // -------------------------------------------------------------------------
    // Schedule actions (shared between Create and Edit)
    // -------------------------------------------------------------------------

    public function toggleIsActive(): void
    {
        $this->is_active = ! $this->is_active;
    }

    /**
     * Switches the "Custom cycle" type between a plain "repeat every N
     * <unit>" schedule and the days-only "active for N, then off for M"
     * cycle. On/off cycling only applies to the days unit.
     */
    public function selectCyclicalMode(string $mode): void
    {
        if ($mode === 'on_off_days') {
            $this->cyclical_unit = 'days';
            if (! $this->cyclical_use_for) {
                $this->cyclical_use_for = 14;
                $this->cyclical_pause_for = 7;
            }
        } else {
            $this->cyclical_use_for = null;
            $this->cyclical_pause_for = null;
        }
    }

    /**
     * Runs the duplicate-time guard plus the rule-based validation and, on
     * failure, dispatches an event telling the sheet to scroll to the first
     * invalid field before rethrowing.
     *
     * @return array<string, mixed>
     */
    protected function validateScheduleForm(): array
    {
        try {
            $this->assertNoDuplicateTimes();

            return $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('scroll-to-error');

            throw $e;
        }
    }

    /**
     * Blocks saving if any time group (the shared `times` list, or any one
     * week-day's / specific-date's own list) contains a duplicate time.
     */
    protected function assertNoDuplicateTimes(): void
    {
        $duplicate = $this->firstDuplicateTime($this->times);

        foreach ($this->week_days as $entry) {
            $duplicate ??= $this->firstDuplicateTime($entry['times'] ?? []);
        }

        foreach ($this->specific_dates as $entry) {
            $duplicate ??= $this->firstDuplicateTime($entry['times'] ?? []);
        }

        if ($duplicate !== null) {
            throw ValidationException::withMessages([
                'times' => __(':time is already in the list — pick another time.', ['time' => $duplicate]),
            ]);
        }
    }

    /** @param array<int, string> $times */
    private function firstDuplicateTime(array $times): ?string
    {
        $counts = array_count_values($times);

        foreach ($times as $time) {
            if (($counts[$time] ?? 0) > 1) {
                return $time;
            }
        }

        return null;
    }

    public function addTime(): void
    {
        $next = $this->nextAvailableTime($this->times);
        if ($next !== null) {
            $this->times[] = $next;
        }
        $this->resetErrorBag('times.*');
    }

    public function updatedTimes(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)$/', $key, $m)) {
            return;
        }
        $timeIndex = (int) $m[1];
        $this->resetErrorBag("times.$timeIndex");
        $counts = array_count_values($this->times);
        if (($counts[$value] ?? 0) > 1) {
            $this->addError("times.$timeIndex", __(':time is already in the list — pick another time.', ['time' => $value]));
        }
    }

    public function removeTime(int $index): void
    {
        if (count($this->times) > 1) {
            unset($this->times[$index]);
            $this->times = array_values($this->times);
        }
        $this->resetErrorBag('times.*');
    }

    public function toggleWeekDay(int $day): void
    {
        $this->resetErrorBag('week_days.*');

        foreach ($this->week_days as $index => $entry) {
            if ((int) $entry['day'] === $day) {
                array_splice($this->week_days, $index, 1);

                return;
            }
        }

        $times = $this->week_days_per_day ? ['09:00'] : $this->week_days_shared_times;
        $this->week_days[] = ['day' => $day, 'times' => $times];
        usort($this->week_days, fn ($a, $b) => $a['day'] <=> $b['day']);
    }

    /**
     * Bulk-selects a preset set of days for the "On certain weekdays" type.
     */
    public function selectWeekDaysPreset(string $preset): void
    {
        $presetCase = WeekDayPreset::tryFrom($preset);

        if (! $presetCase) {
            return;
        }

        $days = $presetCase->days();
        $existing = collect($this->week_days)->keyBy('day');
        $defaultTimes = $this->week_days_per_day ? ['09:00'] : $this->week_days_shared_times;

        $this->week_days = collect($days)
            ->map(fn ($day) => $existing->get($day) ?? ['day' => $day, 'times' => $defaultTimes])
            ->sortBy('day')
            ->values()
            ->all();

        $this->resetErrorBag('week_days.*');
    }

    public function toggleWeekDaysPerDay(): void
    {
        $this->week_days_per_day = ! $this->week_days_per_day;

        if (! $this->week_days_per_day) {
            if (! empty($this->week_days)) {
                $this->week_days_shared_times = $this->week_days[0]['times'] ?? ['09:00'];
            }
            foreach ($this->week_days as $index => $entry) {
                $this->week_days[$index]['times'] = $this->week_days_shared_times;
            }
        }

        $this->resetErrorBag('week_days.*');
        $this->resetErrorBag('week_days_shared_times.*');
    }

    public function addWeekDaysSharedTime(): void
    {
        $next = $this->nextAvailableTime($this->week_days_shared_times);
        if ($next !== null) {
            $this->week_days_shared_times[] = $next;
            foreach ($this->week_days as $index => $entry) {
                $this->week_days[$index]['times'][] = $next;
            }
        }
        $this->resetErrorBag('week_days_shared_times.*');
        $this->resetErrorBag('week_days.*');
    }

    public function removeWeekDaysSharedTime(int $timeIndex): void
    {
        if (count($this->week_days_shared_times) <= 1) {
            return;
        }

        array_splice($this->week_days_shared_times, $timeIndex, 1);
        foreach ($this->week_days as $index => $entry) {
            if (count($this->week_days[$index]['times'] ?? []) > 1) {
                array_splice($this->week_days[$index]['times'], $timeIndex, 1);
            }
        }
        $this->resetErrorBag('week_days_shared_times.*');
        $this->resetErrorBag('week_days.*');
    }

    public function updatedWeekDaysSharedTimes(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)$/', $key, $m)) {
            return;
        }
        $timeIndex = (int) $m[1];
        $this->resetErrorBag("week_days_shared_times.$timeIndex");
        $counts = array_count_values($this->week_days_shared_times);
        if (($counts[$value] ?? 0) > 1) {
            $this->addError("week_days_shared_times.$timeIndex", __(':time is already in the list — pick another time.', ['time' => $value]));
        }

        foreach ($this->week_days as $index => $entry) {
            $this->week_days[$index]['times'][$timeIndex] = $value;
        }
    }

    public function updatedWeekDays(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)\.times\.(\d+)$/', $key, $m)) {
            return;
        }
        $dayIndex = (int) $m[1];
        $timeIndex = (int) $m[2];
        $this->resetErrorBag("week_days.$dayIndex.times.$timeIndex");
        $times = $this->week_days[$dayIndex]['times'] ?? [];
        $counts = array_count_values($times);
        if (($counts[$value] ?? 0) > 1) {
            $this->addError("week_days.$dayIndex.times.$timeIndex", __(':time is already in the list — pick another time.', ['time' => $value]));
        }
    }

    public function updatedSpecificDates(mixed $value, ?string $key = null): void
    {
        if (! is_string($value) || $key === null || ! preg_match('/^(\d+)\.times\.(\d+)$/', $key, $m)) {
            return;
        }
        $index = (int) $m[1];
        $timeIndex = (int) $m[2];
        $this->resetErrorBag("specific_dates.$index.times.$timeIndex");
        $times = $this->specific_dates[$index]['times'] ?? [];
        $counts = array_count_values($times);
        if (($counts[$value] ?? 0) > 1) {
            $this->addError("specific_dates.$index.times.$timeIndex", __(':time is already in the list — pick another time.', ['time' => $value]));
        }
    }

    public function addWeekDayTime(int $dayIndex): void
    {
        $existing = $this->week_days[$dayIndex]['times'] ?? [];
        $next = $this->nextAvailableTime($existing);
        if ($next !== null) {
            $this->week_days[$dayIndex]['times'][] = $next;
        }
        $this->resetErrorBag('week_days.*');
    }

    public function removeWeekDayTime(int $dayIndex, int $timeIndex): void
    {
        if (count($this->week_days[$dayIndex]['times'] ?? []) > 1) {
            array_splice($this->week_days[$dayIndex]['times'], $timeIndex, 1);
        }
        $this->resetErrorBag('week_days.*');
    }

    public function addSpecificDate(string $date): void
    {
        $existing = array_column($this->specific_dates, 'date');
        if ($date && ! in_array($date, $existing)) {
            $this->specific_dates[] = ['date' => $date, 'times' => ['09:00']];
            usort($this->specific_dates, fn ($a, $b) => strcmp($a['date'], $b['date']));
        }
        $this->resetErrorBag('specific_dates.*');
    }

    public function removeSpecificDate(int $index): void
    {
        array_splice($this->specific_dates, $index, 1);
        $this->resetErrorBag('specific_dates.*');
    }

    public function addTimeToDate(int $index): void
    {
        $existing = $this->specific_dates[$index]['times'] ?? [];
        $next = $this->nextAvailableTime($existing);
        if ($next !== null) {
            $this->specific_dates[$index]['times'][] = $next;
        }
        $this->resetErrorBag('specific_dates.*');
    }

    public function removeTimeFromDate(int $index, int $timeIndex): void
    {
        if (count($this->specific_dates[$index]['times'] ?? []) > 1) {
            array_splice($this->specific_dates[$index]['times'], $timeIndex, 1);
        }
        $this->resetErrorBag('specific_dates.*');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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
}
