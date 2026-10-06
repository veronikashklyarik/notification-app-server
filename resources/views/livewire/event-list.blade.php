@php
    use App\Enums\EventStatus;
    use Illuminate\Support\Carbon;

    $tz = auth()->user()->timezone ?? 'UTC';
    $weekPercent = $weekMarksCount > 0 ? (int) round($weekDoneCount / $weekMarksCount * 100) : 0;
    $monthPercent = $monthMarksCount > 0 ? (int) round($monthDoneCount / $monthMarksCount * 100) : 0;
    $weekdayLetters = collect(range(0, 6))->map(fn ($i) => mb_substr(Carbon::now($tz)->startOfWeek(Carbon::MONDAY)->addDays($i)->translatedFormat('D'), 0, 1));

    $selectedDayLabel = $selectedDay ? Carbon::createFromFormat('Y-m-d', $selectedDay, $tz)->translatedFormat('l, j F') : null;
    $selectedDayDoneCount = $selectedDay ? $events->filter(fn ($e) => $e->status === EventStatus::Done)->count() : 0;
    $selectedDayTotal = $selectedDay ? $events->count() : 0;

    $groupedEvents = $events->groupBy(fn ($event) => $event->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d'));
@endphp
<div x-data="{
    historyFilter: 'all',
}" x-on:history-day-changed.window="historyFilter = 'all'">
<x-pull-to-refresh>
    <div class="px-5 md:px-0 pt-6 md:pt-0 pb-4">
        <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('History') }}</h1>
    </div>

    @if($hasAnyHistory)
    <div class="px-5 md:px-0">
        {{-- Week / Month segment --}}
        <div class="flex gap-0.5 p-[3px] rounded-chip bg-fill">
            <button type="button" wire:click="setPeriod('week')"
                    class="flex-1 h-9 rounded-[10px] text-[13px] font-semibold {{ $period === 'week' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
                {{ __('Week') }}
            </button>
            <button type="button" wire:click="setPeriod('month')"
                    class="flex-1 h-9 rounded-[10px] text-[13px] font-semibold {{ $period === 'month' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(16,20,40,0.08)]' : 'text-text-secondary' }}">
                {{ __('Month') }}
            </button>
        </div>

        {{-- Period card --}}
        <div class="mt-3 rounded-card bg-white border border-border p-[18px]">
            @if($period === 'week')
                <div wire:key="history-period-week">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" wire:click="prevPeriod" aria-label="{{ __('Previous week') }}" class="text-[13px] font-semibold text-text-tertiary">‹</button>
                            <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ $weekRangeLabel }}</p>
                            <button type="button" wire:click="nextPeriod" @disabled(! $weekCanGoNext) aria-label="{{ __('Next week') }}" class="text-[13px] font-semibold {{ $weekCanGoNext ? 'text-text-tertiary' : 'text-border cursor-not-allowed' }}">›</button>
                        </div>
                        <p class="mt-1 text-[32px] font-bold tracking-title tabular-nums text-ink">{{ $weekPercent }}%</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[13px] text-text-secondary">{{ __(':done done · :skipped skipped · :missed missed', ['done' => $weekDoneCount, 'skipped' => $weekSkippedCount, 'missed' => $weekMissedCount]) }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ trans_choice(':count day without a miss|:count days without a miss', $weekCleanDays, ['count' => $weekCleanDays]) }}</p>
                    </div>
                </div>

                <div class="mt-4 flex items-end gap-2" style="height: 84px">
                    @foreach($weekBars as $bar)
                        @php
                            $isSelected = $selectedDay === $bar['date'];
                            $dimmed = $selectedDay && ! $isSelected;
                            if ($bar['marks'] === 0) {
                                $barHeight = 18;
                                $barColor = 'bg-avatar-bg';
                            } elseif ($bar['ratio'] >= 1.0) {
                                $barHeight = 84;
                                $barColor = 'bg-brand';
                            } else {
                                $barHeight = (int) round(22 + $bar['ratio'] * (84 - 22));
                                $barColor = 'bg-indigo-light-bar';
                            }
                        @endphp
                        <button type="button" wire:click="selectDay('{{ $bar['date'] }}')" wire:key="week-bar-{{ $bar['date'] }}"
                                class="flex-1 h-full flex items-end {{ $dimmed ? 'opacity-40' : '' }}">
                            <span class="w-full rounded-tag {{ $barColor }} {{ ($isSelected || ($bar['isToday'] && ! $selectedDay)) ? 'ring-2 ring-ink ring-offset-1' : '' }}" style="height: {{ $barHeight }}px"></span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-1.5 flex gap-2">
                    @foreach($weekBars as $bar)
                        <p class="flex-1 text-center text-[11px] {{ $bar['isToday'] ? 'font-semibold text-ink' : 'text-text-tertiary' }}">{{ $bar['initial'] }}</p>
                    @endforeach
                </div>
                </div>
            @else
                <div wire:key="history-period-month">
                <div class="flex items-center justify-center gap-3">
                    <button type="button" wire:click="prevPeriod" aria-label="{{ __('Previous month') }}" class="w-7 h-7 flex items-center justify-center text-[15px] font-semibold text-text-tertiary">‹</button>
                    <p class="text-[17px] font-bold text-ink">{{ $monthLabel }}</p>
                    <button type="button" wire:click="nextPeriod" @disabled(! $monthCanGoNext) aria-label="{{ __('Next month') }}" class="w-7 h-7 flex items-center justify-center text-[15px] font-semibold {{ $monthCanGoNext ? 'text-text-tertiary' : 'text-border cursor-not-allowed' }}">›</button>
                </div>
                <p class="mt-0.5 text-center text-[13px] text-text-secondary">{{ __(':percent% · :done of :total done', ['percent' => $monthPercent, 'done' => $monthDoneCount, 'total' => $monthMarksCount]) }}</p>

                <div class="mt-4 grid grid-cols-7 gap-1.5">
                    @foreach($weekdayLetters as $letter)
                        <p class="text-center text-[11px] text-text-tertiary">{{ $letter }}</p>
                    @endforeach
                </div>
                <div class="mt-1.5 grid grid-cols-7 gap-1.5">
                    @foreach($monthDays as $cell)
                        @if($cell === null)
                            <div></div>
                        @else
                            @php
                                $isSelected = $selectedDay === $cell['date'];
                                $dimmed = $selectedDay && ! $isSelected;
                                if ($cell['marks'] === 0) {
                                    $cellColor = 'bg-avatar-bg text-text-tertiary font-medium';
                                } elseif ($cell['ratio'] >= 1.0) {
                                    $cellColor = 'bg-brand text-white font-semibold';
                                } else {
                                    $cellColor = 'bg-indigo-light-bar text-ink font-semibold';
                                }
                            @endphp
                            <button type="button" wire:click="selectDay('{{ $cell['date'] }}')" wire:key="month-day-{{ $cell['date'] }}"
                                    class="aspect-square rounded-chip flex items-center justify-center text-[13px] tabular-nums {{ $cellColor }} {{ $dimmed ? 'opacity-40' : '' }} {{ ($isSelected || ($cell['isToday'] && ! $selectedDay)) ? 'ring-2 ring-ink ring-offset-1' : '' }}">
                                {{ $cell['day'] }}
                            </button>
                        @endif
                    @endforeach
                </div>
                </div>
            @endif

            @unless($selectedDay)
                <div class="mt-3 pt-3 border-t border-border-light flex items-center gap-4">
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-brand"></span>{{ __('All done') }}</span>
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-indigo-light-bar"></span>{{ __('Partly done') }}</span>
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-avatar-bg"></span>{{ __('No reminders') }}</span>
                </div>
            @endunless
        </div>
    </div>
    @endif

    @if($selectedDay)
        {{-- Selected day chip --}}
        <div wire:key="history-selected-day-chip" class="px-5 md:px-0 mt-3.5 flex items-center justify-between gap-3">
            <button type="button" wire:click="clearSelectedDay" class="inline-flex items-center gap-1.5 h-9 pl-3.5 pr-1.5 rounded-full bg-ink text-white text-[13px] font-semibold">
                {{ $selectedDayLabel }}
                <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
            </button>
            <p class="shrink-0 text-[13px] text-text-secondary">{{ __(':done of :total done', ['done' => $selectedDayDoneCount, 'total' => $selectedDayTotal]) }}</p>
        </div>
    @elseif($hasAnyHistory)
        {{-- Filter chips --}}
        <div wire:key="history-filter-chips" class="px-5 md:px-0 mt-3.5 flex items-center gap-2">
            @foreach(['all' => __('All'), 'done' => __('Done'), 'skipped' => __('Skipped'), 'missed' => __('Missed')] as $key => $label)
                <button
                    type="button"
                    @click="historyFilter = '{{ $key }}'"
                    :class="historyFilter === '{{ $key }}' ? 'bg-ink text-white' : 'bg-white border border-border text-text-secondary'"
                    class="px-3 py-2 text-[13px] font-semibold rounded-pill"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Grouped feed --}}
    <div class="px-5 md:px-0 mt-3.5 space-y-4" x-data="{ openEventId: null }">
        @forelse($groupedEvents as $dateKey => $dayEvents)
            @php
                $groupDate = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateKey, $tz)->startOfDay();
                $groupLabel = $groupDate->isSameDay(now($tz)) ? __('Today') : $groupDate->translatedFormat('l, j F');
                $groupDoneCount = $dayEvents->filter(fn ($e) => $e->status === EventStatus::Done)->count();
                $groupHasDone = $groupDoneCount > 0;
                $groupHasSkipped = $dayEvents->contains(fn ($e) => $e->status === EventStatus::Cancelled);
                $groupHasMissed = $dayEvents->contains(fn ($e) => $e->status === EventStatus::Pending);
            @endphp
            <div wire:key="history-day-{{ $dateKey }}"
                 x-show="historyFilter === 'all' || (historyFilter === 'done' && {{ $groupHasDone ? 'true' : 'false' }}) || (historyFilter === 'skipped' && {{ $groupHasSkipped ? 'true' : 'false' }}) || (historyFilter === 'missed' && {{ $groupHasMissed ? 'true' : 'false' }})">
                @unless($selectedDay)
                    <div class="flex items-center justify-between px-1 mb-2">
                        <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ $groupLabel }}</p>
                        <p class="text-[13px] font-medium text-text-tertiary">{{ __(':done of :total', ['done' => $groupDoneCount, 'total' => $dayEvents->count()]) }}</p>
                    </div>
                @endunless
                <div class="rounded-card bg-white border border-border divide-y divide-border-light overflow-hidden">
                    @foreach($dayEvents as $event)
                        @php
                            $isDone = $event->status === EventStatus::Done;
                            $isSkipped = $event->status === EventStatus::Cancelled;
                            $isMissed = $event->status === EventStatus::Pending;
                            $dotColor = $isDone ? 'bg-success' : ($isSkipped ? 'bg-neutral-dot' : 'bg-danger');
                        @endphp
                        <div wire:key="history-event-{{ $event->id }}"
                             x-show="historyFilter === 'all' || (historyFilter === 'done' && {{ $isDone ? 'true' : 'false' }}) || (historyFilter === 'skipped' && {{ $isSkipped ? 'true' : 'false' }}) || (historyFilter === 'missed' && {{ $isMissed ? 'true' : 'false' }})">
                            <button type="button" @click="openEventId = openEventId === '{{ $event->id }}' ? null : '{{ $event->id }}'"
                                    class="w-full flex items-center gap-2.5 px-4 py-3">
                                <span class="w-[7px] h-[7px] rounded-full shrink-0 {{ $dotColor }}"></span>
                                <span class="flex-1 min-w-0 text-left text-[15px] font-medium text-ink truncate">
                                    {{ $event->notification->name }}{{ $isMissed ? ' · '.$event->scheduled_at->copy()->setTimezone($tz)->format('H:i') : '' }}
                                </span>
                                <span class="shrink-0 text-[13px] tabular-nums {{ $isMissed ? 'font-semibold text-danger' : 'text-text-secondary' }}">
                                    {{ $isDone ? __('done') . ' ' . ($event->completed_at?->copy()->setTimezone($tz)->format('H:i') ?? '') : ($isSkipped ? __('skipped') : __('missed')) }}
                                </span>
                            </button>
                            <div x-show="openEventId === '{{ $event->id }}'" x-cloak x-transition class="bg-canvas-sunken px-4 pt-3 pb-3.5 border-t border-border-light">
                                <div class="flex gap-2">
                                    <button type="button" wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                            class="flex-1 h-11 rounded-chip bg-success-tint text-[14px] font-semibold text-success disabled:opacity-50">{{ __('Done') }}</button>
                                    <button type="button" wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')"
                                            class="flex-1 h-11 rounded-chip bg-fill text-[14px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Skip') }}</button>
                                    @unless($isMissed)
                                        <button type="button" wire:click="clearStatus('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="clearStatus('{{ $event->id }}')"
                                                class="flex-1 h-11 rounded-chip bg-white border border-border text-[14px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Clear') }}</button>
                                    @endunless
                                </div>
                                @unless($isMissed)
                                    <p class="mt-2 text-[12px] leading-normal text-text-quaternary">{{ __("Clear puts it back to pending, so it shows up in that day's list again.") }}</p>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            @if($hasAnyHistory)
                <p class="px-1 text-[13px] text-text-quaternary">
                    {{ $selectedDay ? __('No marks that day.') : ($period === 'week' ? __('No marks this week.') : __('No marks this month.')) }}
                </p>
            @endif
        @endforelse
        @if($selectedDay)
            <p class="px-1 text-[12px] leading-normal text-text-quaternary">{{ $period === 'week' ? __('Tap a mark to change it. Tap the day again or × to see the whole week.') : __('Tap a mark to change it. Tap the day again or × to see the whole month.') }}</p>
        @endif
    </div>

    @unless($hasAnyHistory)
    <div class="px-5 md:px-0 space-y-4">
        <div class="rounded-card bg-white border border-border p-10 text-center">
            <div class="mx-auto w-9 h-9 rounded-full bg-avatar-bg flex items-center justify-center">
                <svg class="w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="mt-3 text-[17px] font-bold text-ink">{{ __('Your marks will show up here') }}</p>
            <p class="mt-1 text-sm text-text-secondary">{{ __('Every time you mark a reminder done or skip it, it lands here — with a weekly chart once there’s a few days of data.') }}</p>
            <a href="{{ route('home') }}" class="mt-5 block w-full h-12 leading-[48px] rounded-control bg-ink text-[15px] font-semibold text-white">
                {{ __('Go to Today') }}
            </a>
        </div>
    </div>
    @endunless
</x-pull-to-refresh>
</div>
