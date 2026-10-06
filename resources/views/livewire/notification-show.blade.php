@php
    use App\Enums\EventStatus;

    $tz = auth()->user()->timezone ?? 'UTC';
    $ended = $notification->isEnded();
    $bucket = $ended ? 'ended' : ($notification->is_active ? 'active' : 'paused');
    $activeLabel = match ($bucket) {
        'active' => __('Notifications on'),
        'paused' => __('Paused'),
        'ended' => __('Ended'),
    };

    $upcomingGroups = $upcomingEvents
        ->groupBy(fn ($event) => $event->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d'))
        ->take(4)
        ->map(function ($events, $dateKey) use ($tz) {
            $date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateKey, $tz)->startOfDay();
            $dayDiff = now($tz)->startOfDay()->diffInDays($date);
            $dayLabel = match (true) {
                $dayDiff == 0 => __('Today'),
                $dayDiff == 1 => __('Tomorrow'),
                default => $date->translatedFormat('D j M'),
            };

            return [
                'label' => $dayLabel,
                'times' => $events->map(fn ($e) => $e->scheduled_at->copy()->setTimezone($tz)->format('H:i'))->sort()->implode(', '),
            ];
        });

    $rangeStart = $notification->starts_at ?? $notification->created_at;
    $rangeLine = $notification->ends_at
        ? __('Since :start to :end', [
            'start' => $rangeStart->translatedFormat('j M Y'),
            'end' => $notification->ends_at->translatedFormat('j M Y'),
        ])
        : __('Since :start · no end date', ['start' => $rangeStart->translatedFormat('j M Y')]);

    $completionPercent = $totalCount > 0 ? (int) round($doneCount / $totalCount * 100) : 0;

    if ($ended && $notification->ends_at) {
        $durationDays = $rangeStart->diffInDays($notification->ends_at) + 1;
        $endedRangeLine = $notification->frequency_label.' · '.__(':start – :end (:count days)', [
            'start' => $rangeStart->translatedFormat('j M'),
            'end' => $notification->ends_at->translatedFormat('j M Y'),
            'count' => $durationDays,
        ]);
    }

    if ($ended) {
        $repeatEndDate = $this->repeatCourseEndDate();
        $repeatPreview = $repeatEndDate
            ? __(':start – :end, same times', [
                'start' => now()->startOfDay()->translatedFormat('j M'),
                'end' => $repeatEndDate->translatedFormat('j M'),
            ])
            : __('Starts today, same times');
    }
