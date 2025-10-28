const CACHE_NAME = 'media-uploader-v1.1';
// File-file lokal yang akan kita cache
const urlsToCache = [
    '/',
    '/js/upload.js',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/maskable-icon-512x512.png'
    // Kita TIDAK menyertakan CDN tailwind di sini
];

self.addEventListener('install', event => {
    // Proses instalasi: buka cache dan tambahkan file-file di atas
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache dibuka');
                return cache.addAll(urlsToCache);
            })
            .catch(err => {
                console.error('Gagal menambahkan file ke cache:', err);
            })
    );
});

self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);

    // --- PERBAIKAN CORS ---
    // Jika permintaan adalah ke domain lain (bukan domain kita)
    // seperti cdn.tailwindcss.com, jangan coba cache.
    // Biarkan browser mengambilnya langsung dari internet.
    if (requestUrl.origin !== self.location.origin) {
        // Jangan tangani permintaan ini, biarkan browser
        event.respondWith(fetch(event.request));
        return;
    }
    // --- AKHIR PERBAIKAN ---

    // Untuk file-file di domain kita, gunakan strategi "cache first"
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Jika file ada di cache, kembalikan dari cache
                if (response) {
                    return response;
                }
                
                // Jika tidak ada di cache, ambil dari network
                return fetch(event.request).then(
                    fetchResponse => {
                        // Jika berhasil, kloning respons dan simpan ke cache
                        if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                            return fetchResponse;
                        }

                        const responseToCache = fetchResponse.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return fetchResponse;
                    }
                );
            })
    );
});

// Membersihkan cache lama
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
