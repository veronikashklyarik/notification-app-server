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
                <h1 class="text-xl font-bold text-gray-900 truncate">Event Details</h1>
            </div>
            <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg
                @if($event->status === \App\Enums\EventStatus::Pending) text-blue-600 bg-blue-50 border border-blue-200
                @elseif($event->status === \App\Enums\EventStatus::Done) text-green-600 bg-green-50 border border-green-200
                @elseif($event->status === \App\Enums\EventStatus::Postponed) text-amber-600 bg-amber-50 border border-amber-200
                @else text-gray-500 bg-gray-100 border border-gray-200
                @endif">
                {{ $event->status->value }}
            </span>
        </div>
    </div>

    {{-- Event Info --}}
    <div class="px-4">
        <div class="card p-5 space-y-5">
            <div>
                @if($event->notification)
                    <a href="{{ route('notifications.show', $event->notification) }}?back={{ urlencode(route('events.show', $event).'?back='.urlencode($backUrl)) }}" class="text-lg font-bold text-indigo-600 hover:text-indigo-500 transition-colors">
                        {{ $event->notification->name }}
                    </a>
                    @if($event->notification->description)
                        <p class="mt-1 text-sm text-gray-400 leading-relaxed">{{ $event->notification->description }}</p>
                    @endif
                @else
                    <p class="text-lg font-bold text-gray-400">Deleted reminder</p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-2xl bg-gray-50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date</p>
                    <p class="mt-1 text-sm font-bold text-gray-900">@userTime($event->scheduled_at, 'M j, Y')</p>
                </div>
                <div class="p-3 rounded-2xl bg-gray-50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Time</p>
                    <p class="mt-1 text-sm font-bold text-gray-900">@userTime($event->scheduled_at, 'H:i')</p>
                </div>
            </div>

            @if($event->comment)
                <div class="p-3 rounded-2xl bg-gray-50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Comment</p>
                    <p class="mt-1 text-sm text-gray-700">{{ $event->comment }}</p>
                </div>
            @endif

            @if($event->completed_at)
                @php
                    $isGreen = $event->status === \App\Enums\EventStatus::Done;
                    $label = match($event->status) {
                        \App\Enums\EventStatus::Done => 'Completed',
                        \App\Enums\EventStatus::Cancelled => 'Cancelled',
                        \App\Enums\EventStatus::Postponed => 'Postponed at',
                        default => 'Completed',
                    };
                @endphp
                <div class="p-3 rounded-2xl {{ $isGreen ? 'bg-green-50' : 'bg-gray-50' }}">
                    <p class="text-[10px] font-bold uppercase tracking-widest {{ $isGreen ? 'text-green-500' : 'text-gray-400' }}">{{ $label }}</p>
                    <p class="mt-1 text-sm font-medium {{ $isGreen ? 'text-green-700' : 'text-gray-600' }}">@userTime($event->completed_at, 'M j, Y \a\t H:i')</p>
                </div>
            @endif

            @if($event->postpone_history && count($event->postpone_history) > 0)
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Postpone History</p>
                    @foreach($event->postpone_history as $entry)
                        <div class="flex items-center gap-2 py-1.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></div>
                            <p class="text-xs text-gray-500">
                                @userTime(\Carbon\Carbon::parse($entry['at']), 'M j') &rarr; @userTime(\Carbon\Carbon::parse($entry['to']), 'M j, Y H:i')
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Revert (for completed/cancelled/postponed events) --}}
    @if($event->status !== \App\Enums\EventStatus::Pending)
        <div class="px-4 mt-5">
            <button wire:click="revertToPending" wire:loading.attr="disabled" wire:target="revertToPending" class="w-full py-3 text-sm font-bold text-amber-600 bg-amber-50 rounded-xl border border-amber-200 hover:bg-amber-100 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="revertToPending">Revert to Pending</span>
                <span wire:loading wire:target="revertToPending">Reverting...</span>
            </button>
        </div>
    @endif

    {{-- Actions (only for pending events) --}}
    @if($event->status === \App\Enums\EventStatus::Pending)
        <div class="px-4 mt-5 flex gap-3">
            <button wire:click="markDone" wire:loading.attr="disabled" wire:target="markDone" class="flex-1 py-3 text-sm font-bold text-green-700 bg-green-50 rounded-xl border border-green-200 hover:bg-green-100 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="markDone">Mark Done</span>
                <span wire:loading wire:target="markDone">Saving...</span>
            </button>
            <button wire:click="markCancelled" wire:loading.attr="disabled" wire:target="markCancelled" class="flex-1 py-3 text-sm font-bold text-gray-500 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="markCancelled">Cancel</span>
                <span wire:loading wire:target="markCancelled">Saving...</span>
            </button>
        </div>

        <div class="px-4 mt-4 mb-6">
            <div class="card p-5">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Update Event</h2>

                @if($errors->any())
                    <div class="p-3 mb-4 text-sm text-red-600 bg-red-50/80 rounded-xl border border-red-100">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form wire:submit="update" class="space-y-4">
                    <div>
                        <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                        <select wire:model.live="status" class="input-styled w-full">
                            <option value="">Select status...</option>
                            <option value="done">Done</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="postponed">Postponed</option>
                        </select>
                    </div>

                    @if($status === 'postponed')
                        <div>
                            <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Postpone Until</label>
                            <input type="datetime-local" wire:model="postponed_until" class="input-styled w-full">
                        </div>
                    @endif

                    <div>
                        <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Comment <span class="text-gray-300">(optional)</span></label>
                        <textarea wire:model="comment" rows="2" class="input-styled w-full resize-none"
                            placeholder="Add a note..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3 text-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="update">Update Event</span>
                        <span wire:loading wire:target="update">Updating...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
