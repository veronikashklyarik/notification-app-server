@php
    $scheduleTypeEnum = \App\Enums\ScheduleType::class;
    $moreOptionsSummaryWarning = false;
    if ($schedule_type === $scheduleTypeEnum::AsNeeded->value) {
        $moreOptionsSummary = __('Marked by hand · nothing is ever sent');
    } elseif (! $is_active) {
        $moreOptionsSummary = __('notifications off');
        $moreOptionsSummaryWarning = true;
    } else {
        $repeatSummary = $reminderInterval
            ? \Illuminate\Support\Str::lower(__(\App\Models\Notification::REMINDER_INTERVALS[$reminderInterval] ?? ''))
            : __('no repeat');

        if ($schedule_type === $scheduleTypeEnum::SpecificDates->value) {
            $moreOptionsSummary = $repeatSummary;
        } else {
            $startLabel = __('Starts :date', ['date' => ($starts_at ? \Illuminate\Support\Carbon::parse($starts_at) : now())->translatedFormat('j M')]);
            $endLabel = $ends_at ? __('ends :date', ['date' => \Illuminate\Support\Carbon::parse($ends_at)->translatedFormat('j M')]) : __('no end date');
            $moreOptionsSummary = $startLabel.' · '.$endLabel.' · '.$repeatSummary;
        }
    }
@endphp

