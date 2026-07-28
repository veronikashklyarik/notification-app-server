<x-layouts.app title="{{ __('History') }}">

    {{-- Mobile Header --}}
    <div class="md:hidden px-1 mb-5 slide-up">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ __('History') }}</h1>
        <p class="text-gray-500 text-sm">{{ __('All completed, cancelled, and postponed events.') }}</p>
    </div>

    {{-- Desktop Header --}}
    <div class="hidden md:block mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('History') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ __('All processed events across your notifications.') }}</p>
    </div>

    @if($events->isEmpty())
        {{-- Mobile Empty State --}}
        <div class="md:hidden bg-white rounded-3xl p-12 text-center slide-up-delay-1">
            <div class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('No history yet') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Completed, cancelled, or postponed events will appear here.') }}</p>
        </div>

        {{-- Desktop Empty State --}}
        <div class="hidden md:block bg-white rounded-2xl border border-gray-200 p-16 text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('No history yet') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Completed, cancelled, or postponed events will appear here.') }}</p>
        </div>
    @else
        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-3 slide-up-delay-1">
            @foreach($events as $event)
                @php
                    $statusColors = match($event->status->value) {
                        'done' => 'text-green-600 bg-green-50',
                        'postponed' => 'text-amber-600 bg-amber-50',
                        default => 'text-gray-500 bg-gray-100',
                    };
                @endphp
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            @if($event->notification)
                                <a href="{{ route('notifications.show', $event->notification) }}" class="font-semibold text-gray-900 truncate block hover:text-indigo-600 transition-colors">
                                    {{ $event->notification->name }}
                                </a>
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
                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg shrink-0 ml-3 {{ $statusColors }}">
                            {{ $event->status->label() }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden md:block bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Notification') }}</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">{{ __('Comment') }}</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">{{ __('Scheduled') }}</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Completed') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($events as $event)
                        @php
                            $colorMap = [
                                'done' => ['bg' => 'bg-green-50', 'text' => 'text-green-700'],
                                'cancelled' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
                                'postponed' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700'],
                            ];
                            $colors = $colorMap[$event->status->value] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                @if($event->notification)
                                    <a href="{{ route('notifications.show', $event->notification) }}"
                                       class="font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                        {{ $event->notification->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 italic">{{ __('Deleted notification') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors['bg'] }} {{ $colors['text'] }}">
                                    {{ $event->status->label() }}
                                </span>
                                @if($event->postponed_until)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ __('Until') }} @userTime($event->postponed_until->copy()->setTimezone($userTimezone), 'M j')</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden md:table-cell">
                                {{ $event->comment ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden lg:table-cell">@userTime($event->scheduled_at->copy()->setTimezone($userTimezone), 'M j, Y')
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-xs">
                                @if($event->completed_at)
                                    <span title="@userTime($event->completed_at->copy()->setTimezone($userTimezone), 'M j, Y H:i')">
                                        {{ $event->completed_at->diffForHumans(parts: 2) }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="mt-4">
                {{ $events->links() }}
            </div>
        @endif
    @endif

</x-layouts.app>
