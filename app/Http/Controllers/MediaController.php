<?php

namespace App\Http\Controllers; // --- INI PERBAIKANNYA ---
use Illuminate\Routing\Controller; // --- PERBAIKAN KRUSIAL: Baris ini ditambahkan ---
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// --- PERBAIKAN V3 ---
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // 1. TAMBAHKAN DRIVER
use Carbon\Carbon;
use Intervention\Image\Interfaces\ImageInterface; // 2. (OPSIONAL TAPI BAGUS) Tambahkan interface untuk type-hinting

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
        // (Sekarang kita urutkan berdasarkan 'taken_at', baru 'created_at')
        $media = Media::latest('taken_at')->latest('created_at')->get();
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
            // Validasi data baru
            'taken_at' => 'nullable|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();

        // Buat nama file unik
        $fileName = time() . '-' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        
        // Simpan file ke 'storage/app/public/uploads'
        // Kita gunakan disk('public')
        $path = $file->storeAs('uploads', $fileName, 'public');

        // Buat URL relatif
        // Symlink 'public/storage' Anda akan menanganinya.
        $url = '/storage/' . $path; // $path sudah berisi 'uploads/namafile.jpg'

        // Simpan metadata ke database
        $media = Media::create([
            'file_name' => $originalName,
            'file_path' => 'public/' . $path, // Simpan path internal (mis: public/uploads/file.jpg)
            'download_url' => $url, // Simpan URL relatif (mis: /storage/uploads/file.jpg)
            'file_type' => $fileType,
            'description' => $request->description,
            'uploader_id' => null, 
            // Simpan data baru
            'taken_at' => $request->taken_at,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json($media, 201); // 201 = Created
    }

    /**
     * Menghapus file dari storage dan database.
     */
    public function destroy(Media $media)
    {
        // $media->file_path berisi 'public/uploads/namafile.jpg'
        // Kita gunakan Str::after untuk mendapatkan path 'uploads/namafile.jpg'
        Storage::disk('public')->delete(Str::after($media->file_path, 'public/'));

        // Hapus data dari database
        $media->delete();

        return response()->json(['message' => 'File berhasil dihapus']);
    }

    /**
     * Memproses download file original.
     */
    public function download(Media $media)
    {
        // Gunakan path yang sama untuk download
        return Storage::disk('public')->download(Str::after($media->file_path, 'public/'), $media->file_name);
    }

    /**
     * Download gambar dengan watermark informasi.
     */
    public function downloadWatermark(Media $media)
    {
        // Pastikan ini adalah file gambar
        if (!Str::startsWith($media->file_type, 'image/')) {
            return redirect()->back()->withErrors('Hanya file gambar yang bisa didownload dengan watermark.');
        }

        // --- PERBAIKAN PATH ---
        // Ambil path lengkap file dari storage
        $originalFilePath = Storage::disk('public')->path(Str::after($media->file_path, 'public/'));
        // --- AKHIR PERBAIKAN ---

        try {
            // --- PERBAIKAN V3: SINTAKS CONSTRUCTOR & READ ---
            // 2. Gunakan driver baru saat membuat manager
            $manager = new ImageManager(new Driver());
            
            // 3. Gunakan 'read()' (V3) bukan 'make()' (V2)
            $img = $manager->read($originalFilePath);
            // --- AKHIR PERBAIKAN ---


            // --- KODE WATERMARK SEKARANG DIAKTIFKAN ---
            
            // Siapkan informasi teks
            $description = $media->description ?? 'Tanpa Deskripsi';
            
            // Ini sekarang aman karena $casts di Media.php
            $takenAt = $media->taken_at 
                            ? $media->taken_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') 
                            : 'Waktu tidak tersedia';

            $location = "Lokasi tidak tersedia";
            if ($media->latitude && $media->longitude) {
                // Format koordinat
                $lat = number_format($media->latitude, 5);
                $lon = number_format($media->longitude, 5);
                $location = "{$lat}, {$lon}";
            }

            // Gabungkan teks
            $watermarkText = "Deskripsi: {$description}\n";
            $watermarkText .= "Waktu: {$takenAt}\n";
            $watermarkText .= "GPS: {$location}";

            // --- PERBAIKAN UTAMA: MENAMBAHKAN FONT ---
            // Tentukan Path Font Anda
            // PENTING: Anda HARUS meletakkan file .ttf di folder ini
            $fontPath = public_path('fonts/Inter_28pt-Regular.ttf');

            if (!file_exists($fontPath)) {
                // Jika font tidak ada, lempar error yang jelas
                throw new \Exception('File font tidak ditemukan di ' . $fontPath);
            }
            
            // --- PERBAIKAN: UKURAN WATERMARK DINAMIS ---
            // Hitung ukuran font & padding berdasarkan lebar gambar
            $imageWidth = $img->width();
            
            // 1. Ukuran Font Dinamis: (mis: 1/45 dari lebar, dengan min 16px)
            $fontSize = max(16, (int) round($imageWidth / 45));
            
            // 2. Padding Dinamis: (mis: 75% dari ukuran font, dengan min 10px)
            $padding = max(10, (int) round($fontSize * 0.75));
            
            // 3. Tinggi Box Dinamis: (cukup untuk 3 baris teks + padding atas/bawah)
            // Perkiraan tinggi per baris = 1.25 * $fontSize. Total 3.75 * $fontSize.
            $textBlockHeight = (int) round($fontSize * 3.75);
            $boxHeight = $textBlockHeight + ($padding * 2);
            // --- AKHIR PERBAIKAN ---

            // --- PERBAIKAN SINTAKS V3 (drawRectangle) ---
            // (Upgrade Desain) Tambahkan kotak latar belakang semi-transparan
            // Gunakan $boxHeight dinamis yang baru dihitung
            $img->drawRectangle(0, $img->height() - $boxHeight, function ($rectangle) use ($img, $boxHeight) {
                $rectangle->width($img->width());
                $rectangle->height($boxHeight); // Gunakan $boxHeight dinamis
                $rectangle->background('rgba(0, 0, 0, 0.6)');
            });
            // --- AKHIR PERBAIKAN SINTAKS ---

            // Tentukan posisi teks (pojok kiri bawah, dengan padding)
            // Gunakan $padding dan $fontSize dinamis
            $img->text($watermarkText, $padding, $img->height() - $padding, function($font) use ($fontPath, $fontSize) {
                $font->file($fontPath); // PENTING: Aktifkan baris ini
                $font->size($fontSize); // Gunakan $fontSize dinamis
                $font->color('#ffffff'); // Warna putih
                $font->align('left');
                $font->valign('bottom');
            });
            // --- AKHIR PERBAIKAN UTAMA ---
            
            // --- AKHIR DARI KODE WATERMARK ---


            // --- PERBAIKAN: Undefined property $img->extension ---
            // Ambil ekstensi dari nama file asli, bukan dari objek $img
            $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
            if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                $extension = 'jpg'; // Default ke jpg jika ekstensi tidak valid/kosong
            }

            // Siapkan nama file download baru
            $newFileName = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '-watermarked.' . $extension;

            // --- PERBAIKAN SINTAKS v3: Menggunakan helper Response() Laravel ---
            // 1. Encode gambar terlebih dahulu
            $encoded = $img->encodeByExtension($extension, quality: 90);
            
            // 2. Ambil data mentah (raw) sebagai string
            $imageData = $encoded->toString();
            
            // 3. Ambil mime type
            $mime = $encoded->mimetype();

            // 4. Buat response manual menggunakan helper Laravel
            return response($imageData, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'attachment; filename="'.$newFileName.'"',
            ]);
            // --- AKHIR PERBAIKAN ---

        } catch (\Throwable $e) { // --- PERBAIKAN: Menangkap \Throwable (lebih luas dari \Exception)
            // Log error seperti biasa
            \Log::error("Error generating watermark for media ID {$media->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString() // Log trace lengkap untuk info lebih
            ]);
            
            // --- PERBAIKAN: KEMBALIKAN KE MODE NORMAL ---
            // Kembalikan ke redirect. Jika Anda masih mendapat .htm,
            // kita tahu error masih terjadi.
            return redirect()->back()->withErrors('Gagal membuat watermark pada gambar. Error: ' . $e->getMessage());
            // --- AKHIR PERBAIKAN ---
        }
    }
}

