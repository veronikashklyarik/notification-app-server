const CACHE_VERSION = 'v1';
const STATIC_CACHE = `notifyr-static-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline';

// Vite-hashed assets — cache-first
const ASSET_PATTERNS = [
    /\/build\/assets\/.+\.(js|css)$/,
    /\/build\/manifest\.json$/,
    /\.(woff2?|ttf|otf|eot)$/,
];

// API / Livewire calls — always network only
const NETWORK_ONLY_PATTERNS = [
    /\/livewire\//,
    /\/api\//,
    /\/sanctum\//,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            cache.addAll([OFFLINE_URL])
        ).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Network-only: Livewire updates, API calls
    if (NETWORK_ONLY_PATTERNS.some((p) => p.test(url.pathname))) {
        event.respondWith(fetch(request));
        return;
    }

    // Cache-first: Vite-hashed static assets
    if (ASSET_PATTERNS.some((p) => p.test(url.pathname))) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) { return cached; }
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first with offline fallback for HTML navigation
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(OFFLINE_URL).then((r) => r || new Response('Offline', { status: 503 }))
            )
        );
        return;
    }
});

const REMINDER_LABELS = {
    en: (n, title) => `${n} reminder${n === 1 ? '' : 's'} for ${title}`,
    ru: (n, title) => {
        const form = n % 10 === 1 && n % 100 !== 11 ? 'напоминание'
            : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 'напоминания'
            : 'напоминаний';
        return `${n} ${form} для ${title}`;
    },
    uk: (n, title) => {
        const form = n % 10 === 1 && n % 100 !== 11 ? 'нагадування'
            : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 'нагадування'
            : 'нагадувань';
        return `${n} ${form} для ${title}`;
    },
    de: (n, title) => `${n} Erinnerung${n === 1 ? '' : 'en'} für ${title}`,
    fr: (n, title) => `${n} rappel${n === 1 ? '' : 's'} pour ${title}`,
    es: (n, title) => `${n} recordatorio${n === 1 ? '' : 's'} para ${title}`,
    pt: (n, title) => `${n} lembrete${n === 1 ? '' : 's'} para ${title}`,
    it: (n, title) => `${n} promemoria per ${title}`,
    pl: (n, title) => `${n} przypomnień dla ${title}`,
};

function formatReminderBody(count, title) {
    const lang = (self.navigator?.language ?? 'en').split('-')[0].toLowerCase();
    const fmt = REMINDER_LABELS[lang] ?? REMINDER_LABELS.en;
    return fmt(count, title);
}

// Web Push: display notification when received from server
self.addEventListener('push', (event) => {
    let data = { title: 'Notifyr', body: '' };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch {
            data.body = event.data.text();
        }
    }

    const tag = data.tag ?? 'notifyr';

    event.waitUntil(
        self.registration.getNotifications({ tag }).then((existing) => {
            const count = existing.length > 0 ? (existing[0].data?.count ?? 1) + 1 : 1;
            const body = count > 1 ? formatReminderBody(count, data.title) : (data.body || '');

            return self.registration.showNotification(data.title, {
                body,
                icon: data.icon ?? '/icon-192.png',
                badge: data.badge ?? '/badge-72.png',
                tag,
                renotify: count > 1,
                requireInteraction: data.requireInteraction ?? false,
                vibrate: [100, 50, 100],
                data: { url: data.url ?? '/', count },
            });
        })
    );
});

// Web Push: dismiss notification(s) for a specific event when acted on from inside the app
self.addEventListener('message', (event) => {
    if (event.data?.type !== 'dismiss-notification') {
        return;
    }

    event.waitUntil(
        self.registration.getNotifications().then((notifications) => {
            notifications
                .filter((n) => n.data?.url === event.data.url)
                .forEach((n) => n.close());
        })
    );
});

// Web Push: open/focus the app when the notification is clicked
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url ?? '/';

    event.waitUntil(
        self.registration.getNotifications().then((notifications) => {
            notifications.forEach((n) => n.close());

            return clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
                for (const client of windowClients) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(urlToOpen);
            });
        })
    );
});
