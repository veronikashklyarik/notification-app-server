@php
    $cyclicalDayLabels = [1 => __('Mo'), 2 => __('Tu'), 3 => __('We'), 4 => __('Th'), 5 => __('Fr'), 6 => __('Sa'), 7 => __('Su')];
    $onOffMode = $cyclical_unit === 'days' && $cyclical_use_for;
@endphp

{{-- Repeat-every-N vs on/off-days cycle --}}
<div class="flex gap-0.5 p-[3px] rounded-chip bg-fill">
    <button type="button" wire:click="selectCyclicalMode('repeat_every')" @unless($onOffMode) disabled @endunless
            class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ ! $onOffMode ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
        {{ __('Repeat every N') }}
    </button>
    <button type="button" wire:click="selectCyclicalMode('on_off_days')" @if($onOffMode) disabled @endif
            class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ $onOffMode ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
        {{ __('On / off days') }}
    </button>
</div>

@unless($onOffMode)
    {{-- Repeat every --}}
    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <span class="shrink-0 text-[13px] text-text-secondary">{{ __('Every') }}</span>
            <input type="number" wire:model.live="cyclical_value" min="1"
                   class="w-[56px] h-11 rounded-control border border-border bg-white text-center text-[15px] font-semibold text-ink">
            <div class="flex-1 flex gap-1.5">
                @foreach(['days' => __('Days'), 'weeks' => __('Weeks'), 'months' => __('Months'), 'years' => __('Years')] as $unit => $unitLabel)
                    @php $unitSelected = $cyclical_unit === $unit; @endphp
                    <button type="button" wire:click="$set('cyclical_unit', '{{ $unit }}')"
                            class="flex-1 h-11 rounded-chip text-[13px] {{ $unitSelected ? 'bg-ink text-white font-semibold' : 'bg-white border border-border text-text-tertiary font-medium' }}">
                        {{ $unitLabel }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@else
    {{-- Days: active/off cycle fields + preview --}}
    <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Active for') }}</label>
                <div class="flex items-center gap-1.5">
                    <input type="number" wire:model.live="cyclical_use_for" min="1" placeholder="14"
                           class="w-full h-11 rounded-control border border-border bg-white text-center text-[14px] text-ink">
                    <span class="shrink-0 text-[13px] text-text-tertiary">{{ __('days') }}</span>
                </div>
            </div>
            <div>
                <label class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Then off for') }}</label>
                <div class="flex items-center gap-1.5">
                    <input type="number" wire:model.live="cyclical_pause_for" min="0" placeholder="7"
                           class="w-full h-11 rounded-control border border-border bg-white text-center text-[14px] text-ink">
                    <span class="shrink-0 text-[13px] text-text-tertiary">{{ __('days') }}</span>
                </div>
            </div>
        </div>

        @if(!empty($this->cyclicPhases))
            @php
                $barTotal = max(1, collect($this->cyclicPhases)->sum(fn ($p) => $p['active_start']->diffInDays($p['active_end']) + 1 + (isset($p['pause_start']) ? $p['pause_start']->diffInDays($p['pause_end']) + 1 : 0)));
            @endphp
            <div class="rounded-control bg-canvas-sunken border border-border-light p-3 space-y-2">
                <div class="flex gap-0.5 h-[10px] rounded-[5px] overflow-hidden">
                    @foreach($this->cyclicPhases as $phase)
                        @php $activeDays = $phase['active_start']->diffInDays($phase['active_end']) + 1; @endphp
                        <div class="bg-success" style="width: {{ $activeDays / $barTotal * 100 }}%"></div>
                        @if(isset($phase['pause_start']))
                            @php $pauseDays = $phase['pause_start']->diffInDays($phase['pause_end']) + 1; @endphp
                            <div class="bg-neutral-dot" style="width: {{ $pauseDays / $barTotal * 100 }}%"></div>
                        @endif
                    @endforeach
                </div>
                <div class="space-y-1.5">
                    @foreach($this->cyclicPhases as $phase)
                        <div class="flex items-center gap-2 text-[13px]">
                            <span class="w-2 h-2 rounded-full bg-success shrink-0"></span>
                            <span class="text-text-secondary">{{ __('Active :start – :end', ['start' => $phase['active_start']->translatedFormat('D, j M'), 'end' => $phase['active_end']->translatedFormat('D, j M')]) }}</span>
                        </div>
                        @if(isset($phase['pause_start']))
                            <div class="flex items-center gap-2 text-[13px]">
                                <span class="w-2 h-2 rounded-full bg-neutral-dot shrink-0"></span>
                                <span class="text-text-tertiary">{{ __('Off :start – :end', ['start' => $phase['pause_start']->translatedFormat('D, j M'), 'end' => $phase['pause_end']->translatedFormat('D, j M')]) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endunless

{{-- Weeks: optional day-of-week toggles --}}
@if($cyclical_unit === 'weeks')
    <div>
        <div class="flex gap-1.5">
            @foreach($cyclicalDayLabels as $day => $label)
                <label class="relative flex-1">
                    <input type="checkbox" wire:model.live="cyclical_week_days" value="{{ $day }}" class="peer sr-only">
                    <span class="flex items-center justify-center h-11 rounded-chip text-[13px] bg-white border border-border text-text-tertiary font-medium peer-checked:bg-ink peer-checked:text-white peer-checked:font-semibold peer-checked:border-ink">
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="mt-1.5 px-1 text-[12px] text-text-quaternary">{{ __('Leave blank to fire every day of that week.') }}</p>
    </div>
@endif

{{-- Months: Each (day grid) or On the (position + weekday) --}}
@if($cyclical_unit === 'months')
    <div class="space-y-3">
        <div class="flex gap-0.5 p-[3px] rounded-chip bg-fill">
            <button type="button" wire:click="$set('cyclical_month_type', 'each')" @if($cyclical_month_type === 'each') disabled @endif
                    class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ $cyclical_month_type === 'each' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
                {{ __('By date') }}
            </button>
            <button type="button" wire:click="$set('cyclical_month_type', 'on_the')" @if($cyclical_month_type === 'on_the') disabled @endif
                    class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ $cyclical_month_type === 'on_the' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
                {{ __('By weekday') }}
            </button>
        </div>

        @if($cyclical_month_type === 'each')
            <div class="grid grid-cols-7 gap-1.5">
                @for($day = 1; $day <= 31; $day++)
                    <label class="relative">
                        <input type="checkbox" wire:model.live="cyclical_month_days" value="{{ $day }}" class="peer sr-only">
                        <span class="flex items-center justify-center h-10 rounded-pill text-[13px] font-semibold bg-white text-text-secondary peer-checked:bg-brand peer-checked:text-white peer-checked:border-solid peer-checked:border-brand {{ $day >= 29 ? 'border border-dashed border-border-dashed' : 'border border-border' }}">
                            {{ $day }}
                        </span>
                    </label>
                @endfor
            </div>
            <p class="text-[12px] text-text-quaternary">{{ __('Days 29–31: in shorter months it fires on the last day.') }}</p>
        @endif

        @if($cyclical_month_type === 'on_the')
            <div class="flex items-center gap-2">
                <select wire:model.live="cyclical_month_position" class="flex-1 h-11 px-3 rounded-control border border-border bg-white text-[14px] text-ink">
                    @foreach(['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('Last')] as $val => $posLabel)
                        <option value="{{ $val }}">{{ $posLabel }}</option>
                    @endforeach
                </select>
                <select wire:model.live="cyclical_month_weekday" class="flex-1 h-11 px-3 rounded-control border border-border bg-white text-[14px] text-ink">
                    @foreach([1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')] as $num => $dayName)
                        <option value="{{ $num }}">{{ $dayName }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
@endif

{{-- Years: month grid + day-of-month or position/weekday --}}
@if($cyclical_unit === 'years')
    <div class="space-y-3">
        <div class="grid grid-cols-4 gap-1.5">
            @foreach([1 => __('Jan'), 2 => __('Feb'), 3 => __('Mar'), 4 => __('Apr'), 5 => __('May'), 6 => __('Jun'), 7 => __('Jul'), 8 => __('Aug'), 9 => __('Sep'), 10 => __('Oct'), 11 => __('Nov'), 12 => __('Dec')] as $num => $abbr)
                <label class="relative">
                    <input type="checkbox" wire:model.live="cyclical_year_months" value="{{ $num }}" class="peer sr-only">
                    <span class="flex items-center justify-center h-10 rounded-chip text-[13px] font-semibold bg-white border border-border text-text-tertiary peer-checked:bg-brand peer-checked:text-white peer-checked:border-brand">
                        {{ $abbr }}
                    </span>
                </label>
            @endforeach
        </div>

        @if(!$cyclical_year_use_weekday)
            <div class="flex items-center gap-2">
                <span class="shrink-0 text-[13px] text-text-secondary">{{ __('On the') }}</span>
                <input type="number" wire:model.live="cyclical_year_day" min="1" max="31" placeholder="—"
                       class="w-20 h-11 rounded-control border border-border bg-white text-center text-[14px] text-ink">
                <span class="shrink-0 text-[13px] text-text-tertiary">{{ __('day of the month') }}</span>
            </div>
            <p class="text-[12px] text-text-quaternary">{{ __('For shorter months, the last available day is used.') }}</p>
        @endif

        <label class="flex items-center gap-2.5">
            <input type="checkbox" wire:model.live="cyclical_year_use_weekday" class="w-[18px] h-[18px] rounded border-border-input text-brand focus:ring-brand">
            <span class="text-[14px] text-ink">{{ __('On specific weekday') }}</span>
        </label>

        @if($cyclical_year_use_weekday)
            <div class="flex items-center gap-2">
                <select wire:model.live="cyclical_month_position" class="flex-1 h-11 px-3 rounded-control border border-border bg-white text-[14px] text-ink">
                    @foreach(['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('Last')] as $val => $posLabel)
                        <option value="{{ $val }}">{{ $posLabel }}</option>
                    @endforeach
                </select>
                <select wire:model.live="cyclical_month_weekday" class="flex-1 h-11 px-3 rounded-control border border-border bg-white text-[14px] text-ink">
                    @foreach([1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')] as $num => $dayName)
                        <option value="{{ $num }}">{{ $dayName }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
@endif
