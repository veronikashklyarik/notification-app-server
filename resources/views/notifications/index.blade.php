<x-layouts.app title="Notifications">

    {{-- Page Header --}}
    <div class="mb-6 md:mb-8">
        {{-- Mobile Header --}}
        <div class="md:hidden slide-up mb-5">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">Notifications</h1>
            <p class="text-gray-500 text-sm">Manage your recurring reminders.</p>
        </div>

        {{-- Desktop Header --}}
        <div class="hidden md:flex md:items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Notifications</h1>
                <p class="text-gray-500 text-sm mt-1">Manage your recurring reminders.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New notification
                </a>
            </div>
        </div>

        {{-- Mobile Action Buttons --}}
        <div class="md:hidden flex items-center gap-2 slide-up-delay-1">
            <a href="{{ route('notifications.create') }}"
               class="flex-1 flex items-center justify-center gap-2 h-12 bg-indigo-600 hover:bg-indigo-700 active:scale-98 text-white font-bold rounded-2xl shadow-lg shadow-indigo-500/30 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New notification
            </a>
        </div>
    </div>

    {{-- Notifications List --}}
    @if($notifications->isEmpty())
        {{-- Mobile Empty State --}}
        <div class="md:hidden bg-white rounded-3xl p-12 text-center slide-up-delay-2">
            <div class="w-20 h-20 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-3xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">No notifications yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-xs mx-auto leading-relaxed">Create your first recurring notification to get started with reminders.</p>
        </div>

        {{-- Desktop Empty State --}}
        <div class="hidden md:block bg-white rounded-2xl border border-gray-200 p-16 text-center">
            <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">No notifications yet</h3>
            <p class="text-sm text-gray-500 mb-5">Create your first recurring notification to get started.</p>
        </div>
    @else
        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-4 slide-up-delay-2">
            @foreach($notifications as $index => $notification)
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative slide-up"
                     style="animation-delay: {{ ($index + 2) * 0.1 }}s">

                    {{-- Status Badge at Top --}}
                    <div class="px-5 pt-4 pb-3">
                        @if($notification->is_active)
                            <div class="inline-flex items-center gap-2.5 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full shadow-lg shadow-green-500/30 relative overflow-hidden">
                                {{-- Animated glow overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                                <div class="relative flex items-center gap-2.5">
                                    {{-- Pulsing dot --}}
                                    <div class="relative flex items-center justify-center">
                                        <span class="absolute w-2.5 h-2.5 bg-white rounded-full animate-ping opacity-75"></span>
                                        <span class="relative w-2.5 h-2.5 bg-white rounded-full shadow-sm"></span>
                                    </div>
                                    <span class="text-xs font-bold text-white uppercase tracking-wider">Active</span>
                                </div>
                            </div>
                        @else
                            <div class="inline-flex items-center gap-2.5 px-4 py-2 bg-gray-100 rounded-full border border-gray-200">
                                <span class="w-2.5 h-2.5 bg-gray-400 rounded-full"></span>
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Inactive</span>
                            </div>
                        @endif
                    </div>

                    {{-- Card Content --}}
                    <a href="{{ route('notifications.show', $notification) }}"
                       class="block px-5 pb-5 active:bg-gray-50 transition-colors">

                        {{-- Header with name --}}
                        <div class="mb-4">
                            <h3 class="font-bold text-gray-900 text-lg mb-1.5 leading-tight">
                                {{ $notification->name }}
                            </h3>
                            @if($notification->description)
                                <p class="text-gray-500 text-sm line-clamp-2 leading-relaxed">
                                    {{ $notification->description }}
                                </p>
                            @endif
                        </div>

                        {{-- Info Grid --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            {{-- Schedule --}}
                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-3.5">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold text-indigo-900/70 uppercase tracking-wider">Schedule</span>
                                </div>
                                <p class="text-sm font-bold text-indigo-900 leading-tight">{{ $notification->frequency_label }}</p>
                            </div>

                            {{-- Next Due --}}
                            @if($notification->next_due_at)
                                @php
                                    $nextDue = $notification->next_due_at;
                                @endphp
                                <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-2xl p-3.5">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        <span class="text-xs font-semibold text-amber-900/70 uppercase tracking-wider">Next</span>
                                    </div>
                                    <p class="text-sm font-bold text-amber-900 leading-tight truncate" title="{{ $nextDue->copy()->setTimezone($userTimezone)->format('M j, Y H:i') }}">
                                        {{ $nextDue->diffForHumans(parts: 2) }}
                                    </p>
                                </div>
                            @else
                                <div class="bg-gray-50 rounded-2xl p-3.5 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-400">No upcoming</span>
                                </div>
                            @endif
                        </div>
                    </a>

                    {{-- Action Buttons --}}
                    <div class="flex items-center border-t border-gray-100">
                        <a href="{{ route('notifications.edit', $notification) }}"
                           class="flex-1 flex items-center justify-center gap-2 py-3.5 text-indigo-600 font-semibold text-sm hover:bg-indigo-50 active:bg-indigo-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            Edit
                        </a>
                        <div class="w-px h-8 bg-gray-200"></div>
                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                              onsubmit="return confirm('Delete this notification? This cannot be undone.')"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 text-red-600 font-semibold text-sm hover:bg-red-50 active:bg-red-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden md:block bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Next due</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($notifications as $notification)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <a href="{{ route('notifications.show', $notification) }}"
                                       class="font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                        {{ $notification->name }}
                                    </a>
                                    @if($notification->description)
                                        <p class="text-gray-400 text-xs mt-0.5 truncate max-w-xs">{{ $notification->description }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                    {{ $notification->frequency_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden lg:table-cell">
                                @if($notification->next_due_at)
                                    @php
                                        $nextDue = $notification->next_due_at;
                                    @endphp
                                    <span title="{{ $nextDue->copy()->setTimezone($userTimezone)->format('M j, Y H:i') }}">
                                        {{ $nextDue->diffForHumans(parts: 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($notification->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('notifications.edit', $notification) }}"
                                       class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                                          onsubmit="return confirm('Delete this notification? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif

</x-layouts.app>
