@auth
@if('serviceWorker' !== '' && 'PushManager' !== '')
<div
    x-data="pushNotificationSettings()"
    x-init="init()"
    x-cloak
    x-show="supported"
>
    {{-- Subscribed --}}
    <template x-if="state === 'subscribed'">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="{{ $iconClass ?? 'w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0' }}">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div>
                    <p class="{{ $labelClass ?? 'text-sm font-semibold text-gray-900' }}">Push Notifications</p>
                    <p class="{{ $subLabelClass ?? 'text-xs text-emerald-600 mt-0.5' }}">Enabled</p>
                </div>
            </div>
            <button
                @click="disable()"
                :disabled="loading"
                class="{{ $disableBtnClass ?? 'text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50' }}"
                x-text="loading ? 'Disabling…' : 'Disable'"
            ></button>
        </div>
    </template>

    {{-- Not subscribed (permission default or granted but no subscription) --}}
    <template x-if="state === 'prompt'">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="{{ $iconClass ?? 'w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0' }}">
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                    </svg>
                </div>
                <div>
                    <p class="{{ $labelClass ?? 'text-sm font-semibold text-gray-900' }}">Push Notifications</p>
                    <p class="{{ $subLabelClass ?? 'text-xs text-gray-400 mt-0.5' }}">Not enabled</p>
                </div>
            </div>
            <button
                @click="enable()"
                :disabled="loading"
                class="{{ $enableBtnClass ?? 'text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors disabled:opacity-50' }}"
                x-text="loading ? 'Enabling…' : 'Enable'"
            ></button>
        </div>
    </template>

    {{-- Denied — show browser/PWA-specific instructions --}}
    <template x-if="state === 'denied'">
        <div>
            <div class="flex items-start gap-3">
                <div class="{{ $iconClass ?? 'w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0' }}">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="{{ $labelClass ?? 'text-sm font-semibold text-gray-900' }}">Push Notifications</p>
                    <p class="{{ $subLabelClass ?? 'text-xs text-amber-600 mt-0.5' }}">Blocked by browser</p>
                </div>
            </div>

            {{-- iOS PWA (standalone) --}}
            <template x-if="platform === 'ios-pwa'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">To enable notifications:</p>
                    <p>1. Open the <strong>Settings</strong> app on your iPhone</p>
                    <p>2. Scroll down and tap <strong>Notifyr</strong></p>
                    <p>3. Tap <strong>Notifications</strong> and toggle <strong>Allow Notifications</strong> on</p>
                    <p>4. Return here and reload the app</p>
                </div>
            </template>

            {{-- iOS Safari (not installed as PWA) --}}
            <template x-if="platform === 'ios-safari'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">Web push requires the app to be installed:</p>
                    <p>1. Tap the <strong>Share</strong> button in Safari (box with arrow)</p>
                    <p>2. Tap <strong>Add to Home Screen</strong></p>
                    <p>3. Open the app from your Home Screen and enable notifications</p>
                    <p class="text-amber-600">Requires iOS 16.4 or later.</p>
                </div>
            </template>

            {{-- Chrome (desktop & Android) --}}
            <template x-if="platform === 'chrome'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">To enable notifications in Chrome:</p>
                    <p>1. Click the <strong>lock icon</strong> (or info icon) in the address bar</p>
                    <p>2. Click <strong>Site settings</strong></p>
                    <p>3. Find <strong>Notifications</strong> and set it to <strong>Allow</strong></p>
                    <p>4. Reload this page</p>
                </div>
            </template>

            {{-- Firefox --}}
            <template x-if="platform === 'firefox'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">To enable notifications in Firefox:</p>
                    <p>1. Click the <strong>lock icon</strong> in the address bar</p>
                    <p>2. Click <strong>Connection Secure → More Information</strong></p>
                    <p>3. Go to <strong>Permissions</strong> tab</p>
                    <p>4. Find <strong>Send Notifications</strong> and uncheck <em>Block</em></p>
                    <p>5. Reload this page</p>
                </div>
            </template>

            {{-- macOS Safari --}}
            <template x-if="platform === 'safari-desktop'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">To enable notifications in Safari:</p>
                    <p>1. Open <strong>Safari → Settings</strong> (or Preferences)</p>
                    <p>2. Click the <strong>Websites</strong> tab</p>
                    <p>3. Select <strong>Notifications</strong> on the left</p>
                    <p>4. Find this site and change it to <strong>Allow</strong></p>
                    <p>5. Reload this page</p>
                </div>
            </template>

            {{-- Fallback for other browsers --}}
            <template x-if="platform === 'other'">
                <div class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-semibold">To enable notifications:</p>
                    <p>Open your browser's site settings for this page, find <strong>Notifications</strong>, and set it to <strong>Allow</strong>, then reload.</p>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function pushNotificationSettings() {
    return {
        supported: false,
        state: 'prompt',   // 'subscribed' | 'prompt' | 'denied'
        platform: 'other', // 'ios-pwa' | 'ios-safari' | 'chrome' | 'firefox' | 'safari-desktop' | 'other'
        loading: false,
        reg: null,
        subscription: null,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',

        async init() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }
            this.supported = true;
            this.platform = this.detectPlatform();

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
@endif
@endauth
