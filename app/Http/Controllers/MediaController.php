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
        return view('upload');
    }

    /**
     * Mengambil semua data media sebagai JSON.
     */
    public function getAllMedia()
    {
        $media = Media::latest()->get();
        return response()->json($media);
    }

    /**
     * Menyimpan file baru yang di-upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm|max:25600',
            'description' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();

        // Buat nama file unik
        $fileName = time() . '-' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        
        // --- PERBAIKAN ---
        // Menggunakan disk 'public' dan folder 'uploads'.
        // storeAs() di disk 'public' akan otomatis menyimpan ke 'storage/app/public/uploads'
        // $path akan berisi 'uploads/nama-file.jpg' (ini yang benar)
        $path = $file->storeAs('uploads', $fileName, 'public');

        // Menggunakan Storage::url() dari disk 'public' untuk URL yang benar
        // Ini akan menghasilkan URL relatif /storage/uploads/nama-file.jpg
        $url = Storage::disk('public')->url($path);
        // --- AKHIR PERBAIKAN ---

        // Simpan metadata ke database
        $media = Media::create([
            'file_name' => $originalName,
            'file_path' => $path, // Menyimpan 'uploads/nama-file.jpg'
            'download_url' => $url, // Menyimpan '/storage/uploads/nama-file.jpg'
            'file_type' => $fileType,
            'description' => $request->description,
            'uploader_id' => null, 
        ]);

        return response()->json($media, 201);
    }

    /**
     * Menghapus file dari storage dan database.
     */
    public function destroy(Media $media) 
    {
        // --- PERBAIKAN ---
        // Gunakan disk 'public' untuk menghapus file
        // $media->file_path berisi 'uploads/namafile.jpg'
        Storage::disk('public')->delete($media->file_path);
        // --- AKHIR PERBAIKAN ---

        $media->delete();

        return response()->json(['message' => 'File berhasil dihapus']);
    }

    /**
     * Memproses download file.
     */
    public function download(Media $media)
    {
        // --- PERBAIKAN ---
        // Gunakan disk 'public' untuk men-download file
        return Storage::disk('public')->download($media->file_path, $media->file_name);
        // --- AKHIR PERBAIKAN ---
    }
}
