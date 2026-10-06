@php
    $tz = auth()->user()->timezone ?? 'UTC';
    $groupedMissed = $missedEvents->groupBy(fn ($event) => $event->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d'));
@endphp
<div x-data="{ confirmingSkipAll: $wire.entangle('confirmingSkipAll') }" class="px-5 pb-8" style="padding-top: max(env(safe-area-inset-top), 20px)">
    <div class="grid grid-cols-[1fr_auto_1fr] items-center">
        <a href="{{ route('home') }}" class="justify-self-start text-[15px] font-semibold text-text-secondary">‹ {{ __('Today') }}</a>
        <h1 class="justify-self-center text-[16px] font-semibold text-ink">{{ __('Catch up') }}</h1>
        @if($missedTotal > 0)
            <button type="button" wire:click="confirmSkipAll"
                    class="justify-self-end text-[15px] font-semibold text-brand">
                {{ __('Skip all') }}
            </button>
        @endif
    </div>

    <p class="mt-3.5 text-sm leading-normal text-text-secondary">
        {{ $missedTotal > 0 ? trans_choice(':count reminder was never marked. Mark what you actually did — the rest can stay missed.|:count reminders were never marked. Mark what you actually did — the rest can stay missed.', $missedTotal, ['count' => $missedTotal]) : '' }}
    </p>

    @if($missedEvents->isNotEmpty())
        <div wire:key="catchup-missed-wrapper">
        <div class="mt-3.5 space-y-4">
            @foreach($groupedMissed as $dateKey => $dayEvents)
                @php
                    $groupDate = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateKey, $tz)->startOfDay();
                    $groupLabel = $groupDate->isSameDay(now($tz)) ? __('Today') : $groupDate->translatedFormat('l, j F');
                @endphp
                <div wire:key="catchup-day-{{ $dateKey }}">
                    <div class="flex items-center justify-between px-1 mb-2">
                        <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ $groupLabel }}</p>
                        <button type="button" wire:click="markDayDone('{{ $dateKey }}')" wire:loading.attr="disabled" wire:target="markDayDone('{{ $dateKey }}')"
                                class="text-[13px] font-semibold text-brand disabled:opacity-50">
                            {{ __('All done') }}
                        </button>
                    </div>
                    <div class="space-y-2">
                        @foreach($dayEvents as $event)
                            <div wire:key="catchup-{{ $event->id }}" class="rounded-row border border-border bg-white px-4 py-3.5 flex items-center gap-3">
                                <span class="w-[46px] shrink-0 text-[15px] font-semibold tabular-nums text-ink">@userTime($event->scheduled_at, 'H:i')</span>
                                <p class="min-w-0 flex-1 text-[15px] font-semibold text-ink truncate">{{ $event->notification->name }}</p>
                                <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                        class="shrink-0 h-9 px-4 rounded-control bg-ink text-[14px] font-semibold text-white disabled:opacity-50">{{ __('Done') }}</button>
                                <button wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')"
                                        class="shrink-0 h-9 px-4 rounded-control bg-fill text-[14px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Skip') }}</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if($missedEvents->count() < $missedTotal)
            <button wire:click="loadMoreMissed" wire:loading.attr="disabled" wire:target="loadMoreMissed"
                    class="mt-2 w-full py-3 text-sm font-semibold text-text-secondary bg-fill rounded-control disabled:opacity-50">
                <span wire:loading.remove wire:target="loadMoreMissed">{{ __('Show more') }}</span>
                <span wire:loading wire:target="loadMoreMissed">{{ __('Loading...') }}</span>
            </button>
        @endif
        </div>
    @else
        <div wire:key="catchup-empty" class="mt-3.5 rounded-card bg-white border border-border p-[26px] text-center">
            <div class="mx-auto w-9 h-9 rounded-full bg-success-tint flex items-center justify-center">
                <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <p class="mt-3 text-[17px] font-bold text-ink">{{ __('Nothing left to sort out') }}</p>
            <p class="mt-1.5 text-sm text-text-secondary">{{ __('Every missed reminder has a status now.') }}</p>
            <a href="{{ route('home') }}" class="mt-4 block w-full h-12 leading-[48px] rounded-control bg-ink text-[15px] font-semibold text-white">
                {{ __('Back to Today') }}
            </a>
        </div>
    @endif

    <x-confirm-dialog
        show="confirmingSkipAll"
        :title="trans_choice('Skip all :count missed?', $missedTotal, ['count' => $missedTotal])"
        :confirm-label="__('Skip all')"
        variant="neutral"
        on-confirm="$wire.skipAllMissed()"
        on-cancel="$wire.cancelSkipAll()"
    >
        {{ __('Every reminder on this list will be marked as skipped. You can undo this for a few seconds.') }}
    </x-confirm-dialog>

    <x-undo-bar />
</div>
