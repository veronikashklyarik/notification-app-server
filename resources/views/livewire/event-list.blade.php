<x-pull-to-refresh class="stagger-children">
    <div class="px-5 pt-6 pb-2">
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">{{ __('Events') }}</h1>
    </div>

    {{-- Today --}}
    <div class="px-4 mt-4">
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Today') }}</h2>
            @if($todayTotal > 0)
                <span class="text-xs text-gray-400">{{ $todayEvents->count() }} / {{ $todayTotal }}</span>
            @endif
        </div>
        <div class="space-y-3">
            @forelse($todayEvents as $event)
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('events.index')) }}" class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 truncate">{{ $event->notification->name }}</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-gray-500">@userTime($event->scheduled_at, 'H:i')</p>
                            </div>
                        </a>
                        <div class="flex gap-2 shrink-0 ml-3">
                            <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')" class="px-4 py-2 text-xs font-bold text-green-700 bg-green-50 rounded-xl border border-green-200 hover:bg-green-100 active:scale-95 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="markDone('{{ $event->id }}')">{{ __('Done') }}</span>
                                <svg wire:loading wire:target="markDone('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                            <button wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')" class="px-3 py-2 text-xs font-bold text-gray-500 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 active:scale-95 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="markCancelled('{{ $event->id }}')">{{ __('Skip') }}</span>
                                <svg wire:loading wire:target="markCancelled('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-6 text-center">
                    <p class="text-sm text-gray-400 font-medium">{{ __('No tasks for today') }}</p>
                </div>
            @endforelse
        </div>

        @if($todayEvents->count() < $todayTotal)
            <button
                wire:click="loadMoreToday"
                wire:loading.attr="disabled"
                wire:target="loadMoreToday"
                class="mt-4 w-full py-3 text-sm font-semibold text-gray-600 bg-gray-50 rounded-2xl border border-gray-200 active:scale-[0.98] transition-all disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="loadMoreToday">{{ __('Show more') }}</span>
                <span wire:loading wire:target="loadMoreToday">{{ __('Loading...') }}</span>
            </button>
        @endif
    </div>

    {{-- Upcoming --}}
    <div class="px-4 mt-6">
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Upcoming') }}</h2>
            @if($upcomingTotal > 0)
                <span class="text-xs text-gray-400">{{ $upcomingEvents->count() }} / {{ $upcomingTotal }}</span>
            @endif
        </div>
        <div class="space-y-3">
            @forelse($upcomingEvents as $event)
                <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('events.index')) }}" class="card p-4 block">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 truncate">{{ $event->notification->name }}</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm text-gray-500">@userTime($event->scheduled_at, 'M j, Y \a\t H:i')</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold text-blue-600 bg-blue-50 rounded-lg uppercase tracking-wider shrink-0 ml-3">
                            {{ __('Pending') }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="card p-6 text-center">
                    <p class="text-sm text-gray-400 font-medium">{{ __('No upcoming events') }}</p>
                </div>
            @endforelse
        </div>

        @if($upcomingEvents->count() < $upcomingTotal)
            <button
                wire:click="loadMoreUpcoming"
                wire:loading.attr="disabled"
                wire:target="loadMoreUpcoming"
                class="mt-4 w-full py-3 text-sm font-semibold text-blue-600 bg-blue-50 rounded-2xl border border-blue-100 active:scale-[0.98] transition-all disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="loadMoreUpcoming">{{ __('Show more') }}</span>
                <span wire:loading wire:target="loadMoreUpcoming">{{ __('Loading...') }}</span>
            </button>
        @endif
    </div>

    {{-- Recent --}}
    @if($recentTotal > 0)
        <div class="px-4 mt-6 mb-6">
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Recent') }}</h2>
                <span class="text-xs text-gray-400">{{ $recentEvents->count() }} / {{ $recentTotal }}</span>
            </div>
            <div class="space-y-2">
                @foreach($recentEvents as $event)
                    <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('events.index')) }}" class="card p-4 block">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                @if($event->notification)
                                    <p class="font-semibold text-gray-900 truncate">{{ $event->notification->name }}</p>
                                @else
                                    <p class="font-semibold text-gray-400 truncate">{{ __('Deleted notification') }}</p>
                                @endif
                                <div class="flex items-center gap-1.5 mt-1">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm text-gray-500">@userTime($event->scheduled_at, 'M j, Y \a\t H:i')</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg shrink-0 ml-3
                                @if($event->status === \App\Enums\EventStatus::Done) text-green-600 bg-green-50
                                @elseif($event->status === \App\Enums\EventStatus::Postponed) text-amber-600 bg-amber-50
                                @else text-gray-500 bg-gray-100
                                @endif">
                                {{ __($event->status->value) }}
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
</x-pull-to-refresh>
