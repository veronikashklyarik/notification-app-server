<?php

namespace App\Livewire;

use App\Enums\ScheduleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class NotificationCreate extends Component
{
    public string $name = '';

    public string $description = '';

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

    public string $backUrl = '';

    public function mount(): void
    {
        $back = request()->query('back', '');
        $this->backUrl = ($back && str_starts_with($back, url('/'))) ? $back : route('notifications.index');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'schedule_type' => ['required', Rule::enum(ScheduleType::class)],
            'week_days' => ['nullable', 'array'],
            'week_days.*.day' => ['integer', 'between:1,7'],
            'week_days.*.times' => ['nullable', 'array'],
            'week_days.*.times.*' => ['date_format:H:i'],
            'specific_dates' => ['nullable', 'array'],
            'specific_dates.*.date' => ['date_format:Y-m-d'],
            'specific_dates.*.times' => ['nullable', 'array'],
            'specific_dates.*.times.*' => ['date_format:H:i'],
            'every_n_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'cyclical_value' => ['nullable', 'integer', 'min:1'],
            'cyclical_unit' => ['nullable', 'string', Rule::in(['days', 'weeks', 'months', 'years'])],
            'cyclical_week_days' => ['nullable', 'array'],
            'cyclical_week_days.*' => ['integer', 'between:1,7'],
            'cyclical_month_type' => ['nullable', 'string', Rule::in(['each', 'on_the'])],
            'cyclical_month_days' => ['nullable', 'array'],
            'cyclical_month_days.*' => ['integer', 'between:1,31'],
            'cyclical_month_position' => ['nullable', 'string', Rule::in(['first', 'second', 'third', 'fourth', 'fifth', 'last'])],
            'cyclical_month_weekday' => ['nullable', 'integer', 'between:1,7'],
            'cyclical_year_months' => ['nullable', 'array'],
            'cyclical_year_months.*' => ['integer', 'between:1,12'],
            'cyclical_year_day' => ['nullable', 'integer', 'between:1,31'],
            'cyclical_year_use_weekday' => ['boolean'],
            'cyclical_use_for' => ['nullable', 'integer', 'min:1'],
            'cyclical_pause_for' => ['nullable', 'integer', 'min:0'],
            'times' => ['nullable', 'array'],
            'times.*' => ['date_format:H:i'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];

        if ($this->schedule_type === 'week_days') {
            $rules['week_days'] = ['required', 'array', 'min:1'];
            $rules['week_days.*.day'] = ['required', 'integer', 'between:1,7'];
            $rules['week_days.*.times'] = ['required', 'array', 'min:1'];
            $rules['week_days.*.times.*'] = ['required', 'date_format:H:i'];
        }

        if ($this->schedule_type === 'every_n_days') {
            $rules['every_n_days'] = ['required', 'integer', 'min:1', 'max:365'];
        }

        if ($this->schedule_type === 'cyclical') {
            $rules['cyclical_value'] = ['required', 'integer', 'min:1'];
            $rules['cyclical_unit'] = ['required', 'string', Rule::in(['days', 'weeks', 'months', 'years'])];

            if ($this->cyclical_unit === 'weeks') {
                $rules['cyclical_week_days'] = ['nullable', 'array'];
            }

            if ($this->cyclical_unit === 'months') {
                $rules['cyclical_month_type'] = ['required', 'string', Rule::in(['each', 'on_the'])];
                if ($this->cyclical_month_type === 'each') {
                    $rules['cyclical_month_days'] = ['required', 'array', 'min:1'];
                } elseif ($this->cyclical_month_type === 'on_the') {
                    $rules['cyclical_month_position'] = ['required', 'string', Rule::in(['first', 'second', 'third', 'fourth', 'fifth', 'last'])];
                    $rules['cyclical_month_weekday'] = ['required', 'integer', 'between:1,7'];
                }
            }

            if ($this->cyclical_unit === 'years') {
                $rules['cyclical_year_months'] = ['required', 'array', 'min:1'];
                if ($this->cyclical_year_use_weekday) {
                    $rules['cyclical_month_position'] = ['required', 'string', Rule::in(['first', 'second', 'third', 'fourth', 'fifth', 'last'])];
                    $rules['cyclical_month_weekday'] = ['required', 'integer', 'between:1,7'];
                }
            }

            if ($this->cyclical_unit === 'days' && $this->cyclical_use_for) {
                $rules['cyclical_use_for'] = ['required', 'integer', 'min:1'];
                $rules['cyclical_pause_for'] = ['required', 'integer', 'min:0'];
            }
        }

        if ($this->schedule_type === 'specific_dates') {
            $rules['specific_dates'] = ['required', 'array', 'min:1'];
            $rules['specific_dates.*.date'] = ['required', 'date_format:Y-m-d'];
            $rules['specific_dates.*.times'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    public function addTime(): void
    {
        $this->times[] = '08:00';
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
            if ((int) ($entry['day'] ?? 0) === $day) {
                array_splice($this->week_days, $index, 1);
                $this->week_days = array_values($this->week_days);

                return;
            }
        }

        $this->week_days[] = ['day' => $day, 'times' => ['09:00']];
        usort($this->week_days, fn ($a, $b) => ($a['day'] ?? 0) <=> ($b['day'] ?? 0));
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

    public function removeTimeFromDate(int $index, int $timeIndex): void
    {
        if (count($this->specific_dates[$index]['times'] ?? []) > 1) {
            array_splice($this->specific_dates[$index]['times'], $timeIndex, 1);
        }
    }

    public function save(): void
    {
        $this->starts_at = $this->starts_at ?: null;
        $this->ends_at = $this->ends_at ?: null;

        $validated = $this->validate();

        if (in_array($this->schedule_type, ['week_days', 'specific_dates'])) {
            $validated['times'] = null;
        }

        $notification = Auth::user()->reminders()->create($validated);

        session()->flash('success', __('Reminder created.'));

        $this->redirect(route('notifications.show', $notification));
    }

    public function render(): View
    {
        return view('livewire.notification-create');
    }
}
