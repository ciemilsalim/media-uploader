/*
File JavaScript V2 untuk Media Uploader
Perubahan:
- Menangani 3 tombol input terpisah (Ambil Foto, Rekam Video, Pilih Galeri)
*/
document.addEventListener('DOMContentLoaded', () => {

    // --- Elemen UI ---
    
    // --- PERUBAHAN ---
    // Input tersembunyi
    const photoInput = document.getElementById('photoInput');
    const videoInput = document.getElementById('videoInput');
    const galleryInput = document.getElementById('galleryInput');

    // Tombol Pilihan
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    const recordVideoBtn = document.getElementById('recordVideoBtn');
    const selectGalleryBtn = document.getElementById('selectGalleryBtn');
    // --- AKHIR PERUBAHAN ---

    const fileDescription = document.getElementById('fileDescription');
    const uploadBtn = document.getElementById('uploadBtn');
    const previewContainer = document.getElementById('previewContainer');
    
    // Progress Bar
    const progressBarContainer = document.getElementById('progressBarContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    const galleryContainer = document.getElementById('galleryContainer');
    const statusMessage = document.getElementById('statusMessage');
    const uploadForm = document.getElementById('uploadForm');

    // Ambil CSRF token dari meta tag di <head>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Variabel untuk menyimpan file yang dipilih
    let currentFile = null;

    // --- Fungsi Bantuan ---

    function showStatus(message, isError = false) {
        if (!statusMessage) return;
        statusMessage.textContent = message;
        statusMessage.className = `p-4 rounded-lg text-sm mb-6 ${isError ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`;
        statusMessage.classList.remove('hidden');
        
        setTimeout(() => {
            statusMessage.classList.add('hidden');
        }, 3000);
    }

    // Mereset form upload ke keadaan awal
    function resetUploadForm() {
        // --- PERUBAHAN ---
        // Reset semua input file
        if (photoInput) photoInput.value = null;
        if (videoInput) videoInput.value = null;
        if (galleryInput) galleryInput.value = null;
        // --- AKHIR PERUBAHAN ---

        currentFile = null;
        fileDescription.value = '';
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
        
        if (progressBarContainer) {
            progressBarContainer.classList.add('hidden');
        }
        if (progressFill) {
            progressFill.style.width = '0%';
        }
        if (progressText) {
            progressText.textContent = '0%';
        }
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-4-4V7a4 4 0 014-4h10a4 4 0 014 4v5a4 4 0 01-4 4H7z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4m0 0l3 3m-3-3l-3 3" />
            </svg>
            Upload File`;
        
        // --- PERUBAHAN ---
        // Aktifkan kembali semua tombol pilihan
        if (takePhotoBtn) takePhotoBtn.disabled = false;
        if (recordVideoBtn) recordVideoBtn.disabled = false;
        if (selectGalleryBtn) selectGalleryBtn.disabled = false;
        // --- AKHIR PERUBAHAN ---
    }

    // --- Logika Inti ---

    // --- PERUBAHAN ---
    // 1. Memicu Input File yang Sesuai
    if (takePhotoBtn) {
        takePhotoBtn.addEventListener('click', () => {
            photoInput.value = null; 
            photoInput.click();
        });
    }

    if (recordVideoBtn) {
        recordVideoBtn.addEventListener('click', () => {
            videoInput.value = null; 
            videoInput.click();
        });
    }

    if (selectGalleryBtn) {
        selectGalleryBtn.addEventListener('click', () => {
            galleryInput.value = null; 
            galleryInput.click();
        });
    }
    // --- AKHIR PERUBAHAN ---


    // 2. Menangani Pemilihan File & Menampilkan Preview
    function handleFileSelection(file) {
        if (!file) return;

        currentFile = file; // Simpan file di variabel global
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
            previewElement.className = 'max-w-full h-auto rounded-lg shadow-md bg-black';
            previewElement.controls = true;
        } else {
            previewElement = document.createElement('p');
            previewElement.textContent = `File: ${file.name} (${file.type})`;
            previewElement.className = 'text-slate-600 p-4 bg-slate-100 rounded-lg';
        }

        previewContainer.appendChild(previewElement);
        uploadBtn.disabled = false; // Aktifkan tombol upload
    }

    // --- PERUBAHAN ---
    // Panggil handleFileSelection saat SALAH SATU input berubah
    if (photoInput) {
        photoInput.addEventListener('change', (e) => handleFileSelection(e.target.files[0]));
    }
    if (videoInput) {
        videoInput.addEventListener('change', (e) => handleFileSelection(e.target.files[0]));
    }
    if (galleryInput) {
        galleryInput.addEventListener('change', (e) => handleFileSelection(e.target.files[0]));
    }
    // --- AKHIR PERUBAHAN ---


    // --- PERBAIKAN PREVIEW KAMERA HP ---
    // Ini sekarang harus memantau semua input
    window.addEventListener('focus', () => {
        setTimeout(() => {
            let activeInput = null;
            if (photoInput && photoInput.files.length > 0) activeInput = photoInput;
            else if (videoInput && videoInput.files.length > 0) activeInput = videoInput;
            else if (galleryInput && galleryInput.files.length > 0) activeInput = galleryInput;

            if (activeInput && !currentFile) {
                console.log('Fokus terdeteksi, memicu preview HP...');
                handleFileSelection(activeInput.files[0]);
            }
        }, 100); 
    });

    // 3. Mengunggah File ke Server Laravel (AJAX)
    uploadForm.addEventListener('submit', (e) => {
        e.preventDefault(); 

        if (!currentFile) {
            showStatus("Silakan pilih file terlebih dahulu.", true);
            return;
        }

        // Tampilkan UI "Mengunggah..."
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Mengunggah...`;
        
        // --- PERUBAHAN ---
        // Nonaktifkan semua tombol pilihan
        if (takePhotoBtn) takePhotoBtn.disabled = true;
        if (recordVideoBtn) recordVideoBtn.disabled = true;
        if (selectGalleryBtn) selectGalleryBtn.disabled = true;
        // --- AKHIR PERUBAHAN ---
        
        if (progressBarContainer) {
            progressBarContainer.classList.remove('hidden');
        }

        const formData = new FormData();
        formData.append('file', currentFile);
        formData.append('description', fileDescription.value);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/media/upload', true);
        
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const progress = (event.loaded / event.total) * 100;
                if (progressFill) {
                    progressFill.style.width = `${progress}%`;
                }
                if (progressText) {
                    progressText.textContent = `${Math.round(progress)}%`;
                }
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                showStatus("File berhasil diunggah!", false);
                resetUploadForm();
                loadFiles(); 
            } else {
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

        xhr.onerror = () => {
            console.error("Upload failed (network error).");
            showStatus("Upload gagal. Periksa koneksi Anda.", true);
            resetUploadForm();
        };

        xhr.send(formData);
    });

    // 4. Memuat dan Menampilkan File dari Server Laravel
    async function loadFiles() {
        if (!galleryContainer) return;

        galleryContainer.innerHTML = '<p class="text-slate-500 col-span-full">Memuat file...</p>';
        
        try {
            const response = await fetch('/media');
            if (!response.ok) {
                throw new Error('Gagal memuat data galeri.');
            }
            const files = await response.json();

            galleryContainer.innerHTML = ''; 
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
        }
    }

    // 5. Membuat Tampilan Kartu File (Desain Elegan)
    function createFileCard(docId, data) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1';

        let mediaElement;
        const fileType = data.file_type.split('/')[0];
        
        if (fileType === 'image') {
            mediaElement = document.createElement('img');
            mediaElement.src = data.download_url;
            mediaElement.alt = data.description || 'Uploaded image';
            mediaElement.className = 'w-full h-48 object-cover';
            
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
            mediaElement.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`;
        }
        card.appendChild(mediaElement);

        const infoDiv = document.createElement('div');
        infoDiv.className = 'p-4';
        
        const desc = document.createElement('p');
        desc.className = 'text-slate-700 font-medium mb-2 truncate';
        desc.textContent = data.description || data.file_name;
        infoDiv.appendChild(desc);
        
        const timestamp = document.createElement('p');
        timestamp.className = 'text-xs text-slate-500 mb-4';
        const fileTypeText = data.file_type.split('/')[1] ? data.file_type.split('/')[1].toUpperCase() : 'FILE';
        const dateText = new Date(data.created_at).toLocaleDateString('id-ID');
        timestamp.textContent = `${fileTypeText} · ${dateText}`;
        infoDiv.appendChild(timestamp);
        // Grup Tombol (Download & Hapus)
        const buttonGroup = document.createElement('div');
        // Tombol Download
        const downloadBtn = document.createElement('a'); // 'a' agar bisa di-download
        
        // --- PERBAIKAN BUG 404 (URL SALAH) ---
        // URL yang benar adalah /media/{id}/download
        // Bukan /media/download/{id}
        downloadBtn.href = `/media/${docId}/download`; // Link ke rute download
        // --- AKHIR PERBAIKAN ---

        downloadBtn.className = 'flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium';
        downloadBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> Download`;
        buttonGroup.appendChild(downloadBtn);
        
        const deleteBtn = document.createElement('button');
        deleteBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>`;
        deleteBtn.className = 'flex items-center justify-center px-3 py-2 text-sm rounded-lg transition-colors bg-red-100 text-red-700 hover:bg-red-200';
        deleteBtn.setAttribute('aria-label', 'Hapus file');
        
        deleteBtn.onclick = () => showDeleteConfirmation(docId);
        
        buttonGroup.appendChild(deleteBtn);
        infoDiv.appendChild(buttonGroup);
        card.appendChild(infoDiv);
        return card;
    }

    // 6. Menghapus File
    async function deleteFile(docId) {
        console.log(`Menghapus file ID: ${docId}`);
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

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.onclick = () => deleteFile(docId);
        
        cancelBtn.onclick = () => modal.classList.add('hidden');
    }

    // Muat galeri saat halaman pertama kali dibuka
    loadFiles();
});

