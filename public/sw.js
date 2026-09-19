// Simple offline queue helper - full Workbox can be added later
self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => self.clients.claim());
self.addEventListener('fetch', event => {
  // Network-first for API, cache-first for assets
  if (event.request.url.includes('/api') || event.request.url.includes('/tickets')) {
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
  }
});
