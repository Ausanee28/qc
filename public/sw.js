const STATIC_CACHE = 'qc-static-v2';
const RUNTIME_CACHE = 'qc-runtime-v2';
const APP_SHELL = [
    '/',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
            .catch(() => Promise.resolve())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const cacheNames = await caches.keys();
        await Promise.all(
            cacheNames
                .filter((cacheName) => ![STATIC_CACHE, RUNTIME_CACHE].includes(cacheName))
                .map((cacheName) => caches.delete(cacheName))
        );
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
        return;
    }

    if (isStaticAsset(request)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

const isStaticAsset = (request) => {
    const destination = request.destination;

    return ['script', 'style', 'font', 'image'].includes(destination)
        || request.url.includes('/build/');
};

const networkFirst = async (request) => {
    const cache = await caches.open(RUNTIME_CACHE);

    try {
        const response = await fetch(request);

        if (response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        const shell = await caches.match('/');
        return shell || Response.error();
    }
};

const staleWhileRevalidate = async (request) => {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);

    const networkPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => cached);

    return cached || networkPromise;
};
