/*
File JavaScript V7 (Lengkap)
Perubahan:
- Tombol Download sekarang memicu Modal Opsi Download.
- Fungsi baru showDownloadOptions() ditambahkan.
- Modal akan menampilkan/menyembunyikan tombol Watermark
  berdasarkan tipe file (gambar atau video).
- PERBAIKAN V7: Menghapus .onclick dari tombol download agar href bisa berjalan.
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

    let filesToUpload = []; // Array untuk antrian file {file, taken_at}


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
        filesToUpload = []; 
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

    // 2. Menambah File ke Antrian dan Menampilkan Preview
    function addFilesToQueue(newFiles) {
        for (const file of newFiles) {
            file.uniqueId = Date.now().toString() + Math.random().toString();
            const fileData = {
                file: file,
                taken_at: new Date().toISOString() 
            };
            filesToUpload.push(fileData); 
            createPreviewElement(fileData.file, file.uniqueId); 
        }
        if (filesToUpload.length > 0) {
            uploadBtn.disabled = false;
        }
    }

    // 3. Membuat Elemen Preview (Thumbnail)
    function createPreviewElement(file, uniqueId) {
        previewContainer.classList.remove('hidden');

        const fileType = file.type.split('/')[0];
        let previewElement;

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'relative w-full h-24 rounded-lg overflow-hidden shadow-md border-2 border-slate-200';
        previewWrapper.id = `preview-${uniqueId}`; 

        if (fileType === 'image') {
            previewElement = document.createElement('img');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'w-full h-full object-cover';
        } else if (fileType === 'video') {
            previewElement = document.createElement('video');
            previewElement.src = URL.createObjectURL(file);
            previewElement.className = 'w-full h-full object-cover bg-black';
            previewElement.muted = true; 
        } else {
            previewElement = document.createElement('div');
            previewElement.className = 'w-full h-full bg-slate-100 flex items-center justify-center p-2';
            previewElement.innerHTML = `<span class="text-xs text-slate-500 text-center truncate">${file.name}</span>`;
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1 right-1 w-6 h-6 bg-black bg-opacity-50 text-white rounded-full flex items-center justify-center text-sm font-bold hover:bg-red-600 transition-colors';
        removeBtn.innerHTML = '&times;';
        
        removeBtn.onclick = (e) => {
            e.stopPropagation(); 
            removeFileFromQueue(uniqueId); 
        };

        previewWrapper.appendChild(previewElement);
        previewWrapper.appendChild(removeBtn);
        previewContainer.appendChild(previewWrapper);
    }

    // 4. Menghapus File dari Antrian
    function removeFileFromQueue(uniqueId) {
        filesToUpload = filesToUpload.filter(f => f.file.uniqueId !== uniqueId);
        const previewElement = document.getElementById(`preview-${uniqueId}`);
        if (previewElement) {
            previewElement.remove();
        }
        if (filesToUpload.length === 0) {
            uploadBtn.disabled = true;
            previewContainer.classList.add('hidden');
        }
    }

    // Listener untuk input file
    if (photoInput) {
        photoInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            e.target.value = null; // Bug fix duplikat
        });
    }
    if (videoInput) {
        videoInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            e.target.value = null; // Bug fix duplikat
        });
    }
    if (galleryInput) {
        galleryInput.addEventListener('change', (e) => {
            addFilesToQueue(e.target.files);
            e.target.value = null; // Bug fix duplikat
        });
    }

    // Bug fix duplikat saat 'focus' (kembali dari kamera)
    window.addEventListener('focus', () => {
        setTimeout(() => {
            if (photoInput && photoInput.files.length > 0) {
                addFilesToQueue(photoInput.files);
                photoInput.value = null; 
            }
            if (videoInput && videoInput.files.length > 0) {
                addFilesToQueue(videoInput.files);
                videoInput.value = null;
            }
        }, 200);
    });

    // 5. Fungsi Geolocation (GPS)
    function getGeolocation() {
        return new Promise((resolve) => {
            if (!('geolocation' in navigator)) {
                console.warn('Geolocation tidak didukung oleh browser ini.');
                showStatus('Geolocation tidak didukung browser.', true);
                resolve(null); 
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve(position.coords); 
                },
                (error) => {
                    console.warn(`Gagal mendapatkan lokasi (Error ${error.code}): ${error.message}`);
                    let errorMsg = 'Gagal mengambil lokasi.';
                    if (error.code === 1) { 
                        errorMsg = 'Anda memblokir izin lokasi. Mengunggah tanpa data GPS.';
                    }
                    showStatus(errorMsg, true);
                    resolve(null); 
                },
                {
                    enableHighAccuracy: true, 
                    timeout: 10000,           
                    maximumAge: 0             
                }
            );
        });
    }


    // 6. Mengunggah SEMUA File (Satu per Satu)
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault(); 

        if (filesToUpload.length === 0) {
            showStatus("Silakan pilih file terlebih dahulu.", true);
            return;
        }

        const baseDescription = fileDescription.value.trim();
        const totalFiles = filesToUpload.length;

        // Tampilkan UI "Mengunggah..."
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `... Mengunggah ...`; // Disingkat untuk kejelasan
        if (takePhotoBtn) takePhotoBtn.disabled = true;
        if (recordVideoBtn) recordVideoBtn.disabled = true;
        if (selectGalleryBtn) selectGalleryBtn.disabled = true;
        if (progressBarContainer) {
            progressBarContainer.classList.remove('hidden');
        }

        // Ambil GPS
        showStatus('Meminta izin dan mengambil lokasi GPS...', false);
        const location = await getGeolocation(); 
        if (location) {
            showStatus('Lokasi GPS didapat! Memulai upload...', false);
        } else {
            showStatus('Gagal/ditolak. Memulai upload tanpa lokasi...', true);
        }

        let successCount = 0;
        let errorCount = 0;

        // Loop dan upload file satu per satu
        for (let i = 0; i < totalFiles; i++) {
            const fileObject = filesToUpload[i];

            if (progressText) {
                progressText.textContent = `Mengunggah ${i + 1} / ${totalFiles} (${fileObject.file.name})...`;
            }

            // Logika penomoran deskripsi
            let finalDescription = baseDescription;
            if (baseDescription !== '' && totalFiles > 1) {
                finalDescription = `${baseDescription}-${i + 1}`; 
            }

            const formData = new FormData();
            formData.append('file', fileObject.file); 
            formData.append('description', finalDescription); 
            formData.append('taken_at', fileObject.taken_at); 
            if (location) {
                formData.append('latitude', location.latitude);
                formData.append('longitude', location.longitude);
            }

            try {
                await uploadFile(formData, (progress) => {
                    if (progressFill) {
                        progressFill.style.width = `${progress * 100}%`;
                    }
                });
                successCount++;
            } catch (error) {
                errorCount++;
                console.error(`Gagal mengunggah ${fileObject.file.name}:`, error);
            }
        }

        // Selesai
        if (errorCount > 0) {
            showStatus(`Selesai: ${successCount} file berhasil, ${errorCount} file gagal.`, true);
        } else {
            showStatus(`Semua ${successCount} file berhasil diunggah!`, false);
        }
        
        resetUploadForm();
        loadFiles(); // Muat ulang galeri
    });

    // 7. Fungsi Helper untuk Upload (menggunakan Promise)
    function uploadFile(formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/media/upload', true);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    onProgress(event.loaded / event.total); 
                }
            };
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.response); 
                } else {
                    reject(xhr.response); 
                }
            };
            xhr.onerror = () => {
                reject('Network error');
            };
            xhr.send(formData);
        });
    }


    // 8. Memuat dan Menampilkan File dari Server Laravel
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

    // 9. Membuat Tampilan Kartu File (Desain Elegan)
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
        const dateToFormat = data.taken_at ? data.taken_at : data.created_at;
        const dateText = new Date(dateToFormat).toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        timestamp.textContent = `${fileTypeText} · ${dateText}`;
        infoDiv.appendChild(timestamp);

        if (data.latitude && data.longitude) {
            const location = document.createElement('p');
            location.className = 'text-xs text-blue-600 mb-4 flex items-center gap-1';
            location.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Lokasi: ${parseFloat(data.latitude).toFixed(4)}, ${parseFloat(data.longitude).toFixed(4)}`;
            const mapLink = document.createElement('a');
            mapLink.href = `https://www.google.com/maps?q=${data.latitude},${data.longitude}`;
            mapLink.target = '_blank';
            mapLink.rel = 'noopener noreferrer';
            mapLink.className = 'ml-1 text-blue-700 hover:underline';
            mapLink.innerHTML = '(Lihat Peta)';
            location.appendChild(mapLink);
            infoDiv.appendChild(location);
        }

        const buttonGroup = document.createElement('div');
        buttonGroup.className = 'flex space-x-2';

        // --- PERUBAHAN: Tombol Download memicu MODAL ---
        // 1. Ubah dari <a> menjadi <button>
        const downloadBtn = document.createElement('button');
        downloadBtn.type = 'button'; // Pastikan tipenya button
        // 2. Tambahkan event onclick untuk memanggil modal
        downloadBtn.onclick = () => showDownloadOptions(docId, data.file_type);
        // 3. Hapus href
        // --- AKHIR PERUBAHAN ---

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

    // 10. Menghapus File
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
            loadFiles(); 
        } catch (error) {
            console.error("Error deleting file:", error);
            showStatus(error.message, true);
        }
    }

    // 11. Modal Konfirmasi Hapus
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

    // --- PENAMBAHAN BARU: MODAL OPSI DOWNLOAD ---
    function showDownloadOptions(docId, fileType) {
        const modal = document.getElementById('downloadModal');
        const btnWatermark = document.getElementById('btnDownloadWatermark');
        const btnOriginal = document.getElementById('btnDownloadOriginal');
        const btnCancel = document.getElementById('cancelDownloadBtn');
        
        // Set URL untuk tombol-tombol
        btnOriginal.href = `/media/${docId}/download`;
        btnWatermark.href = `/media/${docId}/download-watermark`;

        // Logika untuk menampilkan/menyembunyikan tombol
        if (fileType.startsWith('image/')) {
            // Jika ini gambar, tampilkan tombol watermark
            btnWatermark.classList.remove('hidden');
        } else {
            // Jika bukan gambar (mis: video), sembunyikan tombol watermark
            btnWatermark.classList.add('hidden');
        }

        // Tampilkan modal
        modal.classList.remove('hidden');

        // Fungsi untuk menutup modal
        const closeModal = () => modal.classList.add('hidden');

        // Tambahkan listener ke tombol Batal
        // Kita gunakan .onclick agar listener tidak menumpuk
        btnCancel.onclick = closeModal;
        
        // --- PERBAIKAN V7 ---
        // JANGAN tambahkan .onclick ke tombol download (btnOriginal & btnWatermark)
        // Biarkan aksi default <a> (mengikuti href) berjalan.
        // Modal akan tetap terbuka, dan user bisa menutupnya dengan "Batal".
        // --- AKHIR PERBAIKAN ---
    }
    // --- AKHIR PENAMBAHAN ---


    // Muat galeri saat halaman pertama kali dibuka
    loadFiles();
});

