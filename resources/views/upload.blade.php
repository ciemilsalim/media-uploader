<!DOCTYPE html>
<html lang="in" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Uploader - Zahradev</title>
    
    <!-- Memuat Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- WAJIB: CSRF Token untuk keamanan Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Memuat JavaScript APLIKASI -->
    <script src="{{ asset('js/upload-v2.js') }}?v={{ @filemtime(public_path('js/upload-v2.js')) ?: time() }}" defer></script>

    <!-- Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Memperbaiki tampilan input tanggal di beberapa browser */
        input[type="date"]::-webkit-calendar-picker-indicator {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24"><path fill="%236B7280" d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/></svg>');
            cursor: pointer;
        }
    </style>
</head>
<!-- Latar belakang abu (slate) dan layout flex untuk sticky footer -->
<body class="bg-slate-100 font-sans antialiased flex flex-col min-h-screen">

    <!-- === HEADER === -->
    <header class="bg-white shadow-md w-full sticky top-0 z-40">
        <nav class="container mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo / Judul Situs -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    <!-- Pastikan file ZahraDev.png ada di folder public/images/ -->
                    <img src="{{ asset('images/ZahraDev.png') }}" alt="ZahraDev Logo" class="h-10 w-auto">
                </div>
                
                <!-- === PERUBAHAN: Tombol Cara Penggunaan (Hilangkan 'hidden') === -->
                <!-- Menghapus 'hidden' dan 'md:block' agar terlihat di semua ukuran layar -->
                <div class="block">
                    <button type="button" id="howToUseBtn" class="flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:underline transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                        <span class="hidden sm:inline">Cara Penggunaan</span> <!-- Teks disembunyikan di layar < sm -->
                    </button>
                </div>
                <!-- === AKHIR PERUBAHAN === -->

            </div>
        </nav>
    </header>
    <!-- === AKHIR HEADER === -->

    <!-- <main> dengan flex-grow untuk mendorong footer ke bawah -->
    <main class="w-full flex-grow">
        <!-- Container Utama -->
        <div class="container mx-auto max-w-3xl p-4 sm:p-6 lg:p-8">
            
            <!-- Header Halaman -->
            <header class="mb-8 text-center">
                <h1 class="text-4xl font-bold text-slate-900">Upload & Kelola File</h1>
                <p class="text-lg text-slate-600 mt-2">Upload foto dan video langsung ke server Anda.</p>
            </header>

            <!-- Pesan Status Global -->
            <div id="statusMessage" class="hidden mb-6"></div>

            <!-- Card Upload -->
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-slate-200 mb-8">
                <form id="uploadForm">
                    <h2 class="text-xl font-semibold mb-5 text-slate-800">Upload File Baru</h2>
                    
                    <!-- Input Terpisah -->
                    <input type="file" id="photoInput" accept="image/*" capture="camera" class="hidden" multiple>
                    <input type="file" id="videoInput" accept="video/*" capture="camcorder" class="hidden">
                    <input type="file" id="galleryInput" accept="image/*,video/*" class="hidden" multiple>

                    <!-- Grup Tombol Aksi -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <!-- Tombol Ambil Foto -->
                        <button type="button" id="takePhotoBtn" class="w-full flex flex-col sm:flex-row items-center justify-center gap-2 bg-white text-slate-700 px-3 py-3 rounded-lg font-medium border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm sm:text-base">Ambil Foto</span>
                        </button>
                        
                        <!-- Tombol Rekam Video -->
                        <button type="button" id="recordVideoBtn" class="w-full flex flex-col sm:flex-row items-center justify-center gap-2 bg-white text-slate-700 px-3 py-3 rounded-lg font-medium border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm sm:text-base">Rekam Video</span>
                        </button>

                        <!-- Tombol Pilih Galeri -->
                        <button type="button" id="selectGalleryBtn" class="w-full flex flex-col sm:flex-row items-center justify-center gap-2 bg-white text-slate-700 px-3 py-3 rounded-lg font-medium border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="text-sm sm:text-base">Pilih Galeri</span>
                        </button>
                    </div>


                    <!-- Preview (Sekarang Grid) -->
                    <div id="previewContainer" class="hidden my-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                        <!-- Preview gambar/video akan muncul di sini -->
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="mb-4">
                        <label for="fileDescription" class="block text-sm font-medium text-slate-700 mb-2">Deskripsi (Opsional)</label>
                        <input type="text" id="fileDescription" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Mis: Foto bukti di lokasi A">
                    </div>

                    <!-- Progress Bar Container -->
                    <div id="progressBarContainer" class="hidden my-4 w-full">
                        <div class="flex justify-between mb-1">
                            <span id="progressText" class="text-sm font-medium text-emerald-700">0%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2.5">
                            <div id="progressFill" class="bg-emerald-600 h-2.5 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Tombol Upload -->
                    <button type="submit" id="uploadBtn" class="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-colors disabled:bg-slate-400 disabled:cursor-not-allowed" disabled>
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
                <div class="mb-5">
                    <h2 class="text-2xl font-semibold text-slate-800 mb-4">Galeri Anda</h2>
                    
                    <!-- Form Pencarian dengan Tanggal -->
                    <form id="searchForm" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Pencarian Teks -->
                        <div class="md:col-span-1">
                            <label for="searchInput" class="text-sm font-medium text-slate-700 block mb-1">Cari Teks</label>
                            <input type="search" id="searchInput" placeholder="Deskripsi/Nama File..." class="w-full px-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <!-- Tanggal Mulai -->
                        <div>
                            <label for="startDate" class="text-sm font-medium text-slate-700 block mb-1">Dari Tanggal</label>
                            <input type="date" id="startDate" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-700">
                        </div>
                        <!-- Tanggal Akhir -->
                        <div>
                            <label for="endDate" class="text-sm font-medium text-slate-700 block mb-1">Sampai Tanggal</label>
                            <input type="date" id="endDate" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-700">
                        </div>
                    </form>
                </div>
                
                <div id="galleryContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Kartu file akan dimuat di sini oleh JavaScript -->
                    <p class="text-slate-500 col-span-full">Memuat file...</p>
                </div>
            </div>

        </div>
    </main>
    <!-- === AKHIR MAIN === -->

    <!-- === FOOTER === -->
    <footer class="w-full bg-slate-800 mt-16 py-8">
        <div class="container mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm text-slate-400">&copy; {{ date('Y') }} Uploader-Zahradev. All rights reserved.</p>
        </div>
    </footer>
    <!-- === AKHIR FOOTER === -->


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

    <!-- Modal Opsi Download -->
    <div id="downloadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl shadow-xl max-w-sm w-full m-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Pilih Opsi Download</h3>
            <p class="text-sm text-slate-600 mb-6">Pilih versi file yang ingin Anda unduh.</p>
            
            <div class="flex flex-col space-y-3">
                <a id="btnDownloadWatermark" href="#" download class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-lg transition-colors bg-emerald-600 text-white hover:bg-emerald-700 font-medium">
                    Download (dengan Watermark)
                </a>
                <a id="btnDownloadOriginal" href="#" download class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-lg transition-colors bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium">
                    Download (Original)
                </a>
            </div>

            <div class="mt-6 text-center">
                <button type="button" id="cancelDownloadBtn" class="px-4 py-2 text-sm text-slate-600 hover:underline font-medium">Batal</button>
            </div>
        </div>
    </div>

    <!-- === MODAL CARA PENGGUNAAN BARU === -->
    <div id="howToUseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h3 class="text-xl font-semibold text-slate-900">Cara Penggunaan Aplikasi</h3>
                <!-- Tombol Tutup Ikon -->
                <button type="button" id="closeHowToUseBtn_Icon" class="text-slate-400 hover:text-slate-600 text-3xl">&times;</button>
            </div>
            
            <!-- Konten Modal (Scrollable) -->
            <div class="text-sm text-slate-600 space-y-4 overflow-y-auto pr-2">
                <div>
                    <h4 class="font-semibold text-slate-800 mb-1">1. Mengunggah File</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Gunakan tombol <strong>Ambil Foto</strong> atau <strong>Rekam Video</strong> untuk mengakses kamera HP Anda secara langsung.</li>
                        <li>Gunakan <strong>Pilih Galeri</strong> untuk memilih satu atau beberapa file dari galeri Anda.</li>
                        <li>(Opsional) Isi <strong>Deskripsi</strong> untuk file yang akan diunggah. Jika mengunggah banyak file, deskripsi akan diberi nomor (cth: "Bukti-1", "Bukti-2").</li>
                        <li>Tekan tombol <strong>Upload File</strong>. Aplikasi akan otomatis mengambil lokasi GPS Anda dan mengunggah semua file satu per satu.</li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-slate-800 mb-1">2. Mencari Galeri</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Saat halaman dimuat, hanya 3 file terbaru yang akan ditampilkan.</li>
                        <li>Gunakan <strong>Form Pencarian</strong> untuk memfilter galeri.</li>
                        <li><strong>Cari Teks:</strong> Mencari berdasarkan deskripsi atau nama file.</li>
                        <li><strong>Dari/Sampai Tanggal:</strong> Memfilter berdasarkan rentang tanggal pengambilan file (bukan tanggal upload).</li>
                        <li>Mengisi salah satu form akan otomatis menampilkan semua hasil yang cocok (tidak terbatas 3 file).</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-slate-800 mb-1">3. Download File (Watermark)</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Tekan tombol <strong>Download</strong> pada kartu file.</li>
                        <li>Pilih <strong>Download (dengan Watermark)</strong> untuk mengunduh gambar.</li>
                        <li>Watermark akan otomatis ditambahkan, berisi: Deskripsi, Waktu Pengambilan, Alamat Lengkap (via GPS), dan kredit "createdBy Uploader-Zahradev".</li>
                        <li>Ukuran watermark akan menyesuaikan secara dinamis dengan ukuran gambar.</li>
                        <li>Fitur watermark hanya tersedia untuk file <strong>gambar</strong>. File video hanya bisa di-download original.</li>
                    </ul>
                </div>
            </div>

            <!-- Tombol Tutup Bawah -->
            <div class="mt-6 text-right flex-shrink-0">
                <button type="button" id="closeHowToUseBtn" class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium text-sm">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
    <!-- === AKHIR MODAL BARU === -->


    <!-- Script inline HANYA untuk modal "Cara Penggunaan" -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const howToUseModal = document.getElementById('howToUseModal');
            const openBtn = document.getElementById('howToUseBtn');
            const closeBtn = document.getElementById('closeHowToUseBtn');
            const closeIconBtn = document.getElementById('closeHowToUseBtn_Icon');

            const openModal = () => {
                if(howToUseModal) howToUseModal.classList.remove('hidden');
            }
            const closeModal = () => {
                if(howToUseModal) howToUseModal.classList.add('hidden');
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (closeIconBtn) closeIconBtn.addEventListener('click', closeModal);
        });
    </script>

</body>
</html>

