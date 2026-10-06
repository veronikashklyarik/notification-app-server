@auth
<div
    x-data="pushNotificationSettings('{{ __('Disabling…') }}', '{{ __('Disable') }}', '{{ __('Enabling…') }}', '{{ __('Enable') }}')"
    x-init="init()"
    x-cloak
    x-show="supported"
>
    {{-- Subscribed --}}
    <template x-if="state === 'subscribed'">
        <div class="rounded-card bg-white border border-border px-4 py-[15px]">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="{{ $labelClass ?? 'text-[15px] font-semibold text-ink' }}">{{ __('Push on this device') }}</p>
                    <p class="{{ $subLabelClass ?? 'mt-0.5 text-[13px] text-success' }}" x-text="platformOnLabel()"></p>
                </div>
                <button
                    type="button"
                    @click="disable()"
                    :disabled="loading"
                    class="shrink-0 relative w-[50px] h-[30px] rounded-full bg-brand disabled:opacity-50"
                >
                    <span class="absolute top-0.5 left-0.5 translate-x-5 w-6 h-6 rounded-full bg-white transition-transform"></span>
                </button>
            </div>
            <template x-if="!isInstalled">
                <a href="{{ route('install') }}" class="mt-2.5 block text-[13px] font-semibold text-brand">{{ __('Install as an app') }}</a>
            </template>
        </div>
    </template>

    {{-- Not subscribed (permission default or granted but no subscription) --}}
    <template x-if="state === 'prompt'">
        <div class="rounded-card bg-white border border-border px-4 py-[15px]">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="{{ $labelClass ?? 'text-[15px] font-semibold text-ink' }}">{{ __('Push on this device') }}</p>
                    <p class="{{ $subLabelClass ?? 'mt-0.5 text-[13px] text-warning' }}">{{ __('Off — nothing will be sent') }}</p>
                </div>
                <button
                    type="button"
                    @click="enable()"
                    :disabled="loading"
                    class="shrink-0 relative w-[50px] h-[30px] rounded-full bg-border-input disabled:opacity-50"
                >
                    <span class="absolute top-0.5 left-0.5 translate-x-0.5 w-6 h-6 rounded-full bg-white transition-transform"></span>
                </button>
            </div>
            <template x-if="!isInstalled">
                <a href="{{ route('install') }}" class="mt-2.5 block text-[13px] font-semibold text-brand">{{ __('Install as an app') }}</a>
            </template>
        </div>
    </template>

    {{-- iOS Safari, not installed — push is unavailable at the OS level until installed --}}
    <template x-if="state === 'ios-not-installed'">
        <div class="rounded-card bg-white border border-border px-4 py-[15px]">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="{{ $labelClass ?? 'text-[15px] font-semibold text-ink' }}">{{ __('Push on this device') }}</p>
                    <p class="{{ $subLabelClass ?? 'mt-0.5 text-[13px] text-warning' }}">{{ __('Not available in Safari tabs') }}</p>
                </div>
                <span class="shrink-0 relative w-[50px] h-[30px] rounded-full bg-border-input opacity-50">
                    <span class="absolute top-0.5 translate-x-0.5 w-6 h-6 rounded-full bg-white"></span>
                </span>
            </div>
            <p class="mt-2.5 text-[13px] leading-normal text-text-tertiary">{{ __('On iPhone, reminders can only reach you once :appName is on your Home Screen.', ['appName' => config('app.name')]) }}</p>
            <a href="{{ route('install') }}" class="mt-3 block w-full h-11 leading-[44px] rounded-field bg-brand text-center text-[14px] font-semibold text-white">
                {{ __('How to install') }}
            </a>
        </div>
    </template>

    {{-- Denied — notifications are blocked at the OS/browser level; no toggle, it can't do anything --}}
    <template x-if="state === 'denied'">
        <div class="rounded-field bg-warning-tint border border-warning-tint-border p-3.5">
            <p class="text-[15px] font-semibold text-warning-text">{{ __('Notifications are blocked') }}</p>
            <p class="mt-1 text-[13px] leading-normal text-warning-text">{{ __("You turned them off for :appName, so the app can't ask again. Turn them back on in your settings:", ['appName' => config('app.name')]) }}</p>

            @php
                $deniedSteps = [
                    'ios-pwa' => [__('Open the Settings app on your iPhone'), __('Scroll down and tap :appName', ['appName' => config('app.name')]), __('Tap Notifications and toggle Allow Notifications on'), __('Return here and reload the app')],
                    'ios-safari' => [__('Tap the Share button in Safari (box with arrow)'), __('Tap Add to Home Screen'), __('Open the app from your Home Screen and enable notifications')],
                    'chrome' => [__('Click the lock icon (or info icon) in the address bar'), __('Click Site settings'), __('Find Notifications and set it to Allow'), __('Reload this page')],
                    'firefox' => [__('Click the lock icon in the address bar'), __('Click Connection Secure → More Information'), __('Go to Permissions tab'), __('Find Send Notifications and uncheck Block'), __('Reload this page')],
                    'safari-desktop' => [__('Open Safari → Settings (or Preferences)'), __('Click the Websites tab'), __('Select Notifications on the left'), __('Find this site and change it to Allow'), __('Reload this page')],
                    'other' => [__("Open your browser's site settings for this page, find Notifications, and set it to Allow, then reload.")],
                ];
            @endphp

            @foreach($deniedSteps as $platformKey => $steps)
                <template x-if="platform === '{{ $platformKey }}'">
                    <div class="mt-3 space-y-2.5">
                        @if($platformKey === 'ios-safari')
                            <p class="text-[13px] font-semibold text-warning-text">{{ __('Web push requires the app to be installed:') }}</p>
                        @endif
                        @foreach($steps as $index => $step)
                            @if(count($steps) === 1)
                                <p class="text-[14px] leading-relaxed text-warning-text">{{ $step }}</p>
                            @else
                                <div class="flex items-start gap-2.5">
                                    <span class="shrink-0 mt-0.5 w-[20px] h-[20px] rounded-full bg-warning text-white text-[11px] font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                    <p class="text-[14px] leading-relaxed text-warning-text">{{ $step }}</p>
                                </div>
                            @endif
                        @endforeach
                        @if($platformKey === 'ios-safari')
                            <p class="text-[12px] text-warning-text/80">{{ __('Requires iOS 16.4 or later.') }}</p>
                        @endif
                    </div>
                </template>
            @endforeach
        </div>
    </template>
</div>

<script>
function pushNotificationSettings(txtDisabling, txtDisable, txtEnabling, txtEnable) {
    return {
        supported: false,
        state: 'prompt',   // 'subscribed' | 'prompt' | 'denied' | 'ios-not-installed'
        platform: 'other', // 'ios-pwa' | 'ios-safari' | 'chrome' | 'firefox' | 'safari-desktop' | 'other'
        isInstalled: false,
        loading: false,
        reg: null,
        subscription: null,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        txtDisabling,
        txtDisable,
        txtEnabling,
        txtEnable,

        async init() {
            this.platform = this.detectPlatform();
            this.isInstalled = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

            if (this.platform === 'ios-safari') {
                this.supported = true;
                this.state = 'ios-not-installed';
                return;
            }

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }
            this.supported = true;

            if (Notification.permission === 'denied') {
                this.state = 'denied';
                return;
            }

            try {
                this.reg = await navigator.serviceWorker.ready;
                this.subscription = await this.reg.pushManager.getSubscription();
            } catch {
                return;
            }

            // A browser-level subscription can belong to a different account that
            // previously signed in on this same device — confirm it's actually ours
            // before showing the toggle as on.
            if (this.subscription) {
                try {
                    const statusRes = await fetch('{{ route('push-subscriptions.status') }}?endpoint=' + encodeURIComponent(this.subscription.endpoint), {
                        headers: { Accept: 'application/json' },
                    });
                    const { subscribed } = await statusRes.json();
                    if (!subscribed) {
                        this.subscription = null;
                    }
                } catch {
                    // If the check itself fails, fall back to trusting the browser.
                }
            }

            this.state = this.subscription ? 'subscribed' : 'prompt';
        },

        detectPlatform() {
            const ua = navigator.userAgent;
            const isIOS = /iP(hone|ad|od)/.test(ua);
            const isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;

            if (isIOS && isStandalone) return 'ios-pwa';
            if (isIOS) return 'ios-safari';
            if (/Firefox\//.test(ua)) return 'firefox';
            if (/Edg\//.test(ua) || /Chrome\//.test(ua)) return 'chrome';
            if (/Safari\//.test(ua)) return 'safari-desktop';
            return 'other';
        },

        platformOnLabel() {
            const labels = {
                'ios-pwa': @json(__('On · iPhone, Safari')),
                'ios-safari': @json(__('On · iPhone, Safari')),
                'chrome': @json(__('On · Chrome')),
                'firefox': @json(__('On · Firefox')),
                'safari-desktop': @json(__('On · Mac, Safari')),
                'other': @json(__('On · this device')),
            };
            return labels[this.platform] ?? labels.other;
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = atob(base64);
            return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
        },

        async enable() {
            this.loading = true;
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'denied') {
                    this.state = 'denied';
                    return;
                }
                if (permission !== 'granted') {
                    return; // dismissed — let them try again
                }

                const r = await fetch('/api/v1/push-subscriptions/vapid-public-key', { headers: { Accept: 'application/json' } });
                if (!r.ok) { return; }
                const { public_key } = await r.json();

                this.subscription = await this.reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(public_key),
                });

                const json = this.subscription.toJSON();
                const res = await fetch('{{ route('push-subscriptions.subscribe') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify({ endpoint: json.endpoint, keys: { p256dh: json.keys.p256dh, auth: json.keys.auth } }),
                });

                if (res.ok) {
                    this.state = 'subscribed';
                } else {
                    await this.subscription.unsubscribe();
                    this.subscription = null;
                }
            } catch (err) {
                console.error('Push enable error:', err);
                if (Notification.permission === 'denied') {
                    this.state = 'denied';
                }
            } finally {
                this.loading = false;
            }
        },

        async disable() {
            this.loading = true;
            try {
                const endpoint = this.subscription.endpoint;
                const ok = await this.subscription.unsubscribe();
                if (ok) {
                    await fetch('{{ route('push-subscriptions.unsubscribe') }}', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                        body: JSON.stringify({ endpoint }),
                    });
                    this.subscription = null;
                    this.state = 'prompt';
                }
            } catch (err) {
                console.error('Push disable error:', err);
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endauth
