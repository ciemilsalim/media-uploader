<!DOCTYPE html>
<html lang="in" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Uploader</title>
    
    <!-- Memuat Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- WAJIB: CSRF Token untuk keamanan Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Memuat JavaScript APLIKASI -->
    <!-- 
    --- PERBAIKAN (CACHE BUSTING) ---
    Kita tambahkan "?v=" diikuti dengan timestamp kapan file .js terakhir diubah.
    Ini memaksa browser (terutama di HP) untuk mengunduh file .js baru
    setiap kali Anda mengeditnya di server, dan BUKAN menggunakan file lama dari cache.
    -->
    <script src="{{ asset('js/upload.js') }}?v={{ @filemtime(public_path('js/upload.js')) ?: time() }}" defer></script>

    <!-- Font (Desain Elegan) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- 
    --- PWA DINONAKTIFKAN ---
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    -->

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased">

    <!-- Container Utama -->
    <div class="container mx-auto max-w-3xl p-4 sm:p-6 lg:p-8">
        
        <!-- Header -->
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-slate-900">Media Uploader</h1>
            <p class="text-lg text-slate-600 mt-2">Upload foto dan video langsung ke server Anda.</p>
        </header>

        <!-- Pesan Status Global -->
        <div id="statusMessage" class="hidden mb-6"></div>

        <!-- Card Upload -->
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-slate-200 mb-8">
            <form id="uploadForm">
                <h2 class="text-xl font-semibold mb-5 text-slate-800">Upload File Baru</h2>
                
                <!-- Input File Tersembunyi -->
                <input type="file" id="fileInput" accept="image/*,video/*" class="hidden">

                <!-- Tombol Pilih File (Desain Elegan) -->
                <button type="button" id="selectFileBtn" class="w-full flex items-center justify-center gap-3 bg-white text-slate-700 px-4 py-3 rounded-lg font-medium border-2 border-dashed border-slate-300 hover:border-blue-500 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Pilih Foto atau Rekam Video
                </button>
                <p class="text-xs text-slate-500 mt-2 mb-4 text-center">Di HP, ini akan memunculkan pilihan kamera.</p>

                <!-- Preview -->
                <div id="previewContainer" class="hidden my-4 border border-slate-200 rounded-lg p-2 bg-slate-50">
                    <!-- Preview gambar/video akan muncul di sini -->
                </div>
                
                <!-- Deskripsi -->
                <div class="mb-4">
                    <label for="fileDescription" class="block text-sm font-medium text-slate-700 mb-2">Deskripsi (Opsional)</label>
                    <input type="text" id="fileDescription" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mis: Foto bukti di lokasi A">
                </div>

                <!-- Progress Bar Container (Desain Elegan) -->
                <div id="progressBarContainer" class="hidden my-4 w-full">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-blue-700">Mengunggah...</span>
                        <span id="progressText" class="text-sm font-medium text-blue-700">0%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5">
                        <div id="progressFill" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                    </div>
                </div>
                
                <!-- Tombol Upload (Desain Elegan) -->
                <button type="submit" id="uploadBtn" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-slate-400 disabled:cursor-not-allowed" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-4-4V7a4 4 0 014-4h10a4 4 0 014 4v5a4 4 0 01-4 4H7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4m0 0l3 3m-3-3l-3 3" />
                    </svg>
                    Upload File
                </button>
            </form>
        </div>

        <!-- Galeri File -->
        <div>
            <h2 class="text-2xl font-semibold mb-5 text-slate-800">Galeri Anda</h2>
            <div id="galleryContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Kartu file akan dimuat di sini oleh JavaScript -->
                <p class="text-slate-500 col-span-full">Memuat file...</p>
            </div>
        </div>

    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl shadow-xl max-w-sm w-full m-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-600 mb-6">Apakah Anda yakin ingin menghapus file ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">Batal</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Hapus</button>
            </div>
        </div>
    </div>

    <!-- 
    --- PWA DINONAKTIFKAN ---
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker berhasil didaftarkan: ', registration.scope);
                }).catch(error => {
                    console.log('Pendaftaran ServiceWorker gagal: ', error);
                });
            });
        }
    </script> 
    -->

</body>
</html>

