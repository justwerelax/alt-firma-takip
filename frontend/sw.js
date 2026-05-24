const CACHE = 'altfirma-v3';

// Sadece CSS ve JS cache'lenir — HTML her zaman ağdan çekilir
const STATIC = [
  './css/app.css?v=3',
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
  const url = e.request.url;

  // API istekleri her zaman ağdan
  if (url.includes('/backend/')) {
    e.respondWith(
      fetch(e.request).catch(() =>
        new Response('{"success":false,"error":"Cevrimdisi"}', {
          headers: { 'Content-Type': 'application/json' }
        })
      )
    );
    return;
  }

  // HTML dosyaları: önce ağdan, başarısız olursa cache
  if (e.request.destination === 'document' || url.endsWith('.html')) {
    e.respondWith(
      fetch(e.request)
        .then(res => {
          const clone = res.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone));
          return res;
        })
        .catch(() => caches.match(e.request))
    );
    return;
  }

  // CSS/JS: önce cache, yoksa ağdan
  e.respondWith(
    caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      const clone = res.clone();
      caches.open(CACHE).then(c => c.put(e.request, clone));
      return res;
    }))
  );
});
