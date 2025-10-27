// Nama cache
const CACHE_NAME = 'media-uploader-v1';
// Daftar file yang akan di-cache
const urlsToCache = [
  '/',
  '/js/upload.js',
  'https://cdn.tailwindcss.com'
  // Anda bisa menambahkan file CSS atau font lain di sini
];

// Event Install: Mendaftarkan file ke cache
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache dibuka');
        return cache.addAll(urlsToCache);
      })
  );
});

// Event Fetch: Mengambil dari cache jika ada (Cache First Strategy)
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Jika file ada di cache, kembalikan dari cache
        if (response) {
          return response;
        }
        // Jika tidak, ambil dari network
        return fetch(event.request);
      }
    )
  );
});

// Event Activate: Membersihkan cache lama
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
