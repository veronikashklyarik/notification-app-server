<div class="stagger-children">
    {{-- Header --}}
    <div class="px-5 pt-6 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 truncate">{{ $notification->name }}</h1>
            </div>
            @if($notification->isEnded())
                <span class="px-3 py-1 text-[11px] font-semibold text-slate-500 bg-slate-100 rounded-full border border-slate-200">{{ __('Ended') }}</span>
            @elseif($notification->is_active)
                <span class="badge-shimmer px-3 py-1 text-[11px] font-bold rounded-full shadow-sm shadow-green-500/20">{{ __('Active') }}</span>
            @else
                <span class="px-3 py-1 text-[11px] font-semibold text-gray-500 bg-gray-100 rounded-full border border-gray-200">{{ __('Paused') }}</span>
            @endif
        </div>
    </div>

    {{-- Info Card --}}
    <div class="px-4">
        <div class="card p-5 space-y-5">
            @if($notification->description)
                <p class="text-sm text-gray-500 leading-relaxed">{{ $notification->description }}</p>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 rounded-2xl bg-gray-50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Schedule') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $notification->frequency_label ?? str_replace('_', ' ', $notification->schedule_type->value) }}</p>
                </div>
                @if($notification->times && !in_array($notification->schedule_type, [\App\Enums\ScheduleType::WeekDays, \App\Enums\ScheduleType::SpecificDates]))
                    <div class="p-3 rounded-2xl bg-gray-50">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Times') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ implode(', ', $notification->times) }}</p>
                    </div>
                @endif
            </div>

            @if($notification->schedule_type === \App\Enums\ScheduleType::WeekDays && $notification->week_days)
                @php $dayNames = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')]; @endphp
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('Days of Week') }}</p>
                    <div class="flex flex-col gap-2">
                        @foreach($notification->week_days as $entry)
                            @php
                                $dayNum = is_array($entry) ? (int)($entry['day'] ?? 0) : (int)$entry;
                                $dayTimes = is_array($entry) ? ($entry['times'] ?? []) : [];
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-lg shrink-0">{{ $dayNames[$dayNum] ?? $dayNum }}</span>
                                @if(!empty($dayTimes))
                                    <span class="text-xs text-gray-500">{{ implode(', ', $dayTimes) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($notification->schedule_type === \App\Enums\ScheduleType::SpecificDates && !empty($notification->specific_dates))
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('Dates') }}</p>
                    <div class="space-y-2">
                        @foreach($notification->specific_dates as $entry)
                            @php
                                $dateStr = is_array($entry) ? ($entry['date'] ?? '') : $entry;
                                $entryTimes = is_array($entry) ? ($entry['times'] ?? []) : [];
                            @endphp
                            @if($dateStr)
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50">
                                    <span class="text-sm font-semibold text-gray-900">{{ \Illuminate\Support\Carbon::parse($dateStr)->translatedFormat('M j, Y') }}</span>
                                    @if(!empty($entryTimes))
                                        <span class="text-xs text-gray-500">{{ implode(', ', $entryTimes) }}</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if($notification->starts_at || $notification->ends_at)
                <div class="flex gap-4">
                    @if($notification->starts_at)
                        <div class="p-3 rounded-2xl bg-gray-50 flex-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Starts') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $notification->starts_at->translatedFormat('M j, Y') }}</p>
                        </div>
                    @endif
                    @if($notification->ends_at)
                        <div class="p-3 rounded-2xl bg-gray-50 flex-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Ends') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $notification->ends_at->translatedFormat('M j, Y') }}</p>
                            @php
                                $startsAt = $notification->starts_at ?? $notification->created_at->startOfDay();
                                $totalDays = (int)$startsAt->diffInDays($notification->ends_at) + 1;
                            @endphp
                            <p class="text-xs text-gray-400 mt-0.5">{{ $totalDays }} {{ $totalDays === 1 ? __('day') : __('days') }} {{ __('total') }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Upcoming Events --}}
    @if($eventsTotal > 0)
        <div class="px-4 mt-5">
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest">{{ __('Upcoming Events') }}</h2>
                <span class="text-xs text-gray-400">{{ $events->count() }} / {{ $eventsTotal }}</span>
            </div>
            <div class="space-y-2">
                @foreach($events as $event)
                    <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('notifications.show', $notification).'?back='.urlencode($backUrl)) }}" class="card block p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-700">@userTime($event->scheduled_at, 'M j, Y \a\t H:i')</p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md text-blue-600 bg-blue-50">
                                {{ __('Pending') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($events->count() < $eventsTotal)
                <button
                    wire:click="loadMoreEvents"
                    wire:loading.attr="disabled"
                    wire:target="loadMoreEvents"
                    class="mt-4 w-full py-3 text-sm font-semibold text-blue-600 bg-blue-50 rounded-2xl border border-blue-100 active:scale-[0.98] transition-all disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMoreEvents">{{ __('Show more') }}</span>
                    <span wire:loading wire:target="loadMoreEvents">{{ __('Loading...') }}</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Recent Events --}}
    @if($recentTotal > 0)
        <div class="px-4 mt-5">
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest">{{ __('Recent Events') }}</h2>
                <span class="text-xs text-gray-400">{{ $recentEvents->count() }} / {{ $recentTotal }}</span>
            </div>
            <div class="space-y-2">
                @foreach($recentEvents as $event)
                    <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('notifications.show', $notification).'?back='.urlencode($backUrl)) }}" class="card block p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-700">@userTime($event->scheduled_at, 'M j, Y \a\t H:i')</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg shrink-0 ml-3
                                @if($event->status === \App\Enums\EventStatus::Done) text-green-600 bg-green-50
                                @elseif($event->status === \App\Enums\EventStatus::Postponed) text-amber-600 bg-amber-50
                                @else text-gray-500 bg-gray-100
                                @endif">
                                {{ $event->status->label() }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($recentEvents->count() < $recentTotal)
                <button
                    wire:click="loadMoreRecent"
                    wire:loading.attr="disabled"
                    wire:target="loadMoreRecent"
                    class="mt-4 w-full py-3 text-sm font-semibold text-gray-600 bg-gray-50 rounded-2xl border border-gray-200 active:scale-[0.98] transition-all disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMoreRecent">{{ __('Show more') }}</span>
                    <span wire:loading wire:target="loadMoreRecent">{{ __('Loading...') }}</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Actions --}}
    <div class="px-4 mt-6 mb-6 space-y-3">
        <div class="flex gap-3">
            <a href="{{ route('notifications.edit', $notification) }}?back={{ urlencode(route('notifications.show', $notification).'?back='.urlencode($backUrl)) }}" class="flex-1 py-3 text-sm font-bold text-center text-indigo-600 bg-white rounded-xl border border-indigo-200 hover:bg-indigo-50 active:scale-[0.98] transition-all shadow-sm">
                {{ __('Edit') }}
            </a>
            @if(!$notification->isEnded())
                <button wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive" type="button" class="flex-1 py-3 text-sm font-bold text-center rounded-xl border active:scale-[0.98] transition-all shadow-sm disabled:opacity-50
                    {{ $notification->is_active
                        ? 'text-amber-600 bg-white border-amber-200 hover:bg-amber-50'
                        : 'text-green-600 bg-white border-green-200 hover:bg-green-50' }}">
                    <span wire:loading.remove wire:target="toggleActive">{{ $notification->is_active ? __('Pause') : __('Activate') }}</span>
                    <span wire:loading wire:target="toggleActive">{{ __('Updating...') }}</span>
                </button>
            @endif
        </div>

        <button wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete,delete" type="button" class="w-full py-3 text-sm font-bold text-red-500 bg-white rounded-xl border border-gray-200 hover:bg-red-50 hover:border-red-200 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50">
            <span wire:loading.remove wire:target="confirmDelete,delete">{{ __('Delete Reminder') }}</span>
            <span wire:loading wire:target="confirmDelete,delete">{{ __('Deleting...') }}</span>
        </button>
    </div>

    @teleport('body')
    <div x-data="{ confirmingDelete: false }"
         x-on:show-delete-confirmation.window="confirmingDelete = true"
         x-show="confirmingDelete" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="confirmingDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
         @click.self="confirmingDelete = false">
        <div x-show="confirmingDelete"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">{{ __('Delete Reminder') }}</h3>
            </div>
            <p class="text-sm text-gray-500 mb-5">{{ __('Are you sure you want to delete this reminder? This will permanently delete the reminder and all its notification history. This action cannot be undone.') }}</p>
            <div class="flex gap-3">
                <button type="button" @click="confirmingDelete = false"
                        class="flex-1 py-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 active:scale-[0.98] transition-all">
                    {{ __('Cancel') }}
                </button>
                <button type="button"
                        @click="confirmingDelete = false; $wire.delete()"
                        class="flex-1 py-3 text-sm font-bold text-red-500 bg-gray-100 rounded-xl hover:bg-red-50 active:scale-[0.98] transition-all">
                    {{ __('Delete') }}
                </button>
            </div>
        </div>
    </div>
    @endteleport
</div>
