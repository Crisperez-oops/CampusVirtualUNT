const CACHE = 'cv-unt-v1';
const ASSETS = [
  '/proyecto%20uni/index.php',
  '/proyecto%20uni/assets/css/estilo.css',
  '/proyecto%20uni/assets/css/feed-fb.css',
  '/proyecto%20uni/assets/css/social.css',
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
});

self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(r => r || fetch(e.request))
  );
});
