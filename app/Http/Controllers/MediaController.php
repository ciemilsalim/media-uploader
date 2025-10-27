<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Menampilkan halaman upload utama.
     */
    public function index()
    {
        // Mengembalikan view 'upload.blade.php'
        return view('upload');
    }

    /**
     * Mengambil semua data media sebagai JSON.
     */
    public function getAllMedia()
    {
        // Mengambil semua media, diurutkan dari yang terbaru
        $media = Media::latest()->get();
        return response()->json($media);
    }

    /**
     * Menyimpan file baru yang di-upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            // Validasi file: wajib ada, tipe file gambar atau video, maks 25MB (25600 KB)
            'file' => 'required|mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm|max:25600',
            'description' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();

        // Buat nama file unik untuk menghindari konflik
        // Contoh: 1678886400-nama-file-asli.jpg
        $fileName = time() . '-' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        
        // Simpan file ke 'storage/app/public/uploads'
        $path = $file->storeAs('public/uploads', $fileName, 'local');

        // Buat URL publik untuk file tersebut
        // Ini akan menghasilkan /storage/uploads/nama-file.jpg
        $url = Storage::url($path);

        // Simpan metadata ke database
        $media = Media::create([
            'file_name' => $originalName,
            'file_path' => $path, // Path internal (mis: public/uploads/file.jpg)
            'download_url' => $url, // Path publik (mis: /storage/uploads/file.jpg)
            'file_type' => $fileType,
            'description' => $request->description,
            'uploader_id' => null, // Kita set null jika tidak ada login
        ]);

        return response()->json($media, 201); // 201 = Created
    }

    /**
     * Menghapus file dari storage dan database.
     */
    public function destroy(Media $media) // Menggunakan route model binding
    {
        // Hapus file dari storage
        // $media->file_path berisi 'public/uploads/namafile.jpg'
        Storage::disk('local')->delete($media->file_path);

        // Hapus data dari database
        $media->delete();

        return response()->json(['message' => 'File berhasil dihapus']);
    }

    // --- PENAMBAHAN BARU: Fungsi Download File ---
    /**
     * Memproses download file.
     */
    public function download(Media $media) // Menggunakan route model binding
    {
        // $media->file_path berisi path internal (mis: 'public/uploads/file.jpg')
        // $media->file_name berisi nama file asli (mis: 'foto-bukti.jpg')
        // Storage::download() akan mencari file berdasarkan path internal,
        // dan memaksa browser men-download-nya dengan nama file asli.
        return Storage::disk('local')->download($media->file_path, $media->file_name);
    }
    // --- AKHIR PENAMBAHAN ---
}

