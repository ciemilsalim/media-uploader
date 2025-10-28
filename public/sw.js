// --- PERBAIKAN ---
// Naikkan versi cache untuk memaksa update
const CACHE_NAME = 'media-uploader-v1.2';
// --- AKHIR PERBAIKAN ---

// File-file lokal yang akan kita cache
const urlsToCache = [
    '/',
    '/js/upload.js',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/maskable-icon-512x512.png'
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

// --- PERBAIKAN: Ubah Strategi Fetch ---
self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);

    // 1. Tangani request ke domain lain (mis: CDN)
    if (requestUrl.origin !== self.location.origin) {
        event.respondWith(fetch(event.request)); // Ambil langsung dari network
        return;
    }

    // 2. Tangani request PENTING (HTML & JS) -> STRATEGI: NETWORK FIRST
    // Kita ingin ini selalu baru jika online (Network First)
    if (requestUrl.pathname === '/' || requestUrl.pathname.startsWith('/js/')) {
        event.respondWith(
            fetch(event.request)
                .then(networkResponse => {
                    // Jika berhasil, simpan ke cache dan kembalikan
                    return caches.open(CACHE_NAME).then(cache => {
                        // Perbarui cache dengan versi baru
                        cache.put(event.request, networkResponse.clone());
                        console.log('Cache diperbarui untuk:', requestUrl.pathname);
                        return networkResponse;
                    });
                })
                .catch(() => {
                    // Jika network gagal (offline), ambil dari cache
                    console.log('Network gagal, mengambil dari cache:', requestUrl.pathname);
                    return caches.match(event.request);
                })
        );
        return;
    }

    // 3. Tangani aset statis (manifest, ikon) -> STRATEGI: CACHE FIRST
    // Ini bisa "Cache First" karena jarang berubah
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Kembalikan dari cache jika ada
                if (response) {
                    return response;
                }
                // Jika tidak, ambil dari network dan simpan ke cache
                return fetch(event.request).then(
                    fetchResponse => {
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
// --- AKHIR PERBAIKAN ---

// Membersihkan cache lama
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME]; // Gunakan nama cache yang baru
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        // Hapus semua cache yang BUKAN v1.2
                        console.log('Menghapus cache lama:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

