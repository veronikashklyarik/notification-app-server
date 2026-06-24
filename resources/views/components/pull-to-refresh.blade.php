@props(['class' => ''])

<div
    {{ $attributes->merge(['class' => $class]) }}
    x-data="{
        pulling: false,
        refreshing: false,
        pullY: 0,
        startY: 0,
        threshold: 80,
        mouseDown: false,

        onTouchStart(e) {
            if (window.scrollY > 0) return;
            this.startY = e.touches[0].clientY;
        },
        onTouchMove(e) {
            if (this.refreshing || window.scrollY > 0) return;
            const dy = e.touches[0].clientY - this.startY;
            if (dy > 0) {
                this.pulling = true;
                this.pullY = Math.min(dy * 0.4, this.threshold);
            }
        },
        async onTouchEnd() {
            if (!this.pulling) return;
            if (this.pullY >= this.threshold * 0.9) {
                this.refreshing = true;
                this.pullY = 0;
                this.pulling = false;
                await $wire.refresh();
                this.refreshing = false;
            } else {
                this.pulling = false;
                this.pullY = 0;
            }
        },
        onMouseStart(e) {
            if (window.scrollY > 0) return;
            this.startY = e.clientY;
            this.mouseDown = true;
        },
        onMouseMove(e) {
            if (!this.mouseDown || this.refreshing || window.scrollY > 0) return;
            const dy = e.clientY - this.startY;
            if (dy > 0) {
                this.pulling = true;
                this.pullY = Math.min(dy * 0.4, this.threshold);
            } else {
                this.pulling = false;
                this.pullY = 0;
            }
        },
        async onMouseUp() {
            if (!this.mouseDown) return;
            this.mouseDown = false;
            await this.onTouchEnd();
        }
    }"
    @touchstart="onTouchStart"
    @touchmove.passive="onTouchMove"
    @touchend="onTouchEnd"
    @mousedown="onMouseStart"
    @mousemove.window="onMouseMove"
    @mouseup.window="onMouseUp"
>
    {{-- Pull-to-refresh indicator --}}
    <div
        class="flex items-center justify-center overflow-hidden transition-all duration-200"
        :style="refreshing ? 'height: 56px;' : (pulling ? `height: ${pullY * 0.7}px;` : 'height: 0px;')"
    >
        <div
            class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center transition-transform duration-200"
            :class="refreshing ? 'animate-spin' : ''"
            :style="!refreshing ? `transform: rotate(${(pullY / threshold) * 180}deg)` : ''"
        >
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </div>
    </div>

    {{ $slot }}
</div>
