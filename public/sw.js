/* CargoTrace service worker: makes the app installable (PWA) and caches static assets. Needs HTTPS or localhost. */
const CACHE = 'cargotrace-v2';
const ASSETS = ['/js/ct-i18n.js', '/js/ct-dict.js', '/js/ct-map.js', '/icons/icon-192.png', '/icons/icon-512.png'];
self.addEventListener('install', (e) => { e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting())); });
self.addEventListener('activate', (e) => { e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', (e) => {
  const r = e.request, u = new URL(r.url);
  if (r.method !== 'GET' || u.origin !== location.origin) { return; }
  // JS & ikon: network first, cache hanya sebagai cadangan saat offline
  if (ASSETS.includes(u.pathname)) {
    e.respondWith(
      fetch(r).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(r, copy));
        return res;
      }).catch(() => caches.match(r))
    );
    return;
  }
  e.respondWith(fetch(r).catch(() => caches.match(r)));   // pages: network first (always fresh, authenticated)
});