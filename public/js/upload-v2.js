/*
File JavaScript V3 (di-upgrade dari V2) untuk Media Uploader
Perubahan:
- Logika Multi-Upload: Menangani antrian file (array) bukan satu file.
- Multi-Preview: Menampilkan thumbnail untuk setiap file dalam antrian.
- Tombol Hapus per-File: Setiap thumbnail memiliki tombol 'x' untuk menghapusnya dari antrian.
- Upload Berurutan: Mengunggah file satu per satu menggunakan deskripsi yang sama.
*/
document.addEventListener('DOMContentLoaded', () => {

    // --- Elemen UI ---
    const photoInput = document.getElementById('photoInput');
    const videoInput = document.getElementById('videoInput');
    const galleryInput = document.getElementById('galleryInput');
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    const recordVideoBtn = document.getElementById('recordVideoBtn');
    const selectGalleryBtn = document.getElementById('selectGalleryBtn');
    const fileDescription = document.getElementById('fileDescription');
    const uploadBtn = document.getElementById('uploadBtn');
    const previewContainer = document.getElementById('previewContainer');
    const progressBarContainer = document.getElementById('progressBarContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const galleryContainer = document.getElementById('galleryContainer');
    const statusMessage = document.getElementById('statusMessage');
    const uploadForm = document.getElementById('uploadForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- PERUBAHAN BESAR ---
    // Mengganti 'currentFile' (satu objek) menjadi 'filesToUpload' (sebuah array)
    let filesToUpload = [];
    // --- AKHIR PERUBAHAN ---


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
        if (photoInput) photoInput.value = null;
        if (videoInput) videoInput.value = null;
        if (galleryInput) galleryInput.value = null;

        // --- PERUBAHAN ---
        filesToUpload = []; // Kosongkan array antrian
        // --- AKHIR PERUBAHAN ---

        fileDescription.value = '';
        previewContainer.innerHTML = ''; // Kosongkan preview
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
        
        if (takePhotoBtn) takePhotoBtn.disabled = false;
        if (recordVideoBtn) recordVideoBtn.disabled = false;
        if (selectGalleryBtn) selectGalleryBtn.disabled = false;
    }

    // --- Logika Inti ---

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

    // --- PERUBAHAN BESAR ---
    // 2. Menambah File ke Antrian dan Menampilkan Preview
    function addFilesToQueue(newFiles) {
        // 'newFiles' adalah objek FileList, kita ubah jadi Array
        for (const file of newFiles) {
            // Beri ID unik untuk keperluan menghapus
            file.uniqueId = Date.now().toString() + Math.random().toString();
            
            // Tambahkan file ke array antrian kita
            filesToUpload.push(file);
            
            // Buat elemen preview untuk file ini
            createPreviewElement(file);
        }

        // Jika ada file dalam antrian, aktifkan tombol upload
        if (filesToUpload.length > 0) {
            uploadBtn.disabled = false;
        }
    }

    // 3. Membuat Elemen Preview (Thumbnail)
    function createPreviewElement(file) {
        previewContainer.classList.remove('hidden');

        const fileType = file.type.split('/')[0];
        let previewElement;

        // Buat wrapper untuk thumbnail
        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'relative w-full h-24 rounded-lg overflow-hidden shadow-md border-2 border-slate-200';
        // Simpan ID unik di elemen DOM untuk referensi
        previewWrapper.id = `preview-${file.uniqueId}`;

        if (fileType === 'image') {
            previewElement = document.createElement('img');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'w-full h-full object-cover';
        } else if (fileType === 'video') {
            previewElement = document.createElement('video');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'w-full h-full object-cover bg-black';
            previewElement.muted = true; // Video tidak bersuara di preview
        } else {
            previewElement = document.createElement('div');
            previewElement.className = 'w-full h-full bg-slate-100 flex items-center justify-center p-2';
            previewElement.innerHTML = `<span class="text-xs text-slate-500 text-center truncate">${file.name}</span>`;
        }

        // Buat tombol Hapus (X)
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1 right-1 w-6 h-6 bg-black bg-opacity-50 text-white rounded-full flex items-center justify-center text-sm font-bold hover:bg-red-600 transition-colors';
        removeBtn.innerHTML = '&times;';
        
        // Tambahkan event listener ke tombol Hapus
        removeBtn.onclick = (e) => {
            e.stopPropagation(); // Hentikan event agar tidak memicu hal lain
            removeFileFromQueue(file.uniqueId);
        };

        // Masukkan elemen-elemen ke wrapper
        previewWrapper.appendChild(previewElement);
        previewWrapper.appendChild(removeBtn);

        // Masukkan wrapper ke kontainer preview
        previewContainer.appendChild(previewWrapper);
    }

    // 4. Menghapus File dari Antrian
    function removeFileFromQueue(uniqueId) {
        // Hapus file dari array
        filesToUpload = filesToUpload.filter(f => f.uniqueId !== uniqueId);

        // Hapus elemen preview dari DOM
        const previewElement = document.getElementById(`preview-${uniqueId}`);
        if (previewElement) {
            previewElement.remove();
        }

        // Jika tidak ada file lagi, nonaktifkan tombol upload
        if (filesToUpload.length === 0) {
            uploadBtn.disabled = true;
            previewContainer.classList.add('hidden');
        }
    }

    // Panggil addFilesToQueue saat SALAH SATU input berubah
    if (photoInput) {
        photoInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            // --- PERBAIKAN BUG DUPLIKAT ---
            // Bersihkan input segera agar event 'focus' tidak memicunya lagi
            e.target.value = null;
            // --- AKHIR PERBAIKAN ---
        });
    }
    if (videoInput) {
        // Input video (capture) biasanya hanya 1 file, tapi kita tetap perlakukan sebagai antrian
        videoInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            // --- PERBAIKAN BUG DUPLIKAT ---
            e.target.value = null;
            // --- AKHIR PERBAIKAN ---
        });
    }
    if (galleryInput) {
        galleryInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            // --- PERBAIKAN BUG DUPLIKAT ---
            e.target.value = null;
            // --- AKHIR PERBAIKAN ---
        });
    }
    // --- AKHIR PERUBAHAN BESAR ---


    // --- PERBAIKAN PREVIEW KAMERA HP ---
    // (Disederhanakan karena addFilesToQueue sudah menangani semuanya)
    window.addEventListener('focus', () => {
        setTimeout(() => {
            // Cukup periksa apakah ada file di input yang belum masuk antrian
            // (Logika ini mungkin perlu disempurnakan, tapi kita coba dulu)
            if (photoInput && photoInput.files.length > 0) {
                addFilesToQueue(photoInput.files);
                photoInput.value = null; // Kosongkan setelah diambil
            }
            if (videoInput && videoInput.files.length > 0) {
                addFilesToQueue(videoInput.files);
                videoInput.value = null;
            }
        }, 200); // Beri jeda lebih lama
    });

