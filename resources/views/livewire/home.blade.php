@php
    use App\Enums\EventStatus;
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $tz = $user->timezone ?? 'UTC';

    $nextUp = $todayEvents->firstWhere('status', EventStatus::Pending);
    $laterToday = $nextUp
        ? $todayEvents->reject(fn ($event) => $event->id === $nextUp->id)
        : $todayEvents;
    $weekPercent = $weekMarksCount > 0 ? (int) round($weekDoneCount / $weekMarksCount * 100) : 0;
    $nextAnyTime = $nextUp ? $nextUp->scheduled_at->copy()->setTimezone($tz)->format('H:i') : $nextReminderTime;
@endphp
<x-pull-to-refresh>
    <div class="md:grid md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] md:gap-7 md:items-start">
    <div class="px-5 md:px-0" style="padding-top: max(env(safe-area-inset-top), 20px)">

        {{-- Header row --}}
        <div class="flex items-end justify-between">
            <div>
                <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ now($tz)->translatedFormat('l, j F') }}</p>
                <h1 class="text-[26px] font-bold tracking-title text-ink mt-0.5">{{ __('Today') }}</h1>
            </div>
            <a href="{{ route('settings') }}" class="md:hidden shrink-0 w-[38px] h-[38px] rounded-full bg-avatar-bg overflow-hidden flex items-center justify-center">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    <span class="text-[14px] font-semibold text-text-secondary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
            </a>
        </div>

        {{-- Push-off banner --}}
        <div
            x-data="{
                show: false,
                async init() {
                    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) return;
                    if (Notification.permission !== 'granted') {
                        this.show = true;
                        return;
                    }
                    try {
                        const reg = await navigator.serviceWorker.ready;
                        const sub = await reg.pushManager.getSubscription();
                        if (!sub) {
                            this.show = true;
                            return;
                        }
                        // A browser-level subscription can belong to a different account
                        // that previously signed in on this same device — confirm it's
                        // actually ours before hiding the banner.
                        const res = await fetch('{{ route('push-subscriptions.status') }}?endpoint=' + encodeURIComponent(sub.endpoint), { headers: { Accept: 'application/json' } });
                        const { subscribed } = await res.json();
                        this.show = !subscribed;
                    } catch {
                        this.show = false;
                    }
                },
            }"
            x-show="show" x-cloak
            class="mt-3.5 flex items-start gap-3 rounded-field border border-indigo-tint-border bg-indigo-tint px-4 py-3.5"
        >
            <svg class="mt-0.5 w-5 h-5 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[14px] font-semibold text-indigo-tint-text">{{ __('Notifications are off on this phone') }}</span>
                    <a href="{{ route('settings') }}" class="shrink-0 px-3 py-1.5 rounded-control bg-brand text-[13px] font-semibold text-white">{{ __('Turn on') }}</a>
                </div>
                @if($nextAnyTime)
                    <p class="mt-1 text-[13px] text-indigo-tint-text/80">{{ __('Nothing will remind you at :time.', ['time' => $nextAnyTime]) }}</p>
                @endif
            </div>
        </div>

        {{-- Missed strip --}}
        @if($missedTotal > 0)
            <a href="{{ route('catch-up') }}" class="mt-3.5 flex items-center gap-2.5 rounded-field border border-warning-tint-border bg-warning-tint px-4 py-3.5">
                <span class="w-2 h-2 rounded-full bg-warning shrink-0"></span>
                <span class="flex-1 text-sm font-medium text-warning-text">{{ trans_choice(':count missed from earlier days', $missedTotal, ['count' => $missedTotal]) }}</span>
                <span class="text-[13px] font-semibold text-warning shrink-0">{{ __('Catch up') }}</span>
            </a>
        @endif

        @if($nextUp)
            {{-- Next-up card --}}
            @php
                $isOverdue = $nextUp->scheduled_at->isPast();
                $minutes = (int) abs(now()->diffInMinutes($nextUp->scheduled_at, false));
            @endphp
            <div wire:key="home-next-up-card" class="mt-3.5 rounded-hero bg-ink p-[18px]">
                <p class="text-[11px] font-semibold uppercase tracking-caption {{ $isOverdue ? 'text-warning' : 'text-[#8f96a3]' }}">
                    @if($isOverdue)
                        {{ __('Overdue · :duration ago', ['duration' => $this->formatMinutes($minutes)]) }}
                    @else
                        {{ __('Next up · in :duration', ['duration' => $this->formatMinutes($minutes)]) }}
                    @endif
                </p>
                <div class="mt-2.5 flex items-baseline gap-2.5">
                    <span class="text-[34px] font-bold tracking-title tabular-nums text-white">@userTime($nextUp->scheduled_at, 'H:i')</span>
                    <span class="text-[17px] font-semibold text-white truncate">{{ $nextUp->notification->name }}</span>
                </div>
                @if($nextUp->notification->description)
                    <p class="mt-1 text-sm text-[#a9b0bc]">{{ $nextUp->notification->description }}</p>
                @endif
                <div class="mt-4 flex gap-2">
                    <button wire:click="markDone('{{ $nextUp->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $nextUp->id }}')"
                            class="flex-1 h-12 rounded-field bg-white text-[15px] font-semibold text-ink disabled:opacity-50">
                        {{ __('Done') }}
                    </button>
                    <button wire:click="markPostponed('{{ $nextUp->id }}')" wire:loading.attr="disabled" wire:target="markPostponed('{{ $nextUp->id }}')"
                            class="shrink-0 px-3.5 h-12 rounded-field bg-ink-muted text-[14px] font-semibold text-[#d5d9e0] disabled:opacity-50">
                        {{ __('In 15 min') }}
                    </button>
                    <button wire:click="markCancelled('{{ $nextUp->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $nextUp->id }}')"
                            class="shrink-0 px-3.5 h-12 rounded-field bg-ink-muted text-[14px] font-semibold text-[#d5d9e0] disabled:opacity-50">
                        {{ __('Skip') }}
                    </button>
                </div>
            </div>
        @else
            {{-- All-clear card --}}
            <div wire:key="home-all-clear-card" class="mt-3.5 rounded-hero bg-white p-7 text-center">
                <div class="mx-auto w-9 h-9 rounded-full bg-success-tint flex items-center justify-center">
                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <p class="mt-3 text-[19px] font-bold text-ink">{{ __('Nothing left for today') }}</p>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ __(':done done, :skipped skipped.', ['done' => $todayDoneCount, 'skipped' => $todaySkippedCount]) }}
                    @if($nextReminderName)
                        @if($nextReminderDays === 1)
                            {{ __('Next: :name tomorrow at :time.', ['name' => $nextReminderName, 'time' => $nextReminderTime]) }}
                        @else
                            {{ trans_choice('Next: :name in :count day at :time.|Next: :name in :count days at :time.', $nextReminderDays, ['name' => $nextReminderName, 'count' => $nextReminderDays, 'time' => $nextReminderTime]) }}
                        @endif
                    @endif
                </p>
            </div>
        @endif

        {{-- Later today --}}
        @if($laterToday->isNotEmpty())
            <div class="mt-5 flex items-center justify-between">
                <h2 class="text-[15px] font-semibold text-ink">{{ $nextUp ? __('Later today') : __('Earlier today') }}</h2>
                <p class="text-[13px] font-medium text-text-tertiary">{{ __(':count of :total done', ['count' => $todayDoneCount, 'total' => $todayTotalCount]) }}</p>
            </div>

            <div class="mt-2 space-y-2" x-data="{ openEventId: @js($highlightEventId) }" x-init="if (openEventId) { $nextTick(() => $refs['highlight-' + openEventId]?.scrollIntoView({ behavior: 'smooth', block: 'center' })) }">
                @foreach($laterToday as $event)
                    @php
                        $isDone = $event->status === EventStatus::Done;
                        $isPending = $event->status === EventStatus::Pending;
                        $isSkipped = $event->status === EventStatus::Cancelled;
                        $isSnoozed = $event->status === EventStatus::Postponed;
                        $isHandled = ! $isPending;
                        $isHighlighted = $highlightEventId === $event->id;
                    @endphp
                    <div wire:key="later-today-{{ $event->id }}" x-ref="highlight-{{ $event->id }}" class="rounded-row border overflow-hidden {{ $isHighlighted ? 'border-brand ring-2 ring-brand/20' : 'border-border' }}">
                        <div class="{{ $isHandled ? 'bg-surface-muted' : 'bg-white' }} px-4 py-3.5"
                             @if(! $isSnoozed) @click="openEventId = openEventId === '{{ $event->id }}' ? null : '{{ $event->id }}'" role="button" @endif>
                        <div class="flex items-center gap-3 {{ $isHandled ? 'opacity-75' : '' }}">
                            <span class="w-[46px] shrink-0 text-[15px] font-semibold tabular-nums {{ $isHandled ? 'text-text-secondary' : 'text-ink' }}">
                                @userTime($event->scheduled_at, 'H:i')
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[15px] font-semibold truncate {{ $isHandled ? 'text-text-secondary' : 'text-ink' }} {{ $isDone ? 'line-through' : '' }}">
                                    {{ $event->notification->name }}
                                </p>
                                @if($isSnoozed)
                                    <p class="text-sm text-text-tertiary truncate">{{ __('Snoozed · back at :time', ['time' => $event->postponed_until?->copy()->setTimezone($tz)->format('H:i')]) }}</p>
                                @elseif($isSkipped)
                                    <p class="text-sm text-text-tertiary truncate">{{ __('Skipped') }}</p>
                                @elseif($event->notification->description)
                                    <p class="text-sm text-text-tertiary truncate">{{ $event->notification->description }}</p>
                                @endif
                            </div>
                            @if($isPending)
                                <p class="hidden md:block shrink-0 w-[150px] text-[14px] text-text-tertiary truncate">{{ $event->notification->frequency_label }}</p>
                                <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                        @click.stop class="hidden md:block shrink-0 text-[14px] font-semibold text-brand disabled:opacity-50">{{ __('Mark done') }}</button>
                                <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                        @click.stop class="md:hidden shrink-0 w-[30px] h-[30px] rounded-full border-[1.5px] border-border-input disabled:opacity-50"
                                        aria-label="{{ __('Done') }}"></button>
                            @elseif($isDone)
                                <p class="hidden md:block shrink-0 w-[150px] text-[14px] text-text-tertiary truncate">{{ __('Done at :time', ['time' => $event->completed_at?->copy()->setTimezone($tz)->format('H:i')]) }}</p>
                                <span class="shrink-0 w-[30px] h-[30px] rounded-full bg-success flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                            @elseif($isSnoozed)
                                <span class="shrink-0 w-[30px] h-[30px] rounded-full bg-neutral-dot flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                                        <circle cx="12" cy="12" r="9" stroke-linecap="round" />
                                    </svg>
                                </span>
                            @else
                                <span class="shrink-0 w-[30px] h-[30px] rounded-full bg-neutral-dot flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                        </div>

                        @unless($isSnoozed)
                        <div x-show="openEventId === '{{ $event->id }}'" x-cloak x-transition
                             class="bg-canvas-sunken px-4 pt-3 pb-3.5 border-t border-border-light" @click.stop>
                            <div class="flex gap-2">
                                <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')"
                                        class="flex-1 h-11 rounded-chip bg-success-tint text-[14px] font-semibold text-success disabled:opacity-50">{{ __('Done') }}</button>
                                <button wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')"
                                        class="flex-1 h-11 rounded-chip bg-fill text-[14px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Skip') }}</button>
                                @if($isHandled)
                                    <button wire:click="clearStatus('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="clearStatus('{{ $event->id }}')"
                                            class="flex-1 h-11 rounded-chip bg-white border border-border text-[14px] font-semibold text-text-secondary disabled:opacity-50">{{ __('Clear') }}</button>
                                @endif
                            </div>
                            <p class="mt-2 text-[12px] leading-normal text-text-quaternary">
                                {{ $isHandled ? __('Clear puts it back to pending.') : __('Skip records that it did not happen — it still counts in your history.') }}
                            </p>
                        </div>
                        @endunless
                    </div>
                @endforeach
            </div>
            <p class="mt-3 pl-1 text-[12px] text-text-quaternary">{{ __('Tap the circle to mark it done, or tap the row for all three options.') }}</p>
        @endif

        {{-- Mobile weekly summary --}}
        @if($weekMarksCount > 0)
            <div class="mt-5 md:hidden rounded-card bg-white border border-border p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Last 7 days') }}</p>
                    <p class="text-[13px] text-text-secondary"><span class="font-semibold text-ink">{{ $weekPercent }}%</span> · {{ __(':count of :total', ['count' => $weekDoneCount, 'total' => $weekMarksCount]) }}</p>
                </div>
                <div class="mt-4 flex items-end gap-2" style="height: 36px">
                    @foreach($weekBars as $bar)
                        @php
                            if ($bar['marks'] === 0) {
                                $mobileBarHeight = 15;
                                $mobileBarColor = 'bg-avatar-bg';
                            } elseif ($bar['ratio'] >= 1.0) {
                                $mobileBarHeight = 36;
                                $mobileBarColor = 'bg-brand';
                            } else {
                                $mobileBarHeight = (int) round(10 + $bar['ratio'] * (36 - 15));
                                $mobileBarColor = 'bg-indigo-light-bar';
                            }
                        @endphp
                        <a href="{{ route('events.index', ['day' => $bar['date']]) }}" wire:key="home-mobile-bar-{{ $bar['date'] }}"
                           class="flex-1 h-full flex items-end">
                            <span class="w-full rounded-tag {{ $mobileBarColor }} {{ $bar['isToday'] ? 'ring-2 ring-ink ring-offset-1' : '' }}" style="height: {{ $mobileBarHeight }}px"></span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                    @foreach($weekBars as $bar)
                        <div class="flex-1 text-center text-[11px] {{ $bar['isToday'] ? 'font-semibold text-ink' : 'text-text-tertiary' }}">{{ $bar['initial'] }}</div>
                    @endforeach
                </div>
                <div class="mt-3 pt-3 border-t border-border-light flex items-center gap-4">
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-brand"></span>{{ __('All done') }}</span>
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-indigo-light-bar"></span>{{ __('Partly done') }}</span>
                    <span class="flex items-center gap-1.5 text-[12px] text-text-tertiary"><span class="w-2.5 h-2.5 rounded-[3px] bg-avatar-bg"></span>{{ __('No reminders') }}</span>
                </div>
            </div>
        @endif

    </div>

    {{-- Desktop right column --}}
    <div class="hidden md:flex md:flex-col md:gap-3.5">
        @if($weekMarksCount > 0)
            <div class="rounded-card bg-white border border-border p-[18px]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Last 7 days') }}</p>
                        <p class="mt-1 text-[32px] font-bold tracking-title tabular-nums text-ink">{{ $weekPercent }}%</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[13px] text-text-secondary">{{ __(':count of :total marks', ['count' => $weekDoneCount, 'total' => $weekMarksCount]) }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ trans_choice(':count day without a miss|:count days without a miss', $weekCleanDays, ['count' => $weekCleanDays]) }}</p>
                    </div>
                </div>

                <div class="mt-4 flex items-end gap-2" style="height: 64px">
                    @foreach($weekBars as $bar)
                        @php
                            if ($bar['marks'] === 0) {
                                $barHeight = 20;
                                $barColor = 'bg-avatar-bg';
                            } elseif ($bar['ratio'] >= 1.0) {
                                $barHeight = 64;
                                $barColor = 'bg-brand';
                            } else {
                                $barHeight = (int) round(16 + $bar['ratio'] * (64 - 16));
                                $barColor = 'bg-indigo-light-bar';
                            }
                        @endphp
                        <a href="{{ route('events.index', ['day' => $bar['date']]) }}" wire:key="home-desktop-bar-{{ $bar['date'] }}"
                           class="flex-1 h-full flex items-end">
                            <span class="w-full rounded-tag {{ $barColor }} {{ $bar['isToday'] ? 'ring-2 ring-ink ring-offset-1' : '' }}" style="height: {{ $barHeight }}px"></span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-1.5 flex gap-2">
                    @foreach($weekBars as $bar)
                        <p class="flex-1 text-center text-[11px] {{ $bar['isToday'] ? 'font-semibold text-ink' : 'text-text-tertiary' }}">{{ $bar['initial'] }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @if($activeReminders->isNotEmpty())
            <div class="rounded-card bg-white border border-border p-[18px]">
                <p class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary mb-3">{{ __('Active reminders') }}</p>
                <div class="flex flex-col gap-3">
                    @foreach($activeReminders as $reminder)
                        <a href="{{ route('notifications.show', $reminder) }}" wire:key="active-reminder-{{ $reminder->id }}" class="flex items-center gap-2.5">
                            <span class="w-[7px] h-[7px] rounded-full bg-success shrink-0"></span>
                            <span class="min-w-0 flex-1 text-[14px] font-semibold text-ink truncate">{{ $reminder->name }}</span>
                            <span class="shrink-0 text-[13px] text-text-tertiary">{{ $reminder->frequency_label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-card bg-indigo-tint border border-indigo-tint-border p-5" x-data="{ isInstalled: false }" x-init="isInstalled = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true" x-show="!isInstalled">
            <p class="text-[15px] font-semibold text-indigo-tint-text">{{ __('Install on your phone') }}</p>
            <p class="mt-1.5 text-[13px] leading-normal text-indigo-tint-text">{{ __('Push notifications on iPhone only work once the app is installed.') }}</p>
            <a href="{{ route('install') }}" class="mt-2.5 block text-[13px] font-semibold text-brand">{{ __('How to install') }}</a>
        </div>
    </div>
    </div>

    <x-undo-bar />
</x-pull-to-refresh>
