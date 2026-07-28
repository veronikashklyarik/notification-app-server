<?php

namespace App\Livewire;

use App\Enums\ScheduleType;
use App\Livewire\Concerns\HasScheduleFields;
use App\Models\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class NotificationEdit extends Component
{
    use AuthorizesRequests;
    use HasScheduleFields;

    public Notification $notification;

    public string $name = '';

    public string $description = '';

    public string $backUrl = '';

    public ?int $reminderInterval = null;

    public function mount(Notification $notification): void
    {
        $this->authorize('update', $notification);

        $this->notification = $notification;
        $back = request()->query('back', '');
        $this->backUrl = ($back && str_starts_with($back, url('/'))) ? $back : route('notifications.show', $notification);
        $this->name = $notification->name;
        $this->description = $notification->description ?? '';
        $this->schedule_type = $notification->schedule_type->value;
        $this->week_days = $notification->week_days ?? [];
        $this->specific_dates = $notification->specific_dates ?? [];
        $this->every_n_days = $notification->every_n_days ?? 2;
        $this->cyclical_value = $notification->cyclical_value ?? 1;
        $this->cyclical_unit = $notification->cyclical_unit ?? 'days';
        $this->cyclical_week_days = $notification->cyclical_week_days ?? [];
        $this->cyclical_month_type = $notification->cyclical_month_type ?? 'each';
        $this->cyclical_month_days = $notification->cyclical_month_days ?? [];
        $this->cyclical_month_position = $notification->cyclical_month_position ?? 'first';
        $this->cyclical_month_weekday = $notification->cyclical_month_weekday ?? 1;
        $this->cyclical_year_months = $notification->cyclical_year_months ?? [];
        $this->cyclical_year_day = $notification->cyclical_year_day;
        $this->cyclical_year_use_weekday = $notification->cyclical_year_use_weekday ?? false;
        $this->cyclical_use_for = $notification->cyclical_use_for;
        $this->cyclical_pause_for = $notification->cyclical_pause_for;
        $this->times = $notification->times ?? ['08:00'];
        $this->starts_at = $notification->starts_at?->format('Y-m-d');
        $this->ends_at = $notification->ends_at?->format('Y-m-d');
        $this->is_active = $notification->is_active;
        $this->reminderInterval = $notification->reminder_interval;
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

    public function save(): void
    {
        $this->authorize('update', $this->notification);

        $this->starts_at = $this->starts_at ?: null;
        $this->ends_at = $this->ends_at ?: null;

        $validated = $this->validate();

        if (in_array($this->schedule_type, ['week_days', 'specific_dates'])) {
            $validated['times'] = null;
        }

        $validated['reminder_interval'] = $this->reminderInterval;

        $this->notification->update($validated);

        session()->flash('success', __('Reminder updated.'));

        $this->redirect($this->backUrl);
    }

    public function render(): View
    {
        return view('livewire.notification-edit');
    }
}
