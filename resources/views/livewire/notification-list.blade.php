@php
    $tz = auth()->user()->timezone ?? 'UTC';
@endphp
<div x-data="{
    filter: $persist('all').as('notifyr-reminders-filter'),
    counts: {
        all: $wire.entangle('allCount'),
        active: $wire.entangle('activeCount'),
        paused: $wire.entangle('pausedCount'),
        ended: $wire.entangle('endedCount'),
    },
    buckets: $wire.entangle('notificationBuckets'),
    undoState: $wire.entangle('undoState'),
}">
<x-pull-to-refresh>
    <div class="px-5 md:px-0 pt-6 md:pt-0 pb-4">
        <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('Reminders') }}</h1>
    </div>

    {{-- Filter chips --}}
    <div class="px-5 md:px-0 flex items-center gap-2 overflow-x-auto" x-show="counts.all > 0" x-cloak>
        @foreach([
            ['key' => 'all', 'label' => __('All'), 'count' => $allCount],
            ['key' => 'active', 'label' => __('Active'), 'count' => $activeCount],
            ['key' => 'paused', 'label' => __('Paused'), 'count' => $pausedCount],
            ['key' => 'ended', 'label' => __('Ended'), 'count' => $endedCount],
        ] as $chip)
            <button
                type="button"
                @click="filter = '{{ $chip['key'] }}'"
                :class="filter === '{{ $chip['key'] }}' ? 'bg-ink text-white' : 'bg-white border border-border text-text-secondary'"
                class="shrink-0 px-3 py-2 text-[13px] font-semibold rounded-pill"
            >
                {{ $chip['label'] }} {{ $chip['count'] }}
            </button>
        @endforeach
    </div>

    <div class="px-5 md:px-0 mt-4 space-y-2" x-show="counts.all > 0">
        @foreach($notifications as $notification)
            @php
                $ended = $notification->isEnded();
                $bucket = $ended ? 'ended' : ($notification->is_active ? 'active' : 'paused');
                $dotColor = match ($bucket) {
                    'active' => 'bg-success',
                    'paused' => 'bg-neutral-dot',
                    'ended' => 'bg-neutral-dot-dark',
                };
                $nextEvent = $notification->nextUpcomingEvent;
                if ($bucket !== 'active') {
                    $nextLabel = null;
                } elseif ($nextEvent) {
                    $at = $nextEvent->scheduled_at->copy()->setTimezone($tz);
                    $dayDiff = now($tz)->startOfDay()->diffInDays($at->copy()->startOfDay());
                    $dayLabel = match (true) {
                        $dayDiff == 0 => __('Today'),
                        $dayDiff == 1 => __('Tomorrow'),
                        default => $at->translatedFormat('D j M'),
                    };
                    $nextLabel = __('Next — :day at :time', ['day' => $dayLabel, 'time' => $at->format('H:i')]);
                } else {
                    $nextLabel = null;
                }
            @endphp
            <div
                x-show="filter === 'all' || filter === buckets[{{ $notification->id }}]"
                wire:key="notification-{{ $notification->id }}"
                class="relative overflow-hidden rounded-card select-none"
                x-data="{
                    tx: 0,
                    open: false,
                    dragging: false,
                    startX: 0,
                    startY: 0,
                    snapWidth: 228,
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
                        if (this.tx < -60) {
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
                {{-- Swipe actions --}}
                <div class="absolute right-0 top-0 bottom-0 w-[228px] flex">
                    <button
                        type="button"
                        class="w-[76px] flex items-center justify-center bg-fill text-text-secondary"
                        wire:click="toggleActive({{ $notification->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleActive({{ $notification->id }})"
                        @click.stop
                    >
                        <span class="text-[13px] font-semibold">{{ $bucket === 'active' ? __('Pause') : __('Resume') }}</span>
                    </button>
                    <button
                        type="button"
                        class="w-[76px] flex items-center justify-center bg-indigo-tint text-brand"
                        wire:click="openEditSheet({{ $notification->id }})"
                        @click.stop
                    >
                        <span class="text-[13px] font-semibold">{{ __('Edit') }}</span>
                    </button>
                    <button
                        type="button"
                        class="w-[76px] flex items-center justify-center bg-danger-tint text-danger"
                        wire:click="confirmDelete({{ $notification->id }})"
                        @click.stop
                    >
                        <span class="text-[13px] font-semibold">{{ __('Delete') }}</span>
                    </button>
                </div>

                {{-- Card --}}
                <a href="{{ route('notifications.show', $notification) }}"
                   class="block p-4 bg-white border border-border relative z-10"
                   :style="`transform: translateX(${tx}px); transition: ${dragging ? 'none' : 'transform 0.22s ease'}; cursor: ${dragging ? 'grabbing' : 'grab'}`"
                   @click="if (open) { $event.preventDefault(); $event.stopPropagation(); close(); }"
                >
                    <div class="{{ $bucket === 'active' ? '' : 'opacity-[0.72]' }}">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full shrink-0 {{ $dotColor }}"></span>
                            <p class="flex-1 min-w-0 truncate text-[16px] font-semibold text-ink">{{ $notification->name }}</p>
                            @if($bucket !== 'active')
                                <span class="shrink-0 px-2 py-0.5 text-[12px] font-semibold text-text-secondary bg-fill rounded-tag">
                                    {{ $bucket === 'ended' ? __('Ended') : __('Paused') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm text-text-secondary truncate">{{ $notification->frequency_label }}</p>
                        @if($nextLabel)
                            <p class="mt-1 text-[13px] text-text-tertiary">{{ $nextLabel }}</p>
                        @elseif($bucket === 'ended')
                            <p class="mt-1 text-[13px] text-text-tertiary">{{ __('Ended :date · :done of :total done', ['date' => $notification->ends_at?->copy()->setTimezone($tz)->translatedFormat('j M'), 'done' => $notification->eventsDone, 'total' => $notification->eventsTotal]) }}</p>
                        @endif
                    </div>
                </a>

                {{-- Desktop text actions (no swipe gesture) --}}
                <div class="hidden md:flex items-center gap-4 px-4 pb-4 -mt-1 bg-white border-x border-b border-border relative z-10">
                    <button type="button" wire:click="toggleActive({{ $notification->id }})" wire:loading.attr="disabled" wire:target="toggleActive({{ $notification->id }})"
                            class="text-[13px] font-semibold text-text-secondary disabled:opacity-50">
                        {{ $bucket === 'active' ? __('Pause') : __('Resume') }}
                    </button>
                    <button type="button" wire:click="openEditSheet({{ $notification->id }})"
                       class="text-[13px] font-semibold text-text-secondary">{{ __('Edit') }}</button>
                    <button type="button" wire:click="confirmDelete({{ $notification->id }})"
                            class="text-[13px] font-semibold text-danger">{{ __('Delete') }}</button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Truly empty account: first-run onboarding, regardless of which tab happens to be selected --}}
    <div class="px-5 md:px-0" x-show="counts.all === 0" x-cloak>
        <div class="rounded-card border border-border bg-white p-5">
            <div class="w-11 h-11 rounded-full bg-indigo-tint flex items-center justify-center">
                <svg class="w-[22px] h-[22px] text-brand" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </div>
            <p class="mt-3.5 text-[19px] font-bold text-ink">{{ __('Your first reminder') }}</p>
            <div class="mt-3 flex flex-col gap-2.5">
                @foreach([
                    __('Name a thing you repeat — a pill, a workout, a bill.'),
                    __('Pick when: every day, some weekdays, a cycle or exact dates.'),
                    __('Mark it Done when the push arrives. Today and History keep score.'),
                ] as $i => $step)
                    <p class="flex gap-2.5 text-sm leading-[1.45] text-text-secondary">
                        <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-xs font-semibold flex items-center justify-center">{{ $i + 1 }}</span>
                        {{ $step }}
                    </p>
                @endforeach
            </div>
            <button type="button" wire:click="openCreateSheet" class="block w-full mt-[18px] h-12 leading-[48px] text-center rounded-field bg-brand text-[15px] font-semibold text-white">
                {{ __('Create reminder') }}
            </button>
        </div>
    </div>

    <div class="px-5 md:px-0 mt-[18px]" x-show="counts.all === 0" x-cloak>
        <p class="px-1 mb-2 text-xs font-semibold uppercase tracking-label text-text-tertiary">{{ __('Or start from an example') }}</p>
        <div class="flex flex-col gap-2">
            @foreach([
                ['key' => 'vitamins', 'name' => __('Vitamins'), 'hint' => __('Every day at 08:00')],
                ['key' => 'water', 'name' => __('Water the plants'), 'hint' => __('Every 3 days at 19:00')],
                ['key' => 'rent', 'name' => __('Pay rent'), 'hint' => __('On the 1st of every month at 10:00')],
            ] as $template)
                <button type="button" wire:click="openCreateSheet('{{ $template['key'] }}')" class="w-full flex items-center gap-3 px-3.5 py-3 min-h-14 rounded-field bg-white border border-border text-left">
                    <span class="flex-1 min-w-0">
                        <span class="block text-[15px] font-semibold text-ink">{{ $template['name'] }}</span>
                        <span class="block text-[13px] text-text-tertiary">{{ $template['hint'] }}</span>
                    </span>
                    <span class="shrink-0 text-xl text-brand leading-none">+</span>
                </button>
            @endforeach
        </div>
    </div>

    @php
        $emptyFilterStates = [
            'active' => ['title' => __('No active reminders'), 'body' => __('Resume one from its card, or create a new reminder.')],
            'paused' => ['title' => __('No paused reminders'), 'body' => __('Pause one from its card or detail page — it stays here, silent, until you resume it.')],
            'ended' => ['title' => __('No ended reminders'), 'body' => __('Reminders with an end date land here once their course is over.')],
        ];
    @endphp
    <div class="px-4 md:px-0 mt-4" x-show="counts.all > 0" x-cloak>
        @foreach($emptyFilterStates as $key => $state)
            <div x-show="filter === '{{ $key }}' && counts['{{ $key }}'] === 0" x-cloak class="rounded-card border border-border bg-white p-10 text-center">
                <p class="text-[19px] font-bold text-ink">{{ $state['title'] }}</p>
                <p class="mt-1 text-sm text-text-secondary">{{ $state['body'] }}</p>
                <button type="button" @click="filter = 'all'" class="block w-full mt-5 h-12 rounded-field border border-border text-[15px] font-semibold text-text-secondary">
                    {{ __('Show all :count', ['count' => $allCount]) }}
                </button>
            </div>
        @endforeach
    </div>

    <p class="md:hidden px-5 md:px-0 mt-3 text-[12px] text-text-quaternary" x-show="counts.all > 0 && counts[filter] > 0" x-cloak>{{ __('Swipe a card left for pause, edit and delete.') }}</p>

</x-pull-to-refresh>

<x-undo-bar on-undo="$wire.undoCreate()" />

{{-- FAB --}}
<button type="button" wire:click="openCreateSheet"
   class="md:hidden fixed z-40 right-5 w-[58px] h-[58px] rounded-full bg-brand shadow-fab flex items-center justify-center transition-[bottom] duration-200"
   :class="undoState ? 'bottom-[calc(172px+env(safe-area-inset-bottom))]' : 'bottom-[calc(104px+env(safe-area-inset-bottom))]'"
   aria-label="{{ __('New reminder') }}">
    <svg class="w-[26px] h-[26px]" fill="none" stroke="white" stroke-width="2.1" stroke-linecap="round" viewBox="0 0 24 24">
        <path d="M12 5v14M5 12h14" />
    </svg>
</button>

@teleport('body')
    <div
        x-data="{
            name: $wire.entangle('pendingDeleteName'),
            marksCount: $wire.entangle('pendingDeleteMarksCount'),
            titleTemplate: @js(__('Delete “:name”?')),
            bodyTemplate: @js(trans_choice('Its :count mark goes with it, and nothing more will be sent. This action cannot be undone.|Its :count marks go with it, and nothing more will be sent. This action cannot be undone.', 2, ['count' => ':count'])),
        }"
    >
        <x-confirm-dialog
            show="name !== null"
            :confirm-label="__('Delete')"
            on-confirm="$wire.delete()"
            on-cancel="$wire.cancelDelete()"
        >
            <template x-if="name !== null">
                <div>
                    <h3 class="text-[19px] font-bold text-ink" x-text="titleTemplate.replace(':name', name)"></h3>
                    <p class="mt-2" x-text="bodyTemplate.replace(':count', marksCount)"></p>
                </div>
            </template>
        </x-confirm-dialog>
    </div>
@endteleport

@if($sheetOpen)
    @if($sheetMode === 'create')
        <livewire:notification-create :embedded="true" :template="$sheetTemplate" wire:key="sheet-create-{{ $sheetTemplate }}" />
    @else
        @php($sheetNotification = auth()->user()->reminders()->find($sheetNotificationId))
        @if($sheetNotification)
            <livewire:notification-edit :notification="$sheetNotification" :embedded="true" wire:key="sheet-edit-{{ $sheetNotificationId }}" />
        @else
            {{-- Record vanished (deleted elsewhere) between opening the sheet and this render — close it instead of rendering nothing. --}}
            <div x-init="$wire.closeSheet()"></div>
        @endif
    @endif
@endif
</div>
