const CACHE = 'altfirma-v2';
const STATIC = [
  './pages/dashboard.html',
  './pages/subcontractor.html',
  './pages/changelog.html',
  './css/app.css',
  './js/api.js',
  './js/auth.js',
  './js/utils.js',
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC).catch(() => {})));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys =>
    Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
  ));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  // API istekleri her zaman agdan
  if (e.request.url.includes('/backend/')) {
    e.respondWith(
      fetch(e.request).catch(() =>
        new Response('{"success":false,"error":"Cevrimdisi"}', {
          headers: { 'Content-Type': 'application/json' }
        })
      )
    );
    return;
  }
  // Statik: once cache, yoksa ag
  e.respondWith(
    caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      const clone = res.clone();
      caches.open(CACHE).then(c => c.put(e.request, clone));
      return res;
    }))
  );
});
