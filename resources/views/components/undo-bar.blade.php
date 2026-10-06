@props([
    'onUndo' => "(state.eventIds ? \$wire.restoreBulk(state.eventIds, state.previousStatus) : \$wire.restoreStatus(state.eventId, state.previousStatus))",
])

<div
    x-data="{
        state: $wire.entangle('undoState'),
        dismissed: false,
        timer: null,
    }"
    x-init="$watch('state', (value) => {
        clearTimeout(timer);
        if (value) {
            dismissed = false;
            timer = setTimeout(() => { dismissed = true; $wire.set('undoState', null) }, 5000);
        }
    })"
>
    <div
        x-show="state && !dismissed"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="md:hidden fixed z-40 left-5 right-5 bottom-[calc(108px+env(safe-area-inset-bottom))] flex items-center gap-3 rounded-field bg-ink px-4 py-3.5 shadow-undo"
    >
        <span class="flex-1 min-w-0 text-sm font-medium text-white truncate" x-text="state && state.label"></span>
        <button
            type="button"
            class="text-sm font-semibold text-white shrink-0"
            @click="{{ $onUndo }}; dismissed = true; clearTimeout(timer)"
        >{{ __('Undo') }}</button>
    </div>
</div>