// ... (Sisa file tidak berubah) ...

    // --- PERUBAHAN BESAR ---
    // 5. Mengunggah SEMUA File (Satu per Satu)
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault(); 

        if (filesToUpload.length === 0) {
            showStatus("Silakan pilih file terlebih dahulu.", true);
            return;
        }

        // --- PERBAIKAN: Penomoran Deskripsi ---
        // Ambil deskripsi DASAR, dan trim spasi
        const baseDescription = fileDescription.value.trim();
        // --- AKHIR PERBAIKAN ---
        const totalFiles = filesToUpload.length;

        // Tampilkan UI "Mengunggah..."
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Mengunggah...`;
        
        if (takePhotoBtn) takePhotoBtn.disabled = true;
        if (recordVideoBtn) recordVideoBtn.disabled = true;
        if (selectGalleryBtn) selectGalleryBtn.disabled = true;
        
        if (progressBarContainer) {
            progressBarContainer.classList.remove('hidden');
        }

        let successCount = 0;
        let errorCount = 0;

        // Loop dan upload file satu per satu
        for (let i = 0; i < totalFiles; i++) {
            const file = filesToUpload[i];

            // Update progress bar
            if (progressText) {
                progressText.textContent = `Mengunggah ${i + 1} / ${totalFiles} (${file.name})...`;
            }

            // --- PERBAIKAN: Penomoran Deskripsi Otomatis ---
            let finalDescription = baseDescription;
            // Hanya tambahkan nomor jika deskripsi diisi DAN total file lebih dari 1
            if (baseDescription !== '' && totalFiles > 1) {
                finalDescription = `${baseDescription}-${i + 1}`; // Mis: "Kegiatan-1"
            }
            // --- AKHIR PERBAIKAN ---

            const formData = new FormData();
            formData.append('file', file);
            formData.append('description', finalDescription); // Gunakan deskripsi yang sudah dinomori

            try {
                // Gunakan fungsi uploadFile (didefinisikan di bawah) yang mengembalikan Promise
                // Ini akan meng-handle progress bar per file
                await uploadFile(formData, (progress) => {
                    if (progressFill) {
                        progressFill.style.width = `${progress * 100}%`;
                    }
                });
                successCount++;
            } catch (error) {
                errorCount++;
                console.error(`Gagal mengunggah ${file.name}:`, error);
                // Jangan hentikan loop, lanjut ke file berikutnya
            }
        }

        // Selesai
        if (errorCount > 0) {
            showStatus(`Selesai: ${successCount} file berhasil, ${errorCount} file gagal.`, true);
        } else {
            showStatus(`Semua ${successCount} file berhasil diunggah!`, false);
        }
        
        resetUploadForm();
        loadFiles(); // Muat ulang galeri setelah semua selesai
    });

    // 6. Fungsi Helper untuk Upload (menggunakan Promise)
    function uploadFile(formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/media/upload', true);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');

            // Listener progres upload per file
            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    onProgress(event.loaded / event.total); // Kirim progres (0.0 sampai 1.0)
                }
            };

            // Listener saat upload selesai
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.response); // Sukses
                } else {
                    reject(xhr.response); // Gagal (mis: error validasi)
                }
            };

            // Listener saat error koneksi
            xhr.onerror = () => {
                reject('Network error');
            };

            xhr.send(formData);
        });
    }
    // --- AKHIR PERUBAHAN BESAR ---


    // 4. Memuat dan Menampilkan File dari Server Laravel
    async function loadFiles() {
// ... (Sisa file sama seperti V2) ...
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

        // --- PERBAIKAN TATA LETAK TOMBOL ---
        // Grup Tombol (Download & Hapus)
        const buttonGroup = document.createElement('div');
        // Menambahkan 'flex space-x-2' agar tombol sejajar dan ada jarak
        buttonGroup.className = 'flex space-x-2';
        // --- AKHIR PERBAIKAN ---

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