@endphp
<div style="padding-top: max(env(safe-area-inset-top), 20px)" class="pb-8">

    {{-- Header --}}
    <div class="px-5 flex items-center justify-between">
        <a href="{{ $backUrl }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Reminders') }}</a>
        <a href="{{ route('notifications.edit', $notification) }}?back={{ urlencode(route('notifications.show', $notification).'?back='.urlencode($backUrl)) }}"
           class="text-[15px] font-semibold text-brand">{{ __('Edit') }}</a>
    </div>

    <div class="px-5 mt-3">
        @if($ended)
            <span class="inline-block mb-1.5 px-2 py-0.5 text-[12px] font-semibold text-text-secondary bg-fill rounded-tag">{{ __('Ended :date', ['date' => $notification->ends_at?->translatedFormat('j M') ?? '']) }}</span>
        @endif
        <h1 class="text-[26px] font-bold tracking-title text-ink">{{ $notification->name }}</h1>
        @if($notification->description)
            <p class="mt-1 text-[15px] text-text-secondary">{{ $notification->description }}</p>
        @endif
    </div>

    <div class="px-5 mt-3.5 space-y-3.5">
        {{-- Ended: completion summary + Repeat Course / Extend Dates --}}
        @if($ended)
            <div class="rounded-card bg-white border border-border p-4">
                <p class="text-[15px] font-semibold text-ink">
                    @if($totalCount > 0)
                        {{ __(':count of :total marks done · :percent%', ['count' => $doneCount, 'total' => $totalCount, 'percent' => $completionPercent]) }}
                    @else
                        {{ __('No marks were recorded.') }}
                    @endif
                </p>
                @if($totalCount > 0)
                    <div class="mt-2.5 h-1.5 rounded-full bg-fill overflow-hidden">
                        <div class="h-full rounded-full bg-success" style="width: {{ $completionPercent }}%"></div>
                    </div>
                @endif
                <p class="mt-2.5 text-[14px] text-text-secondary">{{ $endedRangeLine }}</p>
            </div>

            <div class="rounded-card bg-white border border-border p-4">
                <p class="mb-2.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Start again') }}</p>
                <div class="space-y-2">
                    <button type="button" wire:click="repeatCourse" wire:loading.attr="disabled" wire:target="repeatCourse"
                            class="w-full p-3.5 rounded-field bg-brand text-left disabled:opacity-50">
                        <span class="block text-[15px] font-semibold text-white">{{ __('Repeat Course') }}</span>
                        <span class="block mt-0.5 text-[13px] text-white/75">{{ __('New copy · :preview', ['preview' => $repeatPreview]) }}</span>
                    </button>
                    <a href="{{ route('notifications.edit', $notification) }}?expand=more&back={{ urlencode(route('notifications.show', $notification).'?back='.urlencode($backUrl)) }}"
                       class="block w-full p-3.5 rounded-field bg-white border border-border">
                        <span class="block text-[15px] font-semibold text-ink">{{ __('Extend Dates') }}</span>
                        <span class="block mt-0.5 text-[13px] text-text-tertiary">{{ __('Pick a new end date for this one') }}</span>
                    </a>
                </div>
                <p class="mt-2.5 text-[12px] leading-normal text-text-quaternary">{{ __('Nothing is sent for an ended reminder. Its history stays.') }}</p>
            </div>
        @endif

        {{-- Active row --}}
        @unless($ended)
            <div class="rounded-row bg-white border border-border px-4 py-[15px] flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[15px] font-semibold text-ink">{{ $activeLabel }}</p>
                    <p class="mt-0.5 text-[13px] text-text-tertiary">
                        @if($notification->reminder_interval)
                            {{ __('Repeats :interval until you mark it', ['interval' => \Illuminate\Support\Str::lower(__(\App\Models\Notification::REMINDER_INTERVALS[$notification->reminder_interval] ?? ''))]) }}
                        @else
                            {{ __('No repeat if not marked') }}
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive"
                        class="shrink-0 relative w-[50px] h-[30px] rounded-full transition-colors disabled:opacity-50 {{ $notification->is_active ? 'bg-brand' : 'bg-border-input' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white transition-transform {{ $notification->is_active ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                </button>
            </div>
        @endunless

        {{-- Schedule card --}}
        @unless($ended)
        <div class="rounded-card bg-white border border-border p-4">
            <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Schedule') }}</p>
            <p class="mt-1.5 text-[16px] font-semibold text-ink">{{ $notification->frequency_label }}</p>
            <p class="mt-1 text-[14px] text-text-secondary">{{ $rangeLine }}</p>

            <div class="mt-3.5 pt-3.5 border-t border-border-light">
                <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Next up') }}</p>
                @if($upcomingGroups->isNotEmpty())
                    <div class="mt-2 space-y-1.5">
                        @foreach($upcomingGroups as $group)
                            <div class="flex items-center justify-between">
                                <span class="text-[14px] font-semibold text-ink">{{ $group['label'] }}</span>
                                <span class="text-[14px] tabular-nums text-text-secondary">{{ $group['times'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($notification->schedule_type === \App\Enums\ScheduleType::AsNeeded)
                    <button type="button" wire:click="markNow" wire:loading.attr="disabled" wire:target="markNow"
                            class="mt-2 w-full h-11 rounded-field bg-brand text-[14px] font-semibold text-white disabled:opacity-50">
                        {{ __('Mark now') }}
                    </button>
                @else
                    <p class="mt-2 text-[14px] text-text-tertiary">{{ __('Nothing scheduled — this one is marked by hand.') }}</p>
                @endif
            </div>
        </div>
        @endunless

        {{-- Stat tiles --}}
        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-row bg-white border border-border p-3.5">
                <p class="text-[24px] font-bold tracking-title tabular-nums text-ink">{{ $doneCount }}</p>
                <p class="mt-0.5 text-[11px] font-semibold uppercase text-text-tertiary">{{ __('Done') }}</p>
            </div>
            <div class="rounded-row bg-white border border-border p-3.5">
                <p class="text-[24px] font-bold tracking-title tabular-nums text-ink">{{ $skippedCount }}</p>
                <p class="mt-0.5 text-[11px] font-semibold uppercase text-text-tertiary">{{ __('Skipped') }}</p>
            </div>
            <div class="rounded-row bg-white border border-border p-3.5">
                <p class="text-[24px] font-bold tracking-title tabular-nums text-ink">{{ $missedCount }}</p>
                <p class="mt-0.5 text-[11px] font-semibold uppercase text-text-tertiary">{{ __('Missed') }}</p>
            </div>
        </div>
        @unless($ended)
            @if($totalCount > 0)
                <p class="-mt-2 text-[13px] text-text-tertiary">
                    {{ __(':percent% done since :date', ['percent' => $completionPercent, 'date' => $rangeStart->translatedFormat('j M')]) }}
                    @if($avgMarkMinutes !== null)
                        · {{ __('usually marked within :duration', ['duration' => $this->formatMinutes($avgMarkMinutes)]) }}
                    @endif
                </p>
            @endif
        @endunless

        {{-- Ended: collapsed link to full history, expands into the Recent marks card below --}}
        @if($ended && $recentEventsLimit <= 5 && $recentEvents->count() < $totalCount)
            <button type="button" wire:click="loadMoreRecent" wire:loading.attr="disabled" wire:target="loadMoreRecent"
                    class="w-full h-12 px-4 rounded-field bg-white border border-border flex items-center justify-between disabled:opacity-50">
                <span class="text-[15px] font-semibold text-ink">{{ __('All :count marks', ['count' => $totalCount]) }}</span>
                <span class="text-text-tertiary">›</span>
            </button>
        @endif

        {{-- Recent marks --}}
        @if($recentEvents->isNotEmpty() && (! $ended || $recentEventsLimit > 5 || $recentEvents->count() >= $totalCount))
            <div class="rounded-card bg-white border border-border p-4" x-data="{ openEventId: null }">
                <div class="flex items-baseline justify-between">
                    <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Recent marks') }}</p>
                    @if($recentEvents->count() < ($doneCount + $skippedCount + $missedCount))
                        <button type="button" wire:click="loadMoreRecent" wire:loading.attr="disabled" wire:target="loadMoreRecent" class="text-[12px] font-semibold text-brand disabled:opacity-50">
                            {{ __('All :count ›', ['count' => $doneCount + $skippedCount + $missedCount]) }}
                        </button>
                    @else
                        <p class="text-[12px] text-text-quaternary">{{ __('tap to change') }}</p>
                    @endif
                </div>
                <div class="mt-2 space-y-2.5">
                    @foreach($recentEvents as $event)
                        @php
                            $isDone = $event->status === EventStatus::Done;
                            $isSkipped = $event->status === EventStatus::Cancelled;
                            $isMissed = $event->status === EventStatus::Pending;
                            $dotColor = $isDone ? 'bg-success' : ($isSkipped ? 'bg-neutral-dot' : 'bg-danger');
                        @endphp
                        <div class="rounded-control -mx-1 px-1">
                            <button type="button" @click="openEventId = openEventId === '{{ $event->id }}' ? null : '{{ $event->id }}'"
                                    class="w-full flex items-center gap-2.5">
                                <span class="w-[7px] h-[7px] rounded-full shrink-0 {{ $dotColor }}"></span>
                                <span class="flex-1 min-w-0 text-left text-[14px] text-ink truncate">@userTime($event->scheduled_at, 'D, j M')</span>
                                <span class="shrink-0 text-[13px] tabular-nums {{ $isMissed ? 'font-semibold text-danger' : 'text-text-secondary' }}">
                                    {{ $isDone ? __('done') . ' ' . ($event->completed_at?->copy()->setTimezone($tz)->format('H:i') ?? '') : ($isSkipped ? __('skipped') : __('missed')) }}
                                </span>
                            </button>
                            <div x-show="openEventId === '{{ $event->id }}'" x-cloak x-transition class="flex gap-2 pb-2">
                                <button type="button" wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                        class="flex-1 h-10 rounded-chip bg-success-tint text-[13px] font-semibold text-success disabled:opacity-50">{{ __('Done') }}</button>
                                <button type="button" wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')"
                                        class="flex-1 h-10 rounded-chip bg-fill text-[13px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Skip') }}</button>
                                @unless($isMissed)
                                    <button type="button" wire:click="clearStatus('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="clearStatus('{{ $event->id }}')"
                                            class="flex-1 h-10 rounded-chip bg-white border border-border text-[13px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Clear') }}</button>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Delete --}}
        <button type="button" wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete,delete"
                class="w-full h-[50px] rounded-field bg-white border border-border text-[15px] font-semibold text-danger disabled:opacity-50">
            <span wire:loading.remove wire:target="confirmDelete,delete">{{ __('Delete Reminder') }}</span>
            <span wire:loading wire:target="confirmDelete,delete">{{ __('Deleting...') }}</span>
        </button>
    </div>

    @teleport('body')
        <div x-data="{ show: $wire.entangle('confirmingDelete') }">
            <x-confirm-dialog
                show="show"
                :title="__('Delete “:name”?', ['name' => $notification->name])"
                :confirm-label="__('Delete')"
                on-confirm="$wire.delete()"
                on-cancel="$wire.cancelDelete()"
            >
                {{ trans_choice('Its :count mark goes with it, and nothing more will be sent. This action cannot be undone.|Its :count marks go with it, and nothing more will be sent. This action cannot be undone.', $totalCount, ['count' => $totalCount]) }}
            </x-confirm-dialog>
        </div>
    @endteleport
</div>
