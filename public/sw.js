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
