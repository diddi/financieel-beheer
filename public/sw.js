const CACHE_NAME = 'financieel-beheer-v1';
const urlsToCache = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/manifest.json',
  '/assets/images/app-icon-192.png',
  '/assets/images/app-icon-512.png',
  // Voeg andere belangrijke bestanden toe
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      }
    )
  );
});