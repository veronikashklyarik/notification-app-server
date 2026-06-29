@auth
<div
    id="web-push-container"
    style="position: fixed; bottom: 84px; right: 16px; z-index: 9998; display: none;"
>
    <button
        id="web-push-btn"
        style="
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            gap: 8px;
        "
    >
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
        </svg>
        <span id="web-push-text">Enable Notifications</span>
    </button>
</div>

<script>
(function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
    }

    async function getVapidPublicKey() {
        const r = await fetch('/api/v1/push-subscriptions/vapid-public-key', {
            headers: { Accept: 'application/json' },
        });
        if (!r.ok) { return null; }
        const { public_key } = await r.json();
        return public_key ?? null;
    }

    async function sendSubscriptionToServer(subscription) {
        const json = subscription.toJSON();
        const r = await fetch('{{ route('push-subscriptions.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: { p256dh: json.keys.p256dh, auth: json.keys.auth },
            }),
        });
        return r.ok;
    }

    async function removeSubscriptionFromServer(endpoint) {
        const r = await fetch('{{ route('push-subscriptions.unsubscribe') }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ endpoint }),
        });
        return r.ok;
    }

    const container = document.getElementById('web-push-container');
    const btn       = document.getElementById('web-push-btn');
    const text      = document.getElementById('web-push-text');

    let reg          = null;
    let subscription = null;

    async function init() {
        try {
            reg          = await navigator.serviceWorker.ready;
            subscription = await reg.pushManager.getSubscription();
        } catch {
            return;
        }

        if (subscription) {
            // Already subscribed — button stays hidden
            container.style.display = 'none';
        } else {
            container.style.display = 'block';
        }
    }

    btn.addEventListener('click', async function () {
        btn.disabled  = true;
        text.textContent = 'Please wait…';

        try {
            if (subscription) {
                // Unsubscribe
                const endpoint = subscription.endpoint;
                const ok = await subscription.unsubscribe();
                if (ok) {
                    await removeSubscriptionFromServer(endpoint);
                    subscription = null;
                    container.style.display = 'block';
                    text.textContent = 'Enable Notifications';
                } else {
                    text.textContent = 'Enable Notifications';
                }
            } else {
                // Subscribe
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    text.textContent = 'Enable Notifications';
                    btn.disabled = false;
                    return;
                }

                const publicKey = await getVapidPublicKey();
                if (!publicKey) {
                    text.textContent = 'Enable Notifications';
                    btn.disabled = false;
                    return;
                }

                subscription = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey),
                });

                const saved = await sendSubscriptionToServer(subscription);
                if (saved) {
                    container.style.display = 'none';
                } else {
                    await subscription.unsubscribe();
                    subscription = null;
                    text.textContent = 'Enable Notifications';
                }
            }
        } catch (err) {
            console.error('Web Push error:', err);
            text.textContent = 'Enable Notifications';
        }

        btn.disabled = false;
    });

    document.addEventListener('DOMContentLoaded', init);
})();
</script>
@endauth
