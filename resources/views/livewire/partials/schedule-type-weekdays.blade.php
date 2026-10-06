@php
    $weekDayLabels = [1 => __('Mo'), 2 => __('Tu'), 3 => __('We'), 4 => __('Th'), 5 => __('Fr'), 6 => __('Sa'), 7 => __('Su')];
    $weekDayNames = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
    $selectedWeekDays = array_map(fn ($e) => (int) ($e['day'] ?? 0), (array) $week_days);
@endphp
<div class="space-y-2">
    <div class="flex gap-1.5">
        @foreach(\App\Enums\WeekDayPreset::cases() as $preset)
            <button type="button" wire:click="selectWeekDaysPreset('{{ $preset->value }}')"
                    class="px-2.5 h-8 rounded-pill bg-white border border-border text-[12px] font-semibold text-text-secondary">
                {{ $preset->label() }}
            </button>
        @endforeach
    </div>

    <div class="flex gap-1.5">
        @foreach($weekDayLabels as $dayNum => $label)
            @php $isSelected = in_array($dayNum, $selectedWeekDays, true); @endphp
            <button type="button" wire:click="toggleWeekDay({{ $dayNum }})"
                    class="flex-1 h-11 rounded-chip text-[13px] {{ $isSelected ? 'bg-ink text-white font-semibold' : 'bg-white border border-border text-text-tertiary font-medium' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @error('week_days')
        <p class="px-1 text-[13px] text-danger">{{ $message }}</p>
    @enderror

    <div class="flex gap-0.5 p-[3px] rounded-chip bg-fill">
        <button type="button" wire:click="toggleWeekDaysPerDay" @unless($week_days_per_day) disabled @endunless
                class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ ! $week_days_per_day ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
            {{ __('Same time every day') }}
        </button>
        <button type="button" wire:click="toggleWeekDaysPerDay" @if($week_days_per_day) disabled @endif
                class="flex-1 h-[38px] rounded-[10px] text-[13px] font-semibold {{ $week_days_per_day ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
            {{ __('Different per day') }}
        </button>
    </div>
</div>

@if($week_days_per_day)
    @if(!empty($week_days))
        <div class="border-t border-border-light">
            @foreach($week_days as $dayIndex => $entry)
                @php $entryTimes = $entry['times'] ?? ['09:00']; @endphp
                <div class="flex items-start gap-2 {{ !$loop->first ? 'border-t border-border-light' : '' }} py-1.5 min-h-11" wire:key="weekday-{{ $entry['day'] }}">
                    <span class="w-10 shrink-0 h-9 flex items-center text-[14px] font-semibold text-text-secondary">{{ $weekDayNames[(int) $entry['day']] ?? '' }}</span>
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        @foreach($entryTimes as $timeIndex => $time)
                            <div class="flex items-center gap-1 h-9 pl-3 pr-1.5 rounded-chip bg-canvas-sunken border {{ $errors->has("week_days.$dayIndex.times.$timeIndex") ? 'border-danger' : 'border-border' }}" wire:key="weekday-{{ $entry['day'] }}-time-{{ $timeIndex }}">
                                <input type="time" wire:model.live="week_days.{{ $dayIndex }}.times.{{ $timeIndex }}" class="w-[58px] text-[14px] font-medium text-ink bg-transparent outline-none">
                                @if(count($entryTimes) > 1)
                                    <button type="button" wire:click="removeWeekDayTime({{ $dayIndex }}, {{ $timeIndex }})" class="w-5 h-5 rounded-full flex items-center justify-center text-text-tertiary" aria-label="{{ __('Remove time') }}">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                                            <path d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" wire:click="addWeekDayTime({{ $dayIndex }})"
                                class="w-11 h-11 shrink-0 flex items-center justify-center text-[20px] leading-none text-brand" aria-label="{{ __('Add time on :day', ['day' => $weekDayNames[(int) $entry['day']] ?? '']) }}">+</button>
                    </div>
                </div>
                @foreach($entryTimes as $timeIndex => $time)
                    @error("week_days.$dayIndex.times.$timeIndex")
                        <p class="-mt-1 mb-1 pl-12 text-[12px] text-danger">{{ $message }}</p>
                    @enderror
                @endforeach
            @endforeach
        </div>
    @endif
@else
    <div>
        <div class="flex flex-wrap gap-2">
            @foreach($week_days_shared_times as $timeIndex => $time)
                <div class="flex flex-col" wire:key="shared-time-{{ $timeIndex }}">
                    <div class="flex items-center gap-1 pl-3 pr-1.5 h-10 rounded-chip bg-canvas-sunken border {{ $errors->has("week_days_shared_times.$timeIndex") ? 'border-danger' : 'border-border' }}">
                        <input type="time" wire:model.live="week_days_shared_times.{{ $timeIndex }}" class="w-[72px] text-[14px] font-medium text-ink bg-transparent outline-none">
                        @if(count($week_days_shared_times) > 1)
                            <button type="button" wire:click="removeWeekDaysSharedTime({{ $timeIndex }})" class="w-6 h-6 rounded-full flex items-center justify-center text-text-tertiary" aria-label="{{ __('Remove time') }}">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                    @error("week_days_shared_times.$timeIndex")
                        <p class="mt-1 text-[12px] text-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <button type="button" wire:click="addWeekDaysSharedTime"
                    class="flex items-center px-3.5 h-10 rounded-chip border border-dashed border-border-dashed text-[14px] font-medium text-text-secondary">
                {{ __('+ Add time') }}
            </button>
        </div>
    </div>
@endif