<form id="reminder-form" wire:submit="save" class="space-y-3.5"
      x-data="{ moreOpen: {{ ($expandMore ?? false) ? 'true' : 'false' }} }"
      x-init="$wire.on('scroll-to-error', () => $nextTick(() => $el.querySelector('[data-error-anchor]')?.scrollIntoView({ behavior: 'smooth', block: 'center' })))">
    <div>
        <input type="text" wire:model="name" data-name-input required
               @if($errors->has('name')) aria-invalid="true" data-error-anchor @endif
               class="w-full h-12 px-4 rounded-field border bg-white text-[17px] font-semibold text-ink placeholder:text-text-quaternary placeholder:font-normal {{ $errors->has('name') ? 'border-danger' : 'border-border' }}"
               placeholder="{{ __('Title') }}">
        @error('name')
            <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <input type="text" wire:model="description"
           class="w-full h-12 px-4 rounded-field border border-border bg-white text-[15px] text-ink placeholder:text-text-quaternary"
           placeholder="{{ __('Note — optional') }}">

    @if($errors->any())
        <p class="-mt-1.5 px-1 text-[12px] text-text-quaternary">{{ trans_choice(':count thing to fix — the form scrolled to the first one.|:count things to fix — the form scrolled to the first one.', $errors->count(), ['count' => $errors->count()]) }}</p>
    @endif

    {{-- How often --}}
    <div>
        <p class="mb-1.5 px-1 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('How often') }}</p>
        <div class="space-y-1.5">
            @foreach([
                \App\Enums\ScheduleType::EveryDay->value => [__('Every day'), __('One or more times, every single day')],
                \App\Enums\ScheduleType::WeekDays->value => [__('On certain weekdays'), __('Each day can carry its own times')],
                \App\Enums\ScheduleType::Cyclical->value => [__('Custom cycle'), __('Every N days, weeks, months or years — plus on/off cycles')],
                \App\Enums\ScheduleType::SpecificDates->value => [__('Exact dates'), __('A fixed list of dates, each with its own times')],
                \App\Enums\ScheduleType::AsNeeded->value => [__('No schedule'), __('Kept in the list, never notifies')],
            ] as $type => [$optionLabel, $hint])
                @php $selected = $schedule_type === $type; @endphp
                <button type="button" wire:click="$set('schedule_type', '{{ $type }}')"
                        class="w-full flex items-center gap-3 p-3 rounded-field text-left {{ $selected ? 'bg-indigo-tint border-[1.5px] border-brand' : 'bg-white border border-border' }}">
                    <span class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center {{ $selected ? 'bg-brand' : 'border-[1.5px] border-border-input' }}">
                        @if($selected)
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @endif
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold {{ $selected ? 'text-indigo-tint-text' : 'text-ink' }}">{{ $optionLabel }}</span>
                        <span class="block text-[13px] text-text-tertiary">{{ $hint }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Type-specific controls, one titled card per schedule type --}}
    @if($schedule_type !== \App\Enums\ScheduleType::AsNeeded->value)
        <div class="rounded-card border bg-white p-4 space-y-3.5 {{ $errors->has('week_days') || $errors->has('specific_dates') ? 'border-danger-tint-border' : 'border-border' }}"
             @if($errors->has('week_days') || $errors->has('specific_dates')) data-error-anchor @endif>
            <p class="text-[15px] font-semibold text-ink">
                {{ match($schedule_type) {
                    \App\Enums\ScheduleType::EveryDay->value => __('At what times'),
                    \App\Enums\ScheduleType::WeekDays->value => __('Which days, and when'),
                    \App\Enums\ScheduleType::Cyclical->value => __('How the cycle works'),
                    \App\Enums\ScheduleType::SpecificDates->value => __('Which dates'),
                } }}
            </p>

            @if($schedule_type === \App\Enums\ScheduleType::WeekDays->value)
                @include('livewire.partials.schedule-type-weekdays')
            @endif

            @if($schedule_type === \App\Enums\ScheduleType::Cyclical->value)
                @include('livewire.partials.schedule-type-cyclical')
            @endif

            @if($schedule_type === \App\Enums\ScheduleType::SpecificDates->value)
                @include('livewire.partials.schedule-type-dates')
            @endif

            {{-- Times (every_day and cyclical only — week_days/specific_dates carry their own per-entry times) --}}
            @if(in_array($schedule_type, [\App\Enums\ScheduleType::EveryDay->value, \App\Enums\ScheduleType::Cyclical->value]))
                <div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($times as $index => $time)
                            <div class="flex flex-col" wire:key="time-chip-{{ $index }}">
                                <div class="flex items-center gap-1 pl-3 pr-1.5 h-10 rounded-chip bg-canvas-sunken border {{ $errors->has("times.$index") ? 'border-danger' : 'border-border' }}">
                                    <input type="time" wire:model.live="times.{{ $index }}" class="w-[72px] text-[14px] font-medium text-ink bg-transparent outline-none">
                                    @if(count($times) > 1)
                                        <button type="button" wire:click="removeTime({{ $index }})" class="w-6 h-6 rounded-full flex items-center justify-center text-text-tertiary" aria-label="{{ __('Remove time') }}">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                                                <path d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                @error("times.$index")
                                    <p class="mt-1 text-[12px] text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                        <button type="button" wire:click="addTime"
                                class="flex items-center px-3.5 h-10 rounded-chip border border-dashed border-border-dashed text-[14px] font-medium text-text-secondary">
                            {{ __('+ Add time') }}
                        </button>
                    </div>
                    <p class="mt-2 text-[12px] text-text-quaternary">{{ __('It notifies at every time in the list.') }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- More options — not shown for No schedule, which has nothing to time --}}
    @unless($schedule_type === \App\Enums\ScheduleType::AsNeeded->value)
        <div class="rounded-field border border-border bg-white overflow-hidden">
            <button type="button" @click="moreOpen = !moreOpen" class="w-full flex items-center justify-between gap-3 px-4 min-h-[56px]">
                <span class="shrink-0 text-[15px] font-semibold text-ink">{{ __('More options') }}</span>
                <span class="flex-1 min-w-0 text-[13px] text-right truncate {{ $moreOptionsSummaryWarning ? 'text-warning' : 'text-text-tertiary' }}" x-show="!moreOpen">{{ $moreOptionsSummary }}</span>
                <svg class="w-4 h-4 shrink-0 text-text-tertiary" x-show="moreOpen" x-cloak fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(180deg)">
                    <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div x-show="moreOpen" x-cloak x-transition class="px-4 pb-4 space-y-3.5 border-t border-border pt-3.5">
                @if($schedule_type !== \App\Enums\ScheduleType::SpecificDates->value)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Starts') }}</label>
                            <input type="date" wire:model.live="starts_at" lang="{{ app()->getLocale() }}" class="w-full h-11 px-3 rounded-control border border-border bg-canvas text-[14px] text-ink">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Ends') }}</label>
                            <input type="date" wire:model.live="ends_at" lang="{{ app()->getLocale() }}" class="w-full h-11 px-3 rounded-control border border-border bg-canvas text-[14px] text-ink">
                            <p class="mt-1 text-[12px] text-text-quaternary">{{ __('Blank = forever') }}</p>
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Repeat if not marked') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-tertiary">{{ __('Until you mark it') }}</p>
                    </div>
                    <select wire:model.live="reminderInterval" class="shrink-0 appearance-none bg-transparent border-none text-[15px] font-semibold text-brand text-right focus:ring-0 focus:outline-none">
                        <option value="">{{ __('Off') }}</option>
                        @foreach(\App\Models\Notification::REMINDER_INTERVALS as $minutes => $label)
                            <option value="{{ $minutes }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Notifications on') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-tertiary">{{ __('Off keeps it in the list, silent') }}</p>
                    </div>
                    <button type="button" wire:click="toggleIsActive"
                            class="shrink-0 relative w-[50px] h-[30px] rounded-full transition-colors {{ $is_active ? 'bg-brand' : 'bg-border-input' }}">
                        <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white transition-transform {{ $is_active ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                    </button>
                </div>
            </div>
        </div>
    @endunless

    {{-- Schedule sentence --}}
    @if($this->scheduleDescription)
        <div class="rounded-field bg-indigo-tint border border-indigo-tint-border px-3.5 py-3">
            <p class="text-[14px] leading-normal text-indigo-tint-text">{{ $this->scheduleDescription }}</p>
        </div>
    @endif
</form>
