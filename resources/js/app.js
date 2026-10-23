import './bootstrap';

// Alpine.js is bundled and started by Livewire 4 — do not import or start it here.
// If you need to register Alpine plugins/components, use:
//   document.addEventListener('alpine:init', () => { ... });

document.addEventListener('alpine:init', () => {
    // Live caption for the Home next-up card; mirrors Home::countdownLabel() so the first tick doesn't jump.
    window.Alpine.data('nextUpCountdown', (scheduledAtMs, texts) => ({
        label: '',
        isOverdue: false,
        timeoutId: null,
        intervalId: null,

        init() {
            this.onVisible = () => {
                if (document.visibilityState === 'visible') {
                    this.update();
                    this.schedule();
                }
            };

            this.update();
            this.schedule();
            document.addEventListener('visibilitychange', this.onVisible);
            window.addEventListener('pageshow', this.onVisible);
        },

        destroy() {
            this.clearTimers();
            document.removeEventListener('visibilitychange', this.onVisible);
            window.removeEventListener('pageshow', this.onVisible);
        },

        // Ticks on wall-clock minute boundaries, so the caption flips together with the clock.
        schedule() {
            this.clearTimers();
            this.timeoutId = setTimeout(() => {
                this.update();
                this.intervalId = setInterval(() => this.update(), 60_000);
            }, 60_000 - (Date.now() % 60_000) + 50);
        },

        clearTimers() {
            clearTimeout(this.timeoutId);
            clearInterval(this.intervalId);
        },

        update() {
            const secondsUntil = (scheduledAtMs - Date.now()) / 1000;

            if (secondsUntil > 0) {
                this.isOverdue = false;
                this.label = texts.in.replace(':duration', this.formatMinutes(Math.ceil(secondsUntil / 60)));
            } else if (secondsUntil > -60) {
                this.isOverdue = false;
                this.label = texts.now;
            } else {
                this.isOverdue = true;
                this.label = texts.overdue.replace(':duration', this.formatMinutes(Math.floor(-secondsUntil / 60)));
            }
        },

        formatMinutes(minutes) {
            if (minutes < 60) {
                return texts.minutes.replace(':count', minutes);
            }

            const hours = Math.floor(minutes / 60);
            const remainder = minutes % 60;

            return remainder > 0
                ? texts.hoursMinutes.replace(':hours', hours).replace(':minutes', remainder)
                : texts.hours.replace(':count', hours);
        },
    }));
});
