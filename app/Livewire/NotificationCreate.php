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

    /** @var array<int, int> */
    public array $week_days = [];

    public ?int $every_n_days = 2;

    public ?int $cyclical_value = 1;

    public string $cyclical_unit = 'days';

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
            'week_days.*' => ['integer', 'between:1,7'],
            'every_n_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'cyclical_value' => ['nullable', 'integer', 'min:1'],
            'cyclical_unit' => ['nullable', 'string', Rule::in(['days', 'weeks', 'months', 'years'])],
            'times' => ['nullable', 'array'],
            'times.*' => ['date_format:H:i'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];

        if ($this->schedule_type === 'week_days') {
            $rules['week_days'] = ['required', 'array', 'min:1'];
        }

        if ($this->schedule_type === 'every_n_days') {
            $rules['every_n_days'] = ['required', 'integer', 'min:1', 'max:365'];
        }

        if ($this->schedule_type === 'cyclical') {
            $rules['cyclical_value'] = ['required', 'integer', 'min:1'];
            $rules['cyclical_unit'] = ['required', 'string', Rule::in(['days', 'weeks', 'months', 'years'])];
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

    public function save(): void
    {
        $validated = $this->validate();

        $notification = Auth::user()->reminders()->create($validated);

        session()->flash('success', 'Reminder created.');

        $this->redirect(route('notifications.show', $notification));
    }

    public function render(): View
    {
        return view('livewire.notification-create');
    }
}
