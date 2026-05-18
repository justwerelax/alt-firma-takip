const CACHE_NAME = 'alt-firma-takip-v2';
const ASSETS_TO_CACHE = [
    '/frontend/',
    '/frontend/index.html',
    '/frontend/pages/login.html',
    '/frontend/pages/dashboard.html',
    '/frontend/pages/subcontractor.html',
    '/frontend/pages/changelog.html',
    '/frontend/js/api.js',
    '/frontend/js/auth.js',
    '/frontend/js/utils.js',
    '/frontend/js/app.js',
    '/frontend/css/app.css',
    '/frontend/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(ASSETS_TO_CACHE))
            .catch(err => console.error('Cache error:', err))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;

    // API istekleri her zaman network'ten — cache'leme yok
    if (url.pathname.startsWith('/backend/')) {
        event.respondWith(
            fetch(request).catch(() => new Response(
                JSON.stringify({ success: false, error: 'Çevrimdışı — sunucuya erişilemiyor' }),
                { status: 503, headers: { 'Content-Type': 'application/json' } }
            ))
        );
        return;
    }

    // Statik dosyalar: cache-first
    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(response => {
                if (!response || response.status !== 200 || response.type === 'error') return response;
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                return response;
            }).catch(() => caches.match('/frontend/index.html'));
        })
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});
