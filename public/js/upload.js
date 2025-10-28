// Ini adalah JavaScript baru yang menggantikan semua kode Firebase.
// File ini berbicara ke API Laravel yang kita buat.

document.addEventListener('DOMContentLoaded', () => {

    // --- Elemen UI ---
    const fileInput = document.getElementById('fileInput');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const fileDescription = document.getElementById('fileDescription');
    const uploadBtn = document.getElementById('uploadBtn');
    const previewContainer = document.getElementById('previewContainer');
    const progressBarContainer = document.getElementById('progressBarContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const galleryContainer = document.getElementById('galleryContainer');
    const statusMessage = document.getElementById('statusMessage');
    const uploadForm = document.getElementById('uploadForm');

    // Ambil CSRF token dari meta tag di <head>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let currentFile = null;

    // --- Fungsi Bantuan ---

    // Fungsi untuk menampilkan pesan status
    function showStatus(message, isError = false) {
        statusMessage.textContent = message;
        statusMessage.className = `p-4 rounded-lg text-sm font-medium ${isError ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`;
        statusMessage.classList.remove('hidden');
        
        // Gulir ke pesan status agar terlihat
        statusMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            statusMessage.classList.add('hidden');
        }, 3000);
    }

    // Fungsi untuk mereset form upload
    function resetUploadForm() {
        fileInput.value = null;
        currentFile = null;
        fileDescription.value = '';
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
        progressBarContainer.classList.add('hidden');
        progressFill.style.width = '0%';
        progressText.textContent = '0%';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Upload File';
        selectFileBtn.disabled = false;
    }

    // --- Logika Utama ---

    // 1. Memicu Input File
    selectFileBtn.addEventListener('click', () => {
        // --- PERBAIKAN UNTUK HP ---
        // Reset file input dan currentFile SETIAP KALI tombol diklik.
        // Ini memaksa event 'change' untuk terpicu lagi
        // bahkan jika file yang sama dipilih.
        fileInput.value = null;
        currentFile = null;
        // --- AKHIR PERBAIKAN ---
        fileInput.click();
    });

    // --- FUNGSI BARU (Refaktor dari event 'change') ---
    // Fungsi ini menangani logika preview
    function handleFileSelection(file) {
        if (!file) return;

        currentFile = file;
        previewContainer.classList.remove('hidden');
        previewContainer.innerHTML = ''; // Bersihkan preview lama

        const fileType = file.type.split('/')[0];
        let previewElement;

        if (fileType === 'image') {
            previewElement = document.createElement('img');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'max-w-full h-auto rounded-lg shadow-md';
        } else if (fileType === 'video') {
            previewElement = document.createElement('video');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'max-w-full h-auto rounded-lg shadow-md';
            previewElement.controls = true;
        } else {
            previewElement = document.createElement('p');
            previewElement.textContent = `File: ${file.name} (${file.type})`;
            previewElement.className = 'text-slate-600';
        }

        previewContainer.appendChild(previewElement);
        uploadBtn.disabled = false; // Aktifkan tombol upload
    }
    // --- AKHIR FUNGSI BARU ---


    // 2. Menangani Pemilihan File & Menampilkan Preview
    fileInput.addEventListener('change', (e) => {
        // Panggil fungsi baru kita
        handleFileSelection(e.target.files[0]);
    });

    // --- PERBAIKAN UNTUK HP (Menangani 'focus' saat kembali dari kamera) ---
    window.addEventListener('focus', () => {
        // Jeda singkat untuk memberi waktu browser mengambil file
        setTimeout(() => {
            // Cek jika file input memiliki file DAN kita belum memprosesnya
            if (fileInput.files.length > 0 && !currentFile) {
                console.log('File terdeteksi dari event "focus" (kembali dari kamera).');
                handleFileSelection(fileInput.files[0]);
            }
        }, 100); // 100ms jeda
    });
    // --- AKHIR PERBAIKAN ---

    // 3. Mengunggah File ke Server Laravel (AJAX)
    uploadForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Mencegah form submit normal

        if (!currentFile) {
            showStatus("Silakan pilih file terlebih dahulu.", true);
            return;
        }

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Mengunggah...';
        selectFileBtn.disabled = true;
        progressBarContainer.classList.remove('hidden');

        // Buat FormData untuk mengirim file
        const formData = new FormData();
        formData.append('file', currentFile);
        formData.append('description', fileDescription.value);

        // Buat request AJAX (XMLHttpRequest) untuk melacak progress
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/media/upload', true);
        
        // Atur header yang diperlukan
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        // Listener progres upload
        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const progress = (event.loaded / event.total) * 100;
                progressFill.style.width = `${progress}%`;
                progressText.textContent = `${Math.round(progress)}%`;
            }
        };

        // Listener saat upload selesai
        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                showStatus("File berhasil diunggah!", false);
                resetUploadForm();
                loadFiles(); // Muat ulang galeri
            } else {
                // Tangani error
                console.error("Upload failed:", xhr.responseText);
                let errorMessage = "Upload gagal. Silakan coba lagi.";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if(response.errors && response.errors.file) {
                        errorMessage = `Upload gagal: ${response.errors.file[0]}`;
                    }
                } catch (e) {}
                showStatus(errorMessage, true);
                resetUploadForm();
            }
        };

        // Listener saat error koneksi
        xhr.onerror = () => {
            console.error("Upload failed (network error).");
            showStatus("Upload gagal. Periksa koneksi Anda.", true);
            resetUploadForm();
        };

        // Kirim request
        xhr.send(formData);
    });

    // 4. Memuat dan Menampilkan File dari Server Laravel
    async function loadFiles() {
        try {
            const response = await fetch('/media');
            if (!response.ok) {
                throw new Error('Gagal memuat data galeri.');
            }
            const files = await response.json();

            galleryContainer.innerHTML = ''; // Bersihkan galeri
            if (files.length === 0) {
                galleryContainer.innerHTML = '<p class="text-slate-500 col-span-full">Belum ada file yang diunggah.</p>';
                return;
            }

            files.forEach(fileData => {
                const fileCard = createFileCard(fileData.id, fileData);
                galleryContainer.appendChild(fileCard);
            });

        } catch (error) {
            console.error("Error loading files:", error);
            galleryContainer.innerHTML = '<p class="text-red-500 col-span-full">Gagal memuat data galeri. Coba refresh halaman.</p>';
            showStatus(error.message, true);
        }
    }

    // 5. Membuat Tampilan Kartu File (Desain Baru)
    function createFileCard(docId, data) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl border border-slate-200';

        let mediaElement;
        const fileType = data.file_type.split('/')[0];
        
        if (fileType === 'image') {
            mediaElement = document.createElement('img');
            mediaElement.src = data.download_url;
            mediaElement.alt = data.description || 'Uploaded image';
            mediaElement.className = 'w-full h-48 object-cover bg-slate-100';
            
            mediaElement.onerror = () => {
                console.error(`GAGAL MEMUAT GAMBAR di URL: ${mediaElement.src}`);
                mediaElement.src = `https://placehold.co/600x400/eee/ccc?text=Image+Error`;
            };

        } else if (fileType === 'video') {
            mediaElement = document.createElement('video');
            mediaElement.src = data.download_url;
            mediaElement.controls = true;
            mediaElement.className = 'w-full h-48 bg-black';
        } else {
            mediaElement = document.createElement('div');
            mediaElement.className = 'w-full h-48 bg-slate-200 flex items-center justify-center';
            mediaElement.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`;
        }
        card.appendChild(mediaElement);

        const infoDiv = document.createElement('div');
        infoDiv.className = 'p-4';
        
        const desc = document.createElement('p');
        desc.className = 'text-slate-700 font-medium mb-2 truncate';
        desc.textContent = data.description || 'Tidak ada deskripsi';
        infoDiv.appendChild(desc);
        
        const fileName = document.createElement('p');
        fileName.className = 'text-sm text-slate-500 truncate mb-2';
        fileName.textContent = data.file_name;
        infoDiv.appendChild(fileName);

        const timestamp = document.createElement('p');
        timestamp.className = 'text-xs text-slate-400 mb-4';
        timestamp.textContent = new Date(data.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        infoDiv.appendChild(timestamp);

        // --- PERUBAHAN DESAIN (Tombol) ---
        const buttonGroup = document.createElement('div');
        buttonGroup.className = 'flex space-x-2';

        // Tombol Download
        const downloadBtn = document.createElement('a');
        downloadBtn.href = `/media/download/${docId}`;
        downloadBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>`;
        downloadBtn.className = 'flex items-center justify-center w-full px-3 py-2 text-sm rounded-lg transition-colors bg-slate-600 text-white hover:bg-slate-700';
        downloadBtn.setAttribute('title', 'Download file');
        
        // Tombol Hapus
        const deleteBtn = document.createElement('button');
        deleteBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>`;
        deleteBtn.className = 'flex items-center justify-center px-3 py-2 text-sm rounded-lg transition-colors bg-red-600 text-white hover:bg-red-700';
        deleteBtn.setAttribute('title', 'Hapus file');
        
        deleteBtn.onclick = () => showDeleteConfirmation(docId);
        
        buttonGroup.appendChild(downloadBtn);
        buttonGroup.appendChild(deleteBtn);
        
        infoDiv.appendChild(buttonGroup);
        // --- AKHIR PERUBAHAN ---

        card.appendChild(infoDiv);
        return card;
    }

    // 6. Menghapus File (dari Storage dan Database via Laravel)
    async function deleteFile(docId) {
        console.log(`Deleting doc: ${docId}`);
        document.getElementById('deleteModal').classList.add('hidden');

        try {
            const response = await fetch(`/media/${docId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Gagal menghapus file.');
            }
            
            showStatus("File berhasil dihapus.", false);
            loadFiles(); // Muat ulang galeri
            
        } catch (error) {
            console.error("Error deleting file:", error);
            showStatus(error.message, true);
        }
    }

    // --- Modal Konfirmasi Hapus ---
    function showDeleteConfirmation(docId) {
        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        
        modal.classList.remove('hidden');

        // Hapus listener lama agar tidak menumpuk
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // --- PERBAIKAN ---
        // Menghapus 'D' yang salah ketik
        newConfirmBtn.onclick = () => deleteFile(docId);
        // --- AKHIR PERBAIKAN ---
        cancelBtn.onclick = () => modal.classList.add('hidden');
    }

    // Muat galeri saat halaman pertama kali dibuka
    loadFiles();
});

