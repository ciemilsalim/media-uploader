<!DOCTYPE html>
<html lang="in">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Uploader (Laravel Lokal)</title>
    
    <!-- Memuat Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- WAJIB: CSRF Token untuk keamanan Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- --- PENAMBAHAN UNTUK PWA --- -->
    
    <!-- Meta tag untuk warna tema di browser seluler -->
    <meta name="theme-color" content="#4f46e5">

    <!-- Link ke Web App Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Ikon untuk Apple (iOS) -->
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    
    <!-- --- AKHIR PENAMBAHAN PWA --- -->


    <!-- Memuat JavaScript baru kita -->
    <script src="{{ asset('js/upload.js') }}" defer></script>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <!-- Container Utama -->
    <div class="container mx-auto max-w-3xl p-4 sm:p-6 lg:p-8">
        
        <!-- Header -->
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Media Uploader (Lokal)</h1>
            <p class="text-gray-600">Upload ke server Laravel lokal Anda.</p>
        </header>

        <!-- Pesan Status Global -->
        <div id="statusMessage" class="hidden mb-4"></div>

        <!-- Form Upload -->
        <form id="uploadForm" class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold mb-4">Upload File Baru</h2>
            
            <!-- Input File Tersembunyi -->
            <input type="file" id="fileInput" accept="image/*,video/*" class="hidden">

            <!-- Tombol Pilih File -->
            <button type="button" id="selectFileBtn" class="w-full bg-blue-500 text-white px-4 py-3 rounded-md font-medium hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">
                Pilih Foto atau Rekam Video
            </button>
            <p class="text-xs text-gray-500 mt-1 mb-4">Di HP, ini akan memunculkan pilihan kamera, perekam, atau galeri.</p>

            <!-- Preview -->
            <div id="previewContainer" class="hidden my-4 border border-gray-200 rounded-md p-2">
                <!-- Preview gambar/video akan muncul di sini -->
            </div>
            
            <!-- Deskripsi -->
            <div class="mb-4">
                <label for="fileDescription" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi (Opsional)</label>
                <input type="text" id="fileDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Mis: Foto bukti di lokasi A">
            </div>

            <!-- Progress Bar -->
            <div id="progressBar" class="hidden my-4 w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="progressFill" class="bg-blue-600 h-4 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
            </div>
            <div id="progressText" class="text-center text-sm text-gray-600 mb-4">0%</div>
            
            <!-- Tombol Upload -->
            <button type="submit" id="uploadBtn" class="w-full bg-green-500 text-white px-4 py-3 rounded-md font-medium hover:bg-green-600 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                Upload File
            </button>
        </form>

        <!-- Galeri File -->
        <div>
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Galeri Cloud Anda (Lokal)</h2>
            <div id="galleryContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Kartu file akan dimuat di sini oleh JavaScript -->
                <p class="text-gray-500">Memuat file...</p>
            </div>
        </div>

    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus file ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Batal</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Hapus</button>
            </div>
        </div>
    </div>

    <!-- --- PENAMBAHAN UNTUK PWA --- -->
    <script>
        // Mendaftarkan Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then((registration) => {
                        console.log('Service Worker berhasil didaftarkan:', registration);
                    })
                    .catch((error) => {
                        console.log('Pendaftaran Service Worker gagal:', error);
                    });
            });
        }
    </GTC-JavaScript-End-GTC>
    <!-- --- AKHIR PENAMBAHAN PWA --- -->

</body>
</html>

