@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
@endphp
<x-pull-to-refresh class="stagger-children">
    {{-- Header with gradient --}}
    <div class="gradient-header px-5 pt-[calc(env(safe-area-inset-top)+1.5rem)] pb-8 rounded-b-[32px] shadow-lg shadow-indigo-500/10 -mt-[env(safe-area-inset-top)]">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-200 text-sm font-medium">Good {{ now(config('app.user_timezone', 'UTC'))->format('H') < 5 ? 'night' : (now(config('app.user_timezone', 'UTC'))->format('H') < 12 ? 'morning' : (now(config('app.user_timezone', 'UTC'))->format('H') < 18 ? 'afternoon' : 'evening')) }},</p>
                <h1 class="text-2xl font-bold text-white mt-0.5">{{ $user->name }}</h1>
            </div>
            <a href="{{ route('profile.edit') }}" class="shrink-0">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="w-12 h-12 rounded-full object-cover border-[3px] border-white/30 shadow-lg">
                @else
                    <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center border-[3px] border-white/30">
                        <span class="text-lg font-bold text-white">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                @endif
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="px-4 -mt-5">
        <div class="grid grid-cols-2 gap-3">
            <div class="card p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['active_notifications'] }}</p>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Active</p>
                    </div>
                </div>
            </div>
            <div class="card p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['today_events'] }}</p>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Events --}}
    <div class="px-4 mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Today's Tasks</h2>
                @if($todayTotal > 0)
                    <p class="text-xs text-gray-400 mt-0.5">Showing {{ $todayEvents->count() }} of {{ $todayTotal }}</p>
                @endif
            </div>
            @if($todayTotal > 0)
                <a href="{{ route('events.index') }}" class="text-xs font-semibold text-indigo-600">See all</a>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($todayEvents as $event)
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('home')) }}" class="min-w-0 flex-1">
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
                                <span wire:loading.remove wire:target="markDone('{{ $event->id }}')">Done</span>
                                <svg wire:loading wire:target="markDone('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                            <button wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')" class="px-3 py-2 text-xs font-bold text-gray-500 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 active:scale-95 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="markCancelled('{{ $event->id }}')">Skip</span>
                                <svg wire:loading wire:target="markCancelled('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 mb-4 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-50">
                        <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="font-semibold text-gray-900">All clear!</p>
                    <p class="mt-1 text-sm text-gray-400">No tasks scheduled for today.</p>
                </div>
            @endforelse
        </div>

        @if($todayEvents->count() < $todayTotal)
            <button
                wire:click="loadMore"
                wire:loading.attr="disabled"
                wire:target="loadMore"
                class="mt-4 w-full py-3 text-sm font-semibold text-gray-600 bg-gray-50 rounded-2xl border border-gray-200 active:scale-[0.98] transition-all disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="loadMore">Show more</span>
                <span wire:loading wire:target="loadMore">Loading...</span>
            </button>
        @endif
    </div>

    {{-- Missed Events --}}
    @if($missedTotal > 0)
        <div class="px-4 mt-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Missed</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $missedTotal }} {{ Str::plural('task', $missedTotal) }} from previous days</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="completeAllMissed" wire:loading.attr="disabled" wire:target="completeAllMissed" class="px-3 py-1.5 text-xs font-semibold text-green-700 bg-green-50 rounded-xl border border-green-200 hover:bg-green-100 active:scale-95 transition-all disabled:opacity-50 whitespace-nowrap">
                        <span wire:loading.remove wire:target="completeAllMissed">Complete all</span>
                        <svg wire:loading wire:target="completeAllMissed" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                    <button wire:click="skipAllMissed" wire:loading.attr="disabled" wire:target="skipAllMissed" class="px-3 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 active:scale-95 transition-all disabled:opacity-50 whitespace-nowrap">
                        <span wire:loading.remove wire:target="skipAllMissed">Skip all</span>
                        <svg wire:loading wire:target="skipAllMissed" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($missedEvents as $event)
                    <div class="card p-4 border-l-[3px] border-l-red-400">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('events.show', $event) }}?back={{ urlencode(route('home')) }}" class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 truncate">{{ $event->notification->name }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-red-400">@userTime($event->scheduled_at, 'M j \a\t H:i')</p>
                                </div>
                            </a>
                            <div class="flex gap-2 shrink-0 ml-3">
                                <button wire:click="markDone('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markDone('{{ $event->id }}')" class="px-4 py-2 text-xs font-bold text-green-700 bg-green-50 rounded-xl border border-green-200 hover:bg-green-100 active:scale-95 transition-all disabled:opacity-50">
                                    <span wire:loading.remove wire:target="markDone('{{ $event->id }}')">Done</span>
                                    <svg wire:loading wire:target="markDone('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </button>
                                <button wire:click="markCancelled('{{ $event->id }}')" wire:loading.attr="disabled" wire:target="markCancelled('{{ $event->id }}')" class="px-3 py-2 text-xs font-bold text-gray-500 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 active:scale-95 transition-all disabled:opacity-50">
                                    <span wire:loading.remove wire:target="markCancelled('{{ $event->id }}')">Skip</span>
                                    <svg wire:loading wire:target="markCancelled('{{ $event->id }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($missedEvents->count() < $missedTotal)
                <button
                    wire:click="loadMoreMissed"
                    wire:loading.attr="disabled"
                    wire:target="loadMoreMissed"
                    class="mt-4 w-full py-3 text-sm font-semibold text-gray-600 bg-gray-50 rounded-2xl border border-gray-200 active:scale-[0.98] transition-all disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMoreMissed">Show more</span>
                    <span wire:loading wire:target="loadMoreMissed">Loading...</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Quick Actions --}}
    <div class="px-4 mt-6 mb-6">
        <div class="flex gap-3">
            <a href="{{ route('notifications.create') }}" class="btn-primary flex-1 py-3.5 text-sm text-center animate-pulse-soft">
                + New Reminder
            </a>
            <a href="{{ route('events.index') }}" class="flex-1 py-3.5 text-sm font-bold text-center text-gray-600 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 active:scale-[0.98] transition-all shadow-sm">
                All Events
            </a>
        </div>
    </div>
</x-pull-to-refresh>
