<div>
<x-pull-to-refresh class="stagger-children">
    <div class="px-5 pt-6 pb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Reminders</h1>
                @if($total > 0)
                    <p class="text-xs text-gray-400 mt-0.5">Showing {{ $notifications->count() }} of {{ $total }}</p>
                @endif
            </div>
            <a href="{{ route('notifications.create') }}" class="btn-primary px-4 py-2.5 text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                New
            </a>
        </div>
    </div>

    <div class="px-4 space-y-3">
        @forelse($notifications as $notification)
            <div
                wire:key="notification-{{ $notification->id }}"
                class="relative overflow-hidden rounded-[24px] select-none"
                style="box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 20px -4px rgba(99,102,241,0.06), 0 0 0 1px rgba(0,0,0,0.01);"
                x-data="{
                    tx: 0,
                    open: false,
                    dragging: false,
                    startX: 0,
                    startY: 0,
                    snapWidth: 96,
                    onStart(e) {
                        this.startX = e.touches[0].clientX;
                        this.startY = e.touches[0].clientY;
                        this.dragging = true;
                        $dispatch('swipe-reset', { except: {{ $notification->id }} });
                    },
                    onMouseStart(e) {
                        this.startX = e.clientX;
                        this.startY = e.clientY;
                        this.dragging = true;
                        $dispatch('swipe-reset', { except: {{ $notification->id }} });
                    },
                    onEnd() {
                        this.dragging = false;
                        if (this.tx < -(this.snapWidth / 2)) {
                            this.tx = -this.snapWidth;
                            this.open = true;
                        } else {
                            this.tx = 0;
                            this.open = false;
                        }
                    },
                    close() {
                        this.tx = 0;
                        this.open = false;
                    }
                }"
                x-init="
                    $el.addEventListener('touchmove', (e) => {
                        if (!$data.dragging) return;
                        let dx = e.touches[0].clientX - $data.startX;
                        let dy = e.touches[0].clientY - $data.startY;
                        if (Math.abs(dy) > Math.abs(dx) + 5) { $data.dragging = false; return; }
                        e.preventDefault();
                        let base = $data.open ? -$data.snapWidth : 0;
                        $data.tx = Math.min(0, Math.max(-$data.snapWidth, base + dx));
                    }, { passive: false });
                    document.addEventListener('mousemove', (e) => {
                        if (!$data.dragging) return;
                        let dx = e.clientX - $data.startX;
                        let dy = e.clientY - $data.startY;
                        if (Math.abs(dy) > Math.abs(dx) + 5) { $data.dragging = false; return; }
                        let base = $data.open ? -$data.snapWidth : 0;
                        $data.tx = Math.min(0, Math.max(-$data.snapWidth, base + dx));
                    });
                    document.addEventListener('mouseup', () => {
                        if ($data.dragging) $data.onEnd();
                    });
                "
                @touchstart="onStart($event)"
                @touchend="onEnd()"
                @mousedown="onMouseStart($event)"
                @swipe-reset.window="if ($event.detail.except !== {{ $notification->id }}) close()"
            >
                {{-- Delete button --}}
                <div class="absolute right-0 top-0 bottom-0 w-24 flex items-center justify-center">
                    <button
                        class="w-16 h-16 bg-red-500 rounded-2xl flex flex-col items-center justify-center gap-1 active:opacity-80"
                        wire:click="confirmDelete({{ $notification->id }})"
                        @click.stop
                    >
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                        </svg>
                        <span class="text-[10px] font-bold text-white">Delete</span>
                    </button>
                </div>

                {{-- Card --}}
                <a href="{{ route('notifications.show', $notification) }}"
                   class="card block p-5 bg-white relative z-10 rounded-none"
                   :style="`transform: translateX(${tx}px); transition: ${dragging ? 'none' : 'transform 0.2s ease-out'}; cursor: ${dragging ? 'grabbing' : 'grab'}`"
                   @click="if (open) { $event.preventDefault(); $event.stopPropagation(); close(); }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 truncate text-[15px]">{{ $notification->name }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold text-indigo-700 bg-indigo-50 rounded-md">
                                    {{ $notification->frequency_label ?? str_replace('_', ' ', $notification->schedule_type->value) }}
                                </span>
                                @if($notification->times)
                                    <span class="text-xs text-gray-400">{{ implode(', ', $notification->times) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 mt-0.5">
                            @if($notification->isEnded())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-slate-500 bg-slate-100 rounded-full border border-slate-200">
                                    Ended
                                </span>
                            @elseif($notification->is_active)
                                <span class="badge-shimmer inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-full shadow-sm shadow-green-500/20">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-gray-500 bg-gray-100 rounded-full border border-gray-200">
                                    Paused
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="card p-10 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-5 rounded-[20px] bg-gradient-to-br from-indigo-50 to-purple-50">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-900">No reminders yet</p>
                <p class="mt-1 text-sm text-gray-400">Create your first reminder to get started.</p>
                <a href="{{ route('notifications.create') }}" class="btn-primary inline-block mt-5 px-6 py-2.5 text-sm">
                    Create Reminder
                </a>
            </div>
        @endforelse
    </div>

    @if($notifications->count() < $total)
        <div class="px-4 mt-4">
            <button
                wire:click="loadMore"
                wire:loading.attr="disabled"
                wire:target="loadMore"
                class="w-full py-3 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-2xl border border-indigo-100 active:scale-[0.98] transition-all disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="loadMore">Show more</span>
                <span wire:loading wire:target="loadMore">Loading...</span>
            </button>
        </div>
    @endif

</x-pull-to-refresh>

@teleport('body')
    <div
        x-data="{ show: false }"
        x-on:show-delete-confirmation.window="show = true"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="show = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
        @click.self="show = false"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl"
        >
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Delete Reminder</h3>
            </div>
            <p class="text-sm text-gray-500 mb-5">Are you sure you want to delete this reminder? This will permanently delete the reminder and all its notification history. This action cannot be undone.</p>
            <div class="flex gap-3">
                <button type="button" @click="show = false"
                        class="flex-1 py-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 active:scale-[0.98] transition-all">
                    Cancel
                </button>
                <button type="button"
                        @click="show = false; $wire.delete()"
                        class="flex-1 py-3 text-sm font-bold text-red-500 bg-gray-100 rounded-xl hover:bg-red-50 active:scale-[0.98] transition-all">
                    Delete
                </button>
            </div>
        </div>
    </div>
@endteleport
</div>
