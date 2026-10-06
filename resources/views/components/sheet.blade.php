@props(['title', 'backUrl' => null, 'formId' => 'reminder-form', 'onClose' => null])
@php
    $closeAction = $onClose ?? ('window.location = '.\Illuminate\Support\Js::from($backUrl));
@endphp
<div
    x-data="{
        y: 760,
        dragging: false,
        startY: 0,
        baseY: 0,
        dirty: false,
        confirmingDiscard: false,
        discardMessage: '',
        close() {
            this.y = 760;
            setTimeout(() => { {{ $closeAction }} }, 360);
        },
        attemptClose() {
            if (this.dirty) {
                this.y = 0;
                // Computed once, right as the dialog opens, and stored in
                // reactive state — x-text only re-runs when a *reactive*
                // dependency changes, and reading the name input's raw DOM
                // value (plain querySelector, not $refs: the input lives in
                // the form's own nested x-data scope, which $refs can't see
                // past) doesn't register as one, so this can't be computed
                // lazily inside the binding itself.
                const input = document.querySelector('[data-name-input]');
                const name = (input && input.value.trim()) || @js(__('This reminder'));
                this.discardMessage = @js(__('“:name” hasn’t been saved. Close anyway and lose what you entered?')).replace(':name', name);
                this.confirmingDiscard = true;
            } else {
                this.close();
            }
        },
        onGrabStart(e) {
            this.dragging = true;
            this.startY = e.touches ? e.touches[0].clientY : e.clientY;
            this.baseY = this.y;
        },
        onGrabMove(e) {
            if (!this.dragging) return;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            this.y = Math.max(0, this.baseY + (clientY - this.startY));
        },
        onGrabEnd() {
            if (!this.dragging) return;
            this.dragging = false;
            if (this.y > 130) { this.attemptClose(); } else { this.y = 0; }
        },
    }"
    x-init="requestAnimationFrame(() => requestAnimationFrame(() => { y = 0 }))"
    @input.capture="dirty = true"
    @change.capture="dirty = true"
    class="fixed inset-0 z-[55]"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0"
        :style="`background: rgba(16,20,40, ${(0.45 * Math.max(0, 1 - y / 620)).toFixed(3)}); transition: ${dragging ? 'none' : 'background .36s cubic-bezier(.32,.72,0,1)'}`"
        @click="attemptClose()"
    ></div>

    {{-- Sheet --}}
    <div
        class="absolute left-0 right-0 bottom-0 top-[max(28px,env(safe-area-inset-top))] bg-canvas rounded-t-sheet overflow-hidden flex flex-col shadow-sheet
               md:inset-0 md:top-0 md:m-auto md:w-full md:max-w-[520px] md:h-fit md:max-h-[85vh] md:rounded-sheet"
        :style="`transform: translateY(${y}px); transition: ${dragging ? 'none' : 'transform .36s cubic-bezier(.32,.72,0,1)'}`"
        @touchmove.passive="onGrabMove($event)"
        @touchend="onGrabEnd()"
        @mousemove.window="onGrabMove($event)"
        @mouseup.window="onGrabEnd()"
    >
        {{-- Grabber --}}
        <div class="md:hidden shrink-0 pt-2.5 pb-1 flex justify-center cursor-grab" @touchstart="onGrabStart($event)" @mousedown="onGrabStart($event)">
            <div class="w-[38px] h-[5px] rounded-full bg-border-dashed"></div>
        </div>

        {{-- Sticky header --}}
        <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-border" style="background: rgba(246,247,249,0.92); backdrop-filter: blur(20px)">
            <button type="button" @click="attemptClose()" class="w-[34px] h-[34px] rounded-full bg-avatar-bg flex items-center justify-center" aria-label="{{ __('Cancel') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round">
                    <path d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <span class="text-[16px] font-semibold text-ink">{{ $title }}</span>
            <button type="submit" form="{{ $formId }}" wire:loading.attr="disabled" wire:target="save"
                    class="w-[34px] h-[34px] rounded-full bg-brand flex items-center justify-center disabled:opacity-50" aria-label="{{ __('Save') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="white" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>
    </div>

    <x-confirm-dialog
        show="confirmingDiscard"
        :title="__('Discard this reminder?')"
        :confirm-label="__('Discard')"
        :cancel-label="__('Keep editing')"
        on-confirm="close()"
        on-cancel="confirmingDiscard = false"
    >
        <span x-text="discardMessage"></span>
    </x-confirm-dialog>
</div>
