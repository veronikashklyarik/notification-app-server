<x-layouts.app title="History">

    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">History</h1>
        <p class="text-gray-500 text-sm mt-1">All actions taken on your notifications.</p>
    </div>

    @if($history->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">No history yet</h3>
            <p class="text-sm text-gray-500">Actions taken from the mobile app will appear here.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Notification</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Comment</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Due date</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($history as $entry)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                @if($entry->notification)
                                    <a href="{{ route('notifications.show', $entry->notification) }}"
                                       class="font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                        {{ $entry->notification->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 italic">Deleted notification</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $colorMap = ['Done' => ['bg' => 'bg-green-50', 'text' => 'text-green-700'], 'Cancelled' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'], 'Postponed' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700']];
                                    $actionColors = $colorMap[$entry->action->value] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $actionColors['bg'] }} {{ $actionColors['text'] }}">
                                    {{ $entry->action->value }}
                                </span>
                                @if($entry->postponed_until)
                                    <p class="text-xs text-gray-400 mt-0.5">Until {{ $entry->postponed_until->copy()->setTimezone($userTimezone)->format('M j') }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden md:table-cell">
                                {{ $entry->comment ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden lg:table-cell">
                                {{ $entry->due_at?->copy()->setTimezone($userTimezone)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-xs">
                                @php $createdAt = $entry->created_at->copy()->setTimezone($userTimezone) @endphp
                                <span title="{{ $createdAt->format('M j, Y H:i') }}">
                                    {{ $createdAt->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
            <div class="mt-4">
                {{ $history->links() }}
            </div>
        @endif
    @endif

</x-layouts.app>
