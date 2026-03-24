<x-layouts.app title="{{ $notification->name }}">

    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('notifications.index') }}" class="hover:text-indigo-600 transition-colors">Notifications</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-gray-900 truncate max-w-xs">{{ $notification->name }}</span>
        </nav>

        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $notification->name }}</h1>
                    @if($notification->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>Inactive
                        </span>
                    @endif
                </div>
                @if($notification->description)
                    <p class="text-gray-500 text-sm">{{ $notification->description }}</p>
                @endif
            </div>
            <a href="{{ route('notifications.edit', $notification) }}"
               class="flex-shrink-0 inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                </svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Detail Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">

        {{-- Schedule --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 col-span-2 sm:col-span-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Schedule</p>
            <p class="text-sm font-medium text-gray-900 mb-2">{{ $notification->frequency_label }}</p>

            @if($notification->schedule_type === \App\Enums\ScheduleType::WeekDays && !empty($notification->week_days))
                @php
                    $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
                    $selectedDays = $notification->week_days;
                    sort($selectedDays);
                @endphp
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach(range(1, 7) as $d)
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ in_array($d, $selectedDays) ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ $dayNames[$d] }}
                        </span>
                    @endforeach
                </div>
            @endif

            @if($notification->schedule_type !== \App\Enums\ScheduleType::AsNeeded && !empty($notification->times))
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach($notification->times as $time)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $time }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Next due --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Next due</p>
            @if($notification->next_due_at)
                @php
                    $nextDue = $notification->next_due_at;
                @endphp
                <p class="text-sm font-medium text-gray-900">{{ $nextDue->copy()->setTimezone($userTimezone)->format('M j, Y') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $nextDue->copy()->setTimezone($userTimezone)->format('H:i') }} · {{ $nextDue->diffForHumans(parts: 2) }}</p>
            @else
                <p class="text-sm text-gray-400">—</p>
            @endif
        </div>

        {{-- Ends on / Created --}}
        @if($notification->ends_at)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Ends on</p>
                @php
                    $endsAt = $notification->ends_at;
                    $endsAtPast = $endsAt->copy()->endOfDay()->isPast();
                @endphp
                <p class="text-sm font-medium {{ $endsAtPast ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $endsAt->format('M j, Y') }}
                </p>
                @php
                    $startsAt = $notification->starts_at ?? $notification->created_at->startOfDay();
                    $totalDays = (int) $startsAt->diffInDays($endsAt) + 1;
                    if ($totalDays >= 365) {
                        $durationLabel = round($totalDays / 365, 1) . ' ' . (round($totalDays / 365, 1) == 1 ? 'year' : 'years');
                    } elseif ($totalDays >= 30) {
                        $durationLabel = round($totalDays / 30) . ' ' . (round($totalDays / 30) == 1 ? 'month' : 'months');
                    } elseif ($totalDays >= 7) {
                        $durationLabel = round($totalDays / 7) . ' ' . (round($totalDays / 7) == 1 ? 'week' : 'weeks');
                    } else {
                        $durationLabel = $totalDays . ' ' . ($totalDays === 1 ? 'day' : 'days');
                    }
                @endphp
                <p class="text-xs text-gray-400 mt-0.5">{{ $durationLabel }} total</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Created</p>
                <p class="text-sm font-medium text-gray-900">{{ $notification->created_at->copy()->setTimezone($userTimezone)->format('M j, Y') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">No end date</p>
            </div>
        @endif
    </div>

    {{-- History --}}
    <div>
        <h2 class="text-base font-semibold text-gray-900 mb-4">Action history</h2>

        @if($history->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
                <p class="text-sm text-gray-400">No actions recorded yet.</p>
                <p class="text-xs text-gray-300 mt-1">Actions taken from the mobile app will appear here.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Comment</th>
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Due</th>
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($history as $entry)
                            <tr>
                                <td class="px-6 py-4">
                                    @php $colors = ['Done' => 'green', 'Cancelled' => 'gray', 'Postponed' => 'yellow']; $c = $colors[$entry->action->value] ?? 'gray'; @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700">
                                        {{ $entry->action->value }}
                                    </span>
                                    @if($entry->postponed_until)
                                        <p class="text-xs text-gray-400 mt-0.5">Until {{ $entry->postponed_until->copy()->setTimezone($userTimezone)->format('M j, Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500 hidden md:table-cell">{{ $entry->comment ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500 hidden lg:table-cell">
                                    {{ $entry->due_at?->copy()->setTimezone($userTimezone)->format('M j, Y H:i') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $entry->created_at->copy()->setTimezone($userTimezone)->format('M j, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
                <div class="mt-4">{{ $history->links() }}</div>
            @endif
        @endif
    </div>

</x-layouts.app>
