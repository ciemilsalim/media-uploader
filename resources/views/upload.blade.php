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

    <!-- --- PWA --- -->
    <meta name="theme-color" content="#4338ca">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <!-- --- AKHIR PWA --- -->

    <!-- Memuat JavaScript baru kita -->
    <script src="{{ asset('js/upload.js') }}" defer></script>
    
    <!-- Menambahkan font Inter untuk tampilan yang lebih bersih -->
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased">

    <!-- Container Utama -->
    <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
        
        <!-- Header -->
        <header class="mb-8 pb-4 border-b border-slate-200">
            <h1 class="text-3xl font-bold text-slate-900">Media Uploader</h1>
            <p class="text-slate-600">Upload foto dan video langsung ke server Anda.</p>
        </header>

        <!-- Pesan Status Global -->
        <div id="statusMessage" class="hidden mb-6"></div>

        <!-- Form Upload -->
        <form id="uploadForm" class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-slate-200 mb-10">
            <h2 class="text-xl font-semibold mb-5 text-slate-800">Upload File Baru</h2>
            
            <!-- Input File Tersembunyi -->
            <input type="file" id="fileInput" accept="image/*,video/*" class="hidden">

            <!-- Tombol Pilih File (Desain Baru dengan Ikon) -->
            <button type="button" id="selectFileBtn" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-indigo-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-opacity-75 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3 3m3-3l3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
                <span>Pilih Foto atau Rekam Video</span>
            </button>
            <p class="text-xs text-slate-500 mt-2 mb-4">Di HP, ini akan memunculkan pilihan kamera, perekam, atau galeri.</p>

            <!-- Preview -->
            <div id="previewContainer" class="hidden my-4 border border-slate-200 rounded-lg p-2 bg-slate-50">
                <!-- Preview gambar/video akan muncul di sini -->
            </div>
            
            <!-- Deskripsi -->
            <div class="mb-5">
                <label for="fileDescription" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi (Opsional)</label>
                <input type="text" id="fileDescription" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Mis: Foto bukti di lokasi A">
            </div>

            <!-- Progress Bar -->
            <div id="progressBarContainer" class="hidden my-4">
                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                    <div id="progressFill" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                </div>
                <div id="progressText" class="text-center text-sm text-slate-600 mt-2">0%</div>
            </div>
            
            <!-- Tombol Upload -->
            <button type="submit" id="uploadBtn" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-indigo-700 transition-all duration-200 disabled:bg-slate-400 disabled:cursor-not-allowed" disabled>
                Upload File
            </button>
        </form>

        <!-- Galeri File -->
        <div>
            <h2 class="text-2xl font-semibold mb-5 text-slate-800">Galeri Anda</h2>
            <div id="galleryContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Kartu file akan dimuat di sini oleh JavaScript -->
                <p class="text-slate-500 col-span-full">Memuat file...</p>
            </div>
        </div>

    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full border border-slate-200">
            <h3 class="text-lg font-medium text-slate-900 mb-4">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-600 mb-6">Apakah Anda yakin ingin menghapus file ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 transition-colors">Batal</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Hapus</button>
            </div>
        </div>
    </div>

    <!-- --- PENAMBAHAN UNTUK PWA --- -->
    <script>
        // Mendaftarkan Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker berhasil didaftarkan:', registration);
                    })
                    .catch((error) => {
                        console.log('Pendaftaran Service Worker gagal:', error);
                    });
            });
        }
    </script>
    <!-- --- AKHIR PENAMBAHAN PWA --- -->

</body>
</html>

