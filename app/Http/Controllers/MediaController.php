<?php

namespace App\Http\Controllers;
use Illuminate\Routing\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Carbon\Carbon;
use Intervention\Image\Interfaces\ImageInterface;
use Illuminate\Support\Facades\Http; // --- BARU: Tambahkan HTTP Client ---

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
        // --- PERBAIKAN: Mengganti operator '/' dengan '.' ---
        $url = '/storage/' . $path; // $path sudah berisi 'uploads/namafile.jpg'
        // --- AKHIR PERBAIKAN ---

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
        $originalFilePath = Storage::disk('public')->path(Str::after($media->file_path, 'public/'));
        // --- AKHIR PERBAIKAN ---

        try {
            // --- PERBAIKAN V3: SINTAKS CONSTRUCTOR & READ ---
            $manager = new ImageManager(new Driver());
            $img = $manager->read($originalFilePath);
            // --- AKHIR PERBAIKAN ---


            // --- KODE WATERMARK SEKARANG DIAKTIFKAN ---
            
            // Siapkan informasi teks
            $description = $media->description ?? 'Tanpa Deskripsi';
            
            $takenAt = $media->taken_at 
                            ? $media->taken_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') 
                            : 'Waktu tidak tersedia';

            // --- BARU: REVERSE GEOCODING ---
            $locationLines = []; // Array untuk semua baris lokasi
            if ($media->latitude && $media->longitude) {
                try {
                    $apiKey = config('app.locationiq_api_key'); // Ambil dari config/app.php
                    if (empty($apiKey)) {
                        throw new \Exception('LOCATIONIQ_API_KEY tidak diatur di .env atau config/app.php');
                    }

                    $url = "https://us1.locationiq.com/v1/reverse.php";
                    // Panggil API
                    $response = Http::timeout(5)->get($url, [ // Timeout 5 detik
                        'key' => $apiKey,
                        'lat' => $media->latitude,
                        'lon' => $media->longitude,
                        'format' => 'json',
                        'accept-language' => 'id', // Minta data dalam Bahasa Indonesia
                        'normalizecity' => 1,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        
                        // Selalu tampilkan GPS
                        $locationLines[] = "GPS: {$media->latitude}, {$media->longitude}";

                        // --- PERUBAHAN: Ambil Alamat Lengkap (Display Name) ---
                        // Ambil 'display_name' yang sudah diformat oleh API
                        $fullAddress = $data['display_name'] ?? null;

                        if ($fullAddress) {
                            // --- PERUBAHAN: Tampilkan alamat lengkap ---
                            // Kita tidak lagi menghapus kode pos atau "Indonesia"
                            $locationLines[] = $fullAddress;
                            // --- AKHIR PERUBAHAN ---

                        } else {
                            // Fallback jika 'display_name' tidak ada (seharusnya tidak terjadi)
                            $locationLines[] = "(Alamat tidak ditemukan)";
                        }
                        // --- AKHIR PERUBAHAN ---
                        
                    } else {
                        // Gagal panggil API, fallback ke GPS saja
                        $locationLines[] = "GPS: {$media->latitude}, {$media->longitude}";
                        $locationLines[] = "(Gagal mengambil data alamat)";
                    }
                } catch (\Exception $geoEx) {
                    // Gagal total (misal key tidak ada / timeout), fallback ke GPS saja
                    \Log::error('LocationIQ API Error: '. $geoEx->getMessage());
                    $locationLines[] = "GPS: {$media->latitude}, {$media->longitude}";
                    $locationLines[] = "(Gagal mengambil data alamat)";
                }
            } else {
                $locationLines[] = "Lokasi tidak tersedia";
            }
            // --- AKHIR REVERSE GEOCODING ---

            // --- PERUBAHAN: Gabungkan teks + credit ---
            $watermarkText = "Deskripsi: {$description}\n";
            $watermarkText .= "Waktu: {$takenAt}\n";
            $watermarkText .= implode("\n", $locationLines); // Gabungkan semua baris lokasi
            $watermarkText .= "\n"; // Tambah 1 spasi (baris kosong)
            $watermarkText .= "createdBy Uploader-Zahradev"; // Tambah baris credit
            // --- AKHIR PERUBAHAN ---

            // --- PERBAIKAN: BLOK DUPLIKAT DIHAPUS ---
            // Blok kode lama (dari $takenAt sampai $watermarkText .= "GPS: {$location}";)
            // telah dihapus dari sini untuk mencegah penimpaan $watermarkText.
            // --- AKHIR PERBAIKAN ---

            // --- PERBAIKAN UTAMA: MENAMBAHKAN FONT ---
            // Tentukan Path Font Anda
            // PENTING: Anda HARUS meletakkan file .ttf di folder ini
            $fontPath = public_path('fonts/Inter_28pt-Regular.ttf');

            if (!file_exists($fontPath)) {
                // Jika font tidak ada, lempar error yang jelas
                throw new \Exception('File font tidak ditemukan di ' . $fontPath);
            }
            
            // --- PERBAIKAN: UKURAN WATERMARK DINAMIS ---
            $imageWidth = $img->width();
            
            // 1. Ukuran Font Dinamis: (mis: 1/45 dari lebar, dengan min 16px)
            $fontSize = max(16, (int) round($imageWidth / 45));
            
            // 2. Padding Dinamis: (mis: 75% dari ukuran font, dengan min 10px)
            $padding = max(10, (int) round($fontSize * 0.75));
            
            // --- PERUBAHAN: HITUNG TINGGI BOX BARU ---
            // 2 baris (Deskripsi, Waktu) + jumlah baris lokasi + 1 baris kosong + 1 baris credit
            $lineCount = 2 + count($locationLines) + 2; 
            // Perkiraan tinggi per baris = 1.25 * $fontSize.
            $textBlockHeight = (int) round($fontSize * 1.25 * $lineCount);
            $boxHeight = $textBlockHeight + ($padding * 2);
            // --- AKHIR PERUBAHAN ---

            // --- PERBAIKAN SINTAKS V3 (drawRectangle) ---
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
            return redirect()->back()->withErrors('Gagal membuat watermark pada gambar. Error: ' . $e->getMessage());
            // --- AKHIR PERBAIKAN ---
        }
    }
}

