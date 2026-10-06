<?php

namespace App\Livewire;

use App\Enums\ScheduleType;
use App\Livewire\Concerns\HasScheduleDescription;
use App\Livewire\Concerns\HasScheduleFields;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class NotificationCreate extends Component
{
    use HasScheduleDescription, HasScheduleFields;

    public string $name = '';

    public string $description = '';

    public string $backUrl = '';

    public ?int $reminderInterval = null;

    public bool $embedded = false;

    /** @var array<string, array<string, mixed>> */
    private const TEMPLATES = [
        'vitamins' => [
            'name' => 'Vitamins',
            'schedule_type' => ScheduleType::EveryDay,
            'times' => ['08:00'],
        ],
        'water' => [
            'name' => 'Water the plants',
            'schedule_type' => ScheduleType::Cyclical,
            'cyclical_unit' => 'days',
            'cyclical_value' => 3,
            'times' => ['19:00'],
        ],
        'rent' => [
            'name' => 'Pay rent',
            'schedule_type' => ScheduleType::Cyclical,
            'cyclical_unit' => 'months',
            'cyclical_month_type' => 'each',
            'cyclical_month_days' => [1],
            'times' => ['10:00'],
        ],
    ];

    public function mount(?string $template = null): void
    {
        $back = request()->query('back', '');
        $this->backUrl = ($back && str_starts_with($back, url('/'))) ? $back : route('notifications.index');

        $template = self::TEMPLATES[$template ?? request()->query('template', '')] ?? null;
        if ($template) {
            $this->name = __($template['name']);
            $this->schedule_type = $template['schedule_type']->value;
            $this->times = $template['times'];
            $this->cyclical_unit = $template['cyclical_unit'] ?? $this->cyclical_unit;
            $this->cyclical_value = $template['cyclical_value'] ?? $this->cyclical_value;
            $this->cyclical_month_type = $template['cyclical_month_type'] ?? $this->cyclical_month_type;
            $this->cyclical_month_days = $template['cyclical_month_days'] ?? $this->cyclical_month_days;
        }
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
            'reminderInterval' => ['nullable', 'integer', Rule::in(array_keys(Notification::REMINDER_INTERVALS))],
        ];

        if ($this->schedule_type === ScheduleType::WeekDays->value) {
            $rules['week_days'] = ['required', 'array', 'min:1'];
            $rules['week_days.*.day'] = ['required', 'integer', 'between:1,7'];
            $rules['week_days.*.times'] = ['required', 'array', 'min:1'];
            $rules['week_days.*.times.*'] = ['required', 'date_format:H:i'];
        }

        if ($this->schedule_type === ScheduleType::EveryNDays->value) {
            $rules['every_n_days'] = ['required', 'integer', 'min:1', 'max:365'];
        }

        if ($this->schedule_type === ScheduleType::Cyclical->value) {
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

        if ($this->schedule_type === ScheduleType::SpecificDates->value) {
            $rules['specific_dates'] = ['required', 'array', 'min:1'];
            $rules['specific_dates.*.date'] = ['required', 'date_format:Y-m-d'];
            $rules['specific_dates.*.times'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    public function closeEmbedded(): void
    {
        $this->dispatch('sheet-closed');
    }

    public function save(): void
    {
        $this->starts_at = $this->starts_at ?: null;
        $this->ends_at = $this->ends_at ?: null;

        $validated = $this->validateScheduleForm();

        if (in_array($this->schedule_type, [ScheduleType::WeekDays->value, ScheduleType::SpecificDates->value])) {
            $validated['times'] = null;
        }

        $validated['reminder_interval'] = $this->reminderInterval;

        $notification = Auth::user()->reminders()->create($validated);

        if ($this->embedded) {
            $this->dispatch('reminder-created', id: $notification->id, name: $notification->name);

            return;
        }

        session()->flash('success', __('Reminder created.'));
        session()->flash('createdReminder', ['id' => $notification->id, 'name' => $notification->name]);

        $this->redirect(route('notifications.index'));
    }

    public function render(): View
    {
        return view('livewire.notification-create');
    }
}
