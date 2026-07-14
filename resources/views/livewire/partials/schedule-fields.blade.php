{{-- Schedule type selector (2-column grid) --}}
<div>
    <label class="block mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Schedule') }}</label>
    <div class="grid grid-cols-2 gap-2">
        @foreach([
            'every_day'      => ['Every Day',      'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            'week_days'      => ['Specific Days',  'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            'cyclical'       => ['Custom',         'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
            'specific_dates' => ['Specific Dates', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ] as $value => [$label, $icon])
            <label class="relative cursor-pointer">
                <input type="radio" wire:model.live="schedule_type" value="{{ $value }}" class="peer sr-only">
                <div class="flex items-center gap-2.5 p-3 rounded-xl border-2 transition-all
                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50 peer-checked:shadow-sm
                    border-gray-200 hover:border-gray-300">
                    <svg class="w-4 h-4 shrink-0 text-gray-400 peer-[&]:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700">{{ __($label) }}</span>
                </div>
            </label>
        @endforeach
    </div>

    {{-- Dynamic description below the selector --}}
    @php
        $dayNames  = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
        $monthAbbr = [1 => __('Jan'), 2 => __('Feb'), 3 => __('Mar'), 4 => __('Apr'), 5 => __('May'), 6 => __('Jun'),
                      7 => __('Jul'), 8 => __('Aug'), 9 => __('Sep'), 10 => __('Oct'), 11 => __('Nov'), 12 => __('Dec')];
        $posLabels = ['first' => __('1st'), 'second' => __('2nd'), 'third' => __('3rd'), 'fourth' => __('4th'), 'fifth' => __('5th'), 'last' => __('last')];
        $allTimes  = !empty($times) ? (' ' . __('at :times', ['times' => implode(', ', $times)])) : '';

        $ordinal = function(int $n): string {
            $locale = app()->getLocale();
            if ($locale === 'ru') return $n . __('ordinal_suffix');
            if ($locale === 'pl') return $n . '.';
            $mod100 = $n % 100;
            $mod10  = $n % 10;
            if ($mod100 >= 11 && $mod100 <= 13) return $n . 'th';
            return $n . match($mod10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
        };
        $andJoin = function(array $items) use (&$andJoin): string {
            if (count($items) <= 1) return implode('', $items);
            $last = array_pop($items);
            return implode(', ', $items) . ' ' . __('and') . ' ' . $last;
        };

        $scheduleDesc = match($schedule_type) {
            'every_day' => __('Every day') . $allTimes,
            'week_days' => (function() use ($week_days, $dayNames) {
                if (empty($week_days)) return __('Choose days of the week below');
                $days = array_map(fn($e) => is_array($e) ? (int)($e['day'] ?? 0) : (int)$e, $week_days);
                sort($days);
                return implode(', ', array_map(fn($d) => $dayNames[$d] ?? $d, $days));
            })(),
            'cyclical' => (function() use ($cyclical_value, $cyclical_unit, $cyclical_week_days, $cyclical_month_type, $cyclical_month_days, $cyclical_month_position, $cyclical_month_weekday, $cyclical_year_months, $cyclical_year_day, $cyclical_year_use_weekday, $cyclical_use_for, $cyclical_pause_for, $dayNames, $monthAbbr, $posLabels, $allTimes, $ordinal, $andJoin) {
                $n = $cyclical_value ?? 1;
                $unit = $cyclical_unit ?? 'weeks';
                if ($unit === 'days' && $cyclical_use_for) {
                    return __('Active for :use days, off for :pause days', ['use' => $cyclical_use_for, 'pause' => $cyclical_pause_for ?? 0]);
                }
                if ($unit === 'weeks') {
                    $base = trans_choice('Every :count week|Every :count weeks', $n, ['count' => $n]);
                    if (!empty($cyclical_week_days)) {
                        $sorted = $cyclical_week_days; sort($sorted);
                        $base .= ' ' . __('on') . ' ' . implode(', ', array_map(fn($d) => $dayNames[(int)$d] ?? $d, $sorted));
                    }
                    return $base . $allTimes;
                }
                if ($unit === 'months') {
                    $base = trans_choice('Every :count month|Every :count months', $n, ['count' => $n]);
                    if ($cyclical_month_type === 'each' && !empty($cyclical_month_days)) {
                        $sorted = $cyclical_month_days; sort($sorted);
                        $dayStr = $andJoin(array_map(fn($d) => $ordinal((int)$d), $sorted));
                        return $base . ' ' . __('on the') . ' ' . $dayStr . $allTimes;
                    }
                    if ($cyclical_month_type === 'on_the' && $cyclical_month_position && $cyclical_month_weekday) {
                        $pos = $posLabels[$cyclical_month_position] ?? $cyclical_month_position;
                        $day = $dayNames[(int)$cyclical_month_weekday] ?? $cyclical_month_weekday;
                        return $base . ' ' . __('on the :pos :day', ['pos' => $pos, 'day' => $day]) . $allTimes;
                    }
                    return $base . $allTimes;
                }
                if ($unit === 'years') {
                    $base = trans_choice('Every :count year|Every :count years', $n, ['count' => $n]);
                    if (!empty($cyclical_year_months)) {
                        $sorted = $cyclical_year_months; sort($sorted);
                        $base .= ' ' . __('in') . ' ' . implode(', ', array_map(fn($m) => $monthAbbr[(int)$m] ?? $m, $sorted));
                    }
                    if ($cyclical_year_use_weekday && $cyclical_month_position && $cyclical_month_weekday) {
                        $pos = $posLabels[$cyclical_month_position] ?? $cyclical_month_position;
                        $day = $dayNames[(int)$cyclical_month_weekday] ?? $cyclical_month_weekday;
                        $base .= ' ' . __('on the :pos :day', ['pos' => $pos, 'day' => $day]);
                    } elseif (!$cyclical_year_use_weekday && $cyclical_year_day) {
                        $base .= ' ' . __('on the :day', ['day' => $ordinal((int)$cyclical_year_day)]);
                    }
                    return $base . $allTimes;
                }
                return trans_choice('Every :count day|Every :count days', $n, ['count' => $n]) . $allTimes;
            })(),
            'specific_dates' => empty($specific_dates)
                ? __('No dates added yet')
                : trans_choice(':count date selected|:count dates selected', count($specific_dates), ['count' => count($specific_dates)]),
            'as_needed' => __('Logged manually — no automatic reminders'),
            default => '',
        };
    @endphp
    @if($scheduleDesc)
        <p class="mt-3 text-sm text-indigo-700 bg-indigo-50 rounded-xl px-3.5 py-2.5 leading-relaxed">{{ $scheduleDesc }}</p>
    @endif
</div>

{{-- Week days picker + per-day times --}}
@if($schedule_type === 'week_days')
    @php
        $dayLabels     = [1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 7 => 'Su'];
        $selectedDays  = array_map(fn($e) => is_array($e) ? (int)($e['day'] ?? 0) : (int)$e, (array)$week_days);
        $weekDaysByDay = [];
        foreach ($week_days as $di => $entry) {
            $weekDaysByDay[(int)($entry['day'] ?? 0)] = ['index' => $di, 'times' => $entry['times'] ?? ['09:00']];
        }
    @endphp
    <div>
        <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Days of Week') }}</label>
        <div class="flex gap-1.5">
            @foreach($dayLabels as $dayNum => $label)
                @php $isSelected = in_array($dayNum, $selectedDays, true); @endphp
                <button type="button" wire:click="toggleWeekDay({{ $dayNum }})"
                    class="flex-1 flex items-center justify-center py-2.5 text-xs font-bold border-2 rounded-xl cursor-pointer transition-all
                        {{ $isSelected ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'text-gray-500 border-gray-200 hover:border-gray-300' }}">
                    {{ __($label) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Per-day time rows --}}
    @if(!empty($week_days))
        <div class="space-y-2">
            @foreach($week_days as $di => $entry)
                @php $entryDay = (int)($entry['day'] ?? 0); $entryTimes = $entry['times'] ?? ['09:00']; @endphp
                <div class="border-2 border-gray-200 rounded-xl p-3">
                    <p class="text-xs font-semibold text-gray-600 mb-2">{{ $dayNames[$entryDay] ?? '' }}</p>
                    @foreach($entryTimes as $ti => $time)
                        <div class="flex items-center gap-2 mb-1.5">
                            <input type="time" wire:model.live="week_days.{{ $di }}.times.{{ $ti }}" class="input-styled flex-1">
                            @if(count($entryTimes) > 1)
                                <button type="button" wire:click="removeWeekDayTime({{ $di }}, {{ $ti }})"
                                    aria-label="{{ __('Remove time') }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" wire:click="addWeekDayTime({{ $di }})"
                        class="flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500 mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Add time') }}
                    </button>
                </div>
            @endforeach
        </div>
    @endif
@endif

{{-- Cyclical options --}}
@if($schedule_type === 'cyclical')
    <div class="space-y-4">
        {{-- "Repeat every" is hidden for days+use_for (use/pause cycle controls the rhythm) --}}
        @if(!($cyclical_unit === 'days' && $cyclical_use_for))
            <div>
                <div class="flex items-center mb-2 gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Repeat every') }}</label>
                    <div x-data="{ t: false }" class="relative inline-flex items-center ml-0.5">
                        <button type="button" @mouseenter="t=true" @mouseleave="t=false"
                            class="w-4 h-4 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold hover:bg-gray-200 hover:text-gray-600 transition-colors flex items-center justify-center shrink-0">?</button>
                        <div x-show="t" x-cloak
                            class="absolute left-5 top-0 z-30 w-56 text-xs text-gray-600 bg-white border border-gray-200 rounded-xl p-3 shadow-xl leading-relaxed">
                            {{ __('How often the schedule repeats — every N days, weeks, months, or years.') }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="number" wire:model.live="cyclical_value" min="1"
                        class="input-styled w-24 text-center">
                    <select wire:model.live="cyclical_unit" class="input-styled flex-1">
                        @foreach(['days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months', 'years' => 'Years'] as $val => $lbl)
                            <option value="{{ $val }}">{{ __($lbl) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            {{-- Unit selector still needed to switch away from days --}}
            <div>
                <div class="flex items-center mb-2 gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Repeat every') }}</label>
                </div>
                <select wire:model.live="cyclical_unit" class="input-styled w-full">
                    @foreach(['days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months', 'years' => 'Years'] as $val => $lbl)
                        <option value="{{ $val }}">{{ __($lbl) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Days: Use for / Pause for --}}
        @if($cyclical_unit === 'days')
            <div x-data="{ hasCycle: {{ $cyclical_use_for ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" :checked="hasCycle"
                           @change="hasCycle = $event.target.checked; if (!hasCycle) { $wire.set('cyclical_use_for', null); $wire.set('cyclical_pause_for', null); }"
                           class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Cycle') }}</span>
                    <span class="text-xs font-normal text-gray-400 normal-case">{{ __('active for N days, then off for M days') }}</span>
                </label>

                <div x-show="hasCycle" x-cloak x-transition class="mt-3 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-xs text-gray-500">{{ __('Active for') }}</label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" wire:model.live="cyclical_use_for" min="1" placeholder="14"
                                    class="input-styled w-full text-center">
                                <span class="text-xs text-gray-400 shrink-0">{{ __('days') }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs text-gray-500">{{ __('Off for') }}</label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" wire:model.live="cyclical_pause_for" min="0" placeholder="7"
                                    class="input-styled w-full text-center">
                                <span class="text-xs text-gray-400 shrink-0">{{ __('days') }}</span>
                            </div>
                        </div>
                    </div>

                {{-- Cycle preview --}}
                @if($cyclical_use_for && $cyclical_pause_for)
                    @php
                        $useFor      = max(1, (int)$cyclical_use_for);
                        $pauseFor    = max(0, (int)$cyclical_pause_for);
                        $cycleLength = $useFor + $pauseFor;
                        $startDate   = $starts_at
                            ? \Illuminate\Support\Carbon::parse($starts_at)->startOfDay()
                            : now()->startOfDay();
                        $today       = now()->startOfDay();
                        $endsAt      = $ends_at ? \Illuminate\Support\Carbon::parse($ends_at)->endOfDay() : null;

                        $daysSince   = max(0, (int)$startDate->diffInDays($today, false));
                        $cycleNum    = (int)floor($daysSince / $cycleLength);
                        $phaseStart  = $startDate->copy()->addDays($cycleNum * $cycleLength);

                        $phases = [];
                        $cursor = $phaseStart->copy();
                        for ($i = 0; $i < 3; $i++) {
                            if ($endsAt && $cursor->gt($endsAt)) break;
                            $activeEnd = $cursor->copy()->addDays($useFor - 1);
                            // Clamp active end to ends_at
                            if ($endsAt && $activeEnd->gt($endsAt)) {
                                $activeEnd = $endsAt->copy()->startOfDay();
                            }
                            $phase = ['active_start' => $cursor->copy(), 'active_end' => $activeEnd->copy()];
                            // Only show pause if it starts before ends_at
                            if ($pauseFor > 0) {
                                $pauseStart = $cursor->copy()->addDays($useFor);
                                $pauseEnd   = $cursor->copy()->addDays($cycleLength - 1);
                                if (!$endsAt || $pauseStart->lte($endsAt)) {
                                    if ($endsAt && $pauseEnd->gt($endsAt)) {
                                        $pauseEnd = $endsAt->copy()->startOfDay();
                                    }
                                    $phase['pause_start'] = $pauseStart;
                                    $phase['pause_end']   = $pauseEnd;
                                }
                            }
                            $phases[] = $phase;
                            $cursor->addDays($cycleLength);
                        }
                    @endphp
                    @if(!empty($phases))
                        <div class="mt-3 bg-gray-50 border border-gray-100 rounded-xl p-3 space-y-1.5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('Schedule preview') }}</p>
                            @foreach($phases as $phase)
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                                    <span class="text-gray-700">{{ __('Active') }}: {{ $phase['active_start']->format('d M') }}–{{ $phase['active_end']->format('d M') }}</span>
                                </div>
                                @if(isset($phase['pause_start']))
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
                                        <span class="text-gray-400">{{ __('Off') }}: {{ $phase['pause_start']->format('d M') }}–{{ $phase['pause_end']->format('d M') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
                </div>{{-- /x-show hasCycle --}}
            </div>
        @endif

        {{-- Weekly: day-of-week selector --}}
        @if($cyclical_unit === 'weeks')
            <div>
                <div class="flex items-center mb-2 gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('On these days') }}</label>
                    <span class="text-xs font-normal text-gray-400 normal-case">({{ __('optional') }})</span>
                    <div x-data="{ t: false }" class="relative inline-flex items-center ml-0.5">
                        <button type="button" @mouseenter="t=true" @mouseleave="t=false"
                            class="w-4 h-4 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold hover:bg-gray-200 hover:text-gray-600 transition-colors flex items-center justify-center shrink-0">?</button>
                        <div x-show="t" x-cloak
                            class="absolute left-5 top-0 z-30 w-56 text-xs text-gray-600 bg-white border border-gray-200 rounded-xl p-3 shadow-xl leading-relaxed">
                            {{ __('Limit which weekdays fire within each active week. Leave blank to fire every day of the week.') }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-1.5">
                    @php $dayLabels = [1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 7 => 'Su']; @endphp
                    @foreach($dayLabels as $day => $label)
                        <label class="relative flex-1">
                            <input type="checkbox" wire:model.live="cyclical_week_days" value="{{ $day }}" class="peer sr-only">
                            <span class="flex items-center justify-center py-2.5 text-xs font-bold border-2 rounded-xl cursor-pointer transition-all
                                peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:shadow-sm
                                text-gray-500 border-gray-200 hover:border-gray-300">
                                {{ __($label) }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Monthly: Each (number grid) or On the Nth weekday --}}
        @if($cyclical_unit === 'months')
            <div>
                <div class="flex items-center mb-2 gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Monthly pattern') }}</label>
                    <div x-data="{ t: false }" class="relative inline-flex items-center ml-0.5">
                        <button type="button" @mouseenter="t=true" @mouseleave="t=false"
                            class="w-4 h-4 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold hover:bg-gray-200 hover:text-gray-600 transition-colors flex items-center justify-center shrink-0">?</button>
                        <div x-show="t" x-cloak
                            class="absolute left-5 top-0 z-30 w-60 text-xs text-gray-600 bg-white border border-gray-200 rounded-xl p-3 shadow-xl leading-relaxed">
                            {{ __("'Each' fires on specific dates (e.g. 1st and 15th). 'On the...' fires on the Nth weekday (e.g. 3rd Tuesday).") }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mb-3">
                    <label class="relative flex-1">
                        <input type="radio" wire:model.live="cyclical_month_type" value="each" class="peer sr-only">
                        <span class="flex items-center justify-center py-2.5 text-sm font-semibold border-2 rounded-xl cursor-pointer transition-all
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50 peer-checked:text-indigo-700
                            border-gray-200 text-gray-600 hover:border-gray-300">
                            {{ __('Each') }}
                        </span>
                    </label>
                    <label class="relative flex-1">
                        <input type="radio" wire:model.live="cyclical_month_type" value="on_the" class="peer sr-only">
                        <span class="flex items-center justify-center py-2.5 text-sm font-semibold border-2 rounded-xl cursor-pointer transition-all
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50 peer-checked:text-indigo-700
                            border-gray-200 text-gray-600 hover:border-gray-300">
                            {{ __('On the...') }}
                        </span>
                    </label>
                </div>

                @if($cyclical_month_type === 'each')
                    <div class="grid grid-cols-7 gap-1">
                        @for($day = 1; $day <= 31; $day++)
                            <label class="relative">
                                <input type="checkbox" wire:model.live="cyclical_month_days" value="{{ $day }}" class="peer sr-only">
                                <span class="flex items-center justify-center h-9 text-xs font-semibold rounded-lg border-2 cursor-pointer transition-all
                                    peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                    text-gray-600 border-gray-200 hover:border-gray-300">
                                    {{ $day }}
                                </span>
                            </label>
                        @endfor
                    </div>
                @endif

                @if($cyclical_month_type === 'on_the')
                    <div class="flex items-center gap-2 flex-wrap">
                        <select wire:model.live="cyclical_month_position" class="input-styled flex-1">
                            @foreach(['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'fourth' => '4th', 'fifth' => '5th', 'last' => 'Last'] as $val => $lbl)
                                <option value="{{ $val }}">{{ __($lbl) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="cyclical_month_weekday" class="input-styled flex-1">
                            @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $num => $name)
                                <option value="{{ $num }}">{{ __($name) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif

        {{-- Yearly: month grid --}}
        @if($cyclical_unit === 'years')
            <div>
                <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Months') }}</label>
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach([1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'] as $num => $abbr)
                        <label class="relative">
                            <input type="checkbox" wire:model.live="cyclical_year_months" value="{{ $num }}" class="peer sr-only">
                            <span class="flex items-center justify-center py-2.5 text-xs font-bold border-2 rounded-xl cursor-pointer transition-all
                                peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:shadow-sm
                                text-gray-500 border-gray-200 hover:border-gray-300">
                                {{ __($abbr) }}
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- Day of month (hidden when using weekday pattern) --}}
                @if(!$cyclical_year_use_weekday)
                    <div class="mt-3 flex items-center gap-2">
                        <label class="text-xs text-gray-500 shrink-0">{{ __('On the') }}</label>
                        <input type="number" wire:model.live="cyclical_year_day" min="1" max="31" placeholder="—"
                            class="input-styled w-20 text-center">
                        <span class="text-xs text-gray-400 shrink-0">{{ __('day of the month') }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">{{ __('For months with fewer days, the last available day will be used.') }}</p>
                @endif

                <label class="flex items-center gap-2.5 mt-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="cyclical_year_use_weekday" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span class="text-sm text-gray-600">{{ __('On specific weekday') }}</span>
                </label>

                @if($cyclical_year_use_weekday)
                    <div class="flex items-center gap-2 flex-wrap mt-2">
                        <select wire:model.live="cyclical_month_position" class="input-styled flex-1">
                            @foreach(['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'fourth' => '4th', 'fifth' => '5th', 'last' => 'Last'] as $val => $lbl)
                                <option value="{{ $val }}">{{ __($lbl) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="cyclical_month_weekday" class="input-styled flex-1">
                            @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $num => $name)
                                <option value="{{ $num }}">{{ __($name) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif

{{-- Specific dates picker (each date has its own times) --}}
@if($schedule_type === 'specific_dates')
    <div>
        <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Dates') }}</label>
        @forelse($specific_dates as $index => $entry)
            @php $dateStr = is_array($entry) ? ($entry['date'] ?? '') : $entry; $dateTimes = is_array($entry) ? ($entry['times'] ?? ['09:00']) : ['09:00']; @endphp
            <div class="border-2 border-gray-200 rounded-xl p-3 mb-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-gray-700">
                        {{ $dateStr ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateStr)->format('d M Y') : '' }}
                    </span>
                    <button type="button" wire:click="removeSpecificDate({{ $index }})"
                        aria-label="{{ __('Remove date') }}"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @foreach($dateTimes as $ti => $time)
                    <div class="flex items-center gap-2 mb-1.5">
                        <input type="time" wire:model.live="specific_dates.{{ $index }}.times.{{ $ti }}"
                            class="input-styled flex-1">
                        @if(count($dateTimes) > 1)
                            <button type="button" wire:click="removeTimeFromDate({{ $index }}, {{ $ti }})"
                                aria-label="{{ __('Remove time') }}"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
                <button type="button" wire:click="addTimeToDate({{ $index }})"
                    class="flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500 mt-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add time') }}
                </button>
            </div>
        @empty
            <p class="text-sm text-gray-400 mb-2">{{ __('No dates added yet.') }}</p>
        @endforelse
        <div x-data="{ newDate: '' }" class="flex items-center gap-2 mt-1">
            <input type="date" x-model="newDate" lang="{{ app()->getLocale() }}"
                class="input-styled flex-1"
                :min="new Date().toISOString().split('T')[0]">
            <button type="button"
                x-on:click="if(newDate) { $wire.addSpecificDate(newDate); newDate = ''; }"
                class="flex items-center gap-1 px-4 h-10 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add') }}
            </button>
        </div>
    </div>
@endif

{{-- Upcoming dates preview --}}
@php
    $upcomingDates = [];
    $upFrom = $starts_at
        ? \Illuminate\Support\Carbon::parse($starts_at)->startOfDay()
        : now()->startOfDay();
    $upEnd  = $ends_at ? \Illuminate\Support\Carbon::parse($ends_at)->endOfDay() : null;

    // Helper: find the Nth ISO weekday (1=Mon…7=Sun) occurrence in a given month.
    // $occurrence = 1..5 for first..fifth, -1 for last.
    // Returns Carbon|null (null when the month has no such occurrence).
    $nthWeekdayOfMonth = function (int $year, int $month, int $targetDow, int $occurrence): ?\Illuminate\Support\Carbon {
        if ($occurrence === -1) {
            $date = \Illuminate\Support\Carbon::create($year, $month)->endOfMonth()->startOfDay();
            while ((int) $date->dayOfWeekIso !== $targetDow) { $date->subDay(); }
            return $date;
        }
        $date  = \Illuminate\Support\Carbon::create($year, $month, 1)->startOfDay();
        $count = 0;
        while ($date->month === $month) {
            if ((int) $date->dayOfWeekIso === $targetDow) {
                $count++;
                if ($count === $occurrence) { return $date; }
            }
            $date->addDay();
        }
        return null;
    };

    if ($schedule_type === 'every_day') {
        $c = $upFrom->copy();
        for ($i = 0; $i < 4; $i++) {
            if ($upEnd && $c->gt($upEnd)) { break; }
            $upcomingDates[] = $c->copy();
            $c->addDay();
        }
    } elseif ($schedule_type === 'week_days' && !empty($week_days)) {
        $sel = array_map(fn($e) => is_array($e) ? (int)($e['day'] ?? 0) : (int)$e, (array) $week_days);
        $c   = $upFrom->copy();
        for ($a = 0; count($upcomingDates) < 4 && $a < 100; $a++, $c->addDay()) {
            if ($upEnd && $c->gt($upEnd)) { break; }
            if (in_array((int) $c->dayOfWeekIso, $sel)) { $upcomingDates[] = $c->copy(); }
        }
    } elseif ($schedule_type === 'specific_dates' && !empty($specific_dates)) {
        $today    = now()->startOfDay();
        $allDates = array_filter(array_map(fn($e) => is_array($e) ? ($e['date'] ?? '') : $e, $specific_dates));
        sort($allDates);
        foreach ($allDates as $d) {
            if (! $d) { continue; }
            $date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $d)->startOfDay();
            if ($date->lt($today)) { continue; }
            if ($upEnd && $date->gt($upEnd)) { continue; }
            $upcomingDates[] = $date;
            if (count($upcomingDates) >= 4) { break; }
        }
    } elseif ($schedule_type === 'cyclical') {
        $n    = max(1, (int) ($cyclical_value ?? 1));
        $unit = $cyclical_unit ?? 'days';

        if ($unit === 'days' && ! $cyclical_use_for) {
            $c = $upFrom->copy();
            for ($i = 0; $i < 4; $i++) {
                if ($upEnd && $c->gt($upEnd)) { break; }
                $upcomingDates[] = $c->copy();
                $c->addDays($n);
            }
        } elseif ($unit === 'days' && $cyclical_use_for) {
            $useFor      = max(1, (int) $cyclical_use_for);
            $pauseFor    = max(0, (int) ($cyclical_pause_for ?? 0));
            $cycleLength = $useFor + $pauseFor;
            $c           = $upFrom->copy();
            for ($a = 0; count($upcomingDates) < 4 && $a < 10000; $a++, $c->addDay()) {
                if ($upEnd && $c->gt($upEnd)) { break; }
                if ((int) $upFrom->diffInDays($c, true) % $cycleLength < $useFor) {
                    $upcomingDates[] = $c->copy();
                }
            }
        } elseif ($unit === 'weeks') {
            if (!empty($cyclical_week_days)) {
                $sel      = array_map('intval', (array) $cyclical_week_days);
                $fromWeek = $upFrom->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
                $c        = $upFrom->copy();
                for ($a = 0; count($upcomingDates) < 4 && $a < 200; $a++, $c->addDay()) {
                    if ($upEnd && $c->gt($upEnd)) { break; }
                    $wDiff = (int) abs($fromWeek->diffInWeeks($c->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)));
                    if ($wDiff % $n === 0 && in_array((int) $c->dayOfWeekIso, $sel)) { $upcomingDates[] = $c->copy(); }
                }
            } else {
                $c = $upFrom->copy();
                for ($i = 0; $i < 4; $i++) {
                    if ($upEnd && $c->gt($upEnd)) { break; }
                    $upcomingDates[] = $c->copy();
                    $c->addWeeks($n);
                }
            }
        } elseif ($unit === 'months' && $cyclical_month_type === 'each' && !empty($cyclical_month_days)) {
            $mDays    = array_map('intval', (array) $cyclical_month_days);
            $fromBase = $upFrom->year * 12 + ($upFrom->month - 1);
            $c        = $upFrom->copy();
            for ($a = 0; count($upcomingDates) < 4 && $a < 1500; $a++, $c->addDay()) {
                if ($upEnd && $c->gt($upEnd)) { break; }
                $mDiff = ($c->year * 12 + ($c->month - 1)) - $fromBase;
                if ($mDiff >= 0 && $mDiff % $n === 0 && in_array((int) $c->day, $mDays)) { $upcomingDates[] = $c->copy(); }
            }
        } elseif ($unit === 'months' && $cyclical_month_type === 'on_the' && $cyclical_month_position && $cyclical_month_weekday) {
            $posMap      = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'last' => -1];
            $occurrence  = $posMap[$cyclical_month_position] ?? 1;
            $targetDow   = (int) $cyclical_month_weekday;
            $fromBase    = $upFrom->year * 12 + ($upFrom->month - 1);
            $c           = \Illuminate\Support\Carbon::create($upFrom->year, $upFrom->month, 1)->startOfDay();
            for ($a = 0; count($upcomingDates) < 4 && $a < 200; $a++, $c->addMonth()) {
                $mDiff = ($c->year * 12 + ($c->month - 1)) - $fromBase;
                if ($mDiff < 0 || $mDiff % $n !== 0) { continue; }
                $date = $nthWeekdayOfMonth($c->year, $c->month, $targetDow, $occurrence);
                if (! $date || $date->lt($upFrom)) { continue; }
                if ($upEnd && $date->gt($upEnd)) { break; }
                $upcomingDates[] = $date;
            }
        } elseif ($unit === 'years' && !empty($cyclical_year_months)) {
            $posMap     = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'last' => -1];
            $yearMonths = array_map('intval', (array) $cyclical_year_months);
            sort($yearMonths);
            $fromYear = $upFrom->year;
            for ($yr = $fromYear; count($upcomingDates) < 4 && $yr <= $fromYear + $n * 8; $yr++) {
                if (($yr - $fromYear) % $n !== 0) { continue; }
                foreach ($yearMonths as $month) {
                    if (count($upcomingDates) >= 4) { break; }
                    if ($cyclical_year_use_weekday && $cyclical_month_position && $cyclical_month_weekday) {
                        $occurrence = $posMap[$cyclical_month_position] ?? 1;
                        $date       = $nthWeekdayOfMonth($yr, $month, (int) $cyclical_month_weekday, $occurrence);
                        if (! $date) { continue; }
                    } else {
                        $day     = $cyclical_year_day ? (int) $cyclical_year_day : 1;
                        $lastDay = \Illuminate\Support\Carbon::create($yr, $month)->endOfMonth()->day;
                        $date    = \Illuminate\Support\Carbon::create($yr, $month, min($day, $lastDay))->startOfDay();
                    }
                    if ($date->lt($upFrom)) { continue; }
                    if ($upEnd && $date->gt($upEnd)) { break; }
                    $upcomingDates[] = $date;
                }
            }
        }
    }
@endphp
@if(!empty($upcomingDates))
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('Upcoming') }}</p>
        <div class="space-y-1.5">
            @foreach($upcomingDates as $upDate)
                @php
                    if ($schedule_type === 'week_days') {
                        $upIso   = (int) $upDate->dayOfWeekIso;
                        $upEntry = collect($week_days)->first(fn($e) => (int)($e['day'] ?? 0) === $upIso);
                        $upTimes = $upEntry['times'] ?? [];
                    } elseif ($schedule_type === 'specific_dates') {
                        $upDateStr = $upDate->format('Y-m-d');
                        $upEntry   = collect($specific_dates)->first(fn($e) => is_array($e) ? ($e['date'] ?? '') === $upDateStr : $e === $upDateStr);
                        $upTimes   = is_array($upEntry) ? ($upEntry['times'] ?? []) : [];
                    } else {
                        $upTimes = $times ?? [];
                    }
                    sort($upTimes);
                @endphp
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span>
                    <span class="font-medium">{{ $upDate->format('D, d M Y') }}</span>
                    @if(!empty($upTimes))
                        <span class="text-gray-400">· {{ implode(', ', $upTimes) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Times (hidden for specific_dates and week_days since each date/day has its own) --}}
@if($schedule_type !== 'as_needed' && $schedule_type !== 'specific_dates' && $schedule_type !== 'week_days')
    <div>
        <div class="flex items-center mb-2 gap-1">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Times') }}</label>
            <div x-data="{ t: false }" class="relative inline-flex items-center ml-0.5">
                <button type="button" @mouseenter="t=true" @mouseleave="t=false"
                    class="w-4 h-4 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold hover:bg-gray-200 hover:text-gray-600 transition-colors flex items-center justify-center shrink-0">?</button>
                <div x-show="t" x-cloak
                    class="absolute left-5 top-0 z-30 w-56 text-xs text-gray-600 bg-white border border-gray-200 rounded-xl p-3 shadow-xl leading-relaxed">
                    {{ __('Notify at each time in the list') }}
                </div>
            </div>
        </div>
        @foreach($times as $index => $time)
            <div class="flex items-center gap-2 mb-2">
                <input type="time" wire:model.live="times.{{ $index }}" class="input-styled flex-1">
                @if(count($times) > 1)
                    <button type="button" wire:click="removeTime({{ $index }})"
                        class="w-10 h-10 flex items-center justify-center rounded-xl text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        @endforeach
        <button type="button" wire:click="addTime" class="flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-500 mt-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Add time') }}
        </button>
    </div>
@endif

{{-- Date range --}}
@if($schedule_type !== 'specific_dates')
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="starts_at" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Start Date') }}</label>
            <input type="date" id="starts_at" wire:model.live="starts_at" lang="{{ app()->getLocale() }}" class="input-styled w-full">
            <p class="mt-1 text-xs text-gray-400">{{ __('Reminders begin from this date') }}</p>
        </div>
        <div>
            <label for="ends_at" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('End Date') }}</label>
            <input type="date" id="ends_at" wire:model.live="ends_at" lang="{{ app()->getLocale() }}" class="input-styled w-full">
            <p class="mt-1 text-xs text-gray-400">{{ __('Leave blank to repeat indefinitely') }}</p>
        </div>
    </div>
@endif
