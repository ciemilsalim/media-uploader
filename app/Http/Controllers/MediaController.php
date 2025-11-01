<?php

namespace App\Http\Controllers;
use Illuminate\Routing\Controller;
use App\Models\Media;
use Illuminate\Http\Request; // <-- PENTING: Pastikan ini ada
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Carbon\Carbon; // <-- PENTING: Pastikan ini ada
use Intervention\Image\Interfaces\ImageInterface;
use Illuminate\Support\Facades\Http; 

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
    public function getAllMedia(Request $request) // <-- Tambahkan Request
    {
        // --- LOGIKA PENCARIAN & LIMIT BARU ---
        
        // 1. Ambil semua parameter input
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 2. Mulai query
        $query = Media::query();

        // 3. Filter berdasarkan teks (jika ada)
        $query->when($search, function($q) use ($search) {
            $q->where(function($subq) use ($search) {
                $subq->where('description', 'like', "%{$search}%")
                     ->orWhere('file_name', 'like', "%{$search}%");
            });
        });

        // 4. Filter berdasarkan tanggal
        // Menggunakan 'whereDate' untuk membandingkan tanggal saja (mengabaikan waktu)
        
        // Jika hanya tanggal mulai yang diisi
        $query->when($startDate && !$endDate, function($q) use ($startDate) {
            $q->whereDate('taken_at', '>=', Carbon::parse($startDate));
        });

        // Jika hanya tanggal akhir yang diisi
        $query->when(!$startDate && $endDate, function($q) use ($endDate) {
            $q->whereDate('taken_at', '<=', Carbon::parse($endDate));
        });

        // Jika kedua tanggal diisi (rentang)
        $query->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
            $q->whereDate('taken_at', '>=', Carbon::parse($startDate))
              ->whereDate('taken_at', '<=', Carbon::parse($endDate));
        });

        // 5. Logika Limit
        // HANYA limit ke 3 jika TIDAK ADA parameter pencarian sama sekali
        if (!$search && !$startDate && !$endDate) {
            $query->limit(3);
        }

        // 6. Selalu urutkan dari yang terbaru dan ambil datanya
        $media = $query->latest('taken_at')->latest('created_at')->get();
        
        // --- AKHIR LOGIKA BARU ---
        
        return response()->json($media);
    }

    /**
     * Menyimpan file baru yang di-upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm|max:25600', // 25MB Max
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


            // Siapkan data teks
            $imageWidth = $img->width();
            
            // Siapkan informasi teks
            $description = $media->description ?? 'Tanpa Deskripsi';
            
            // (Perbaikan $casts di Media.php akan menangani ini)
            $takenAt = $media->taken_at 
                            ? $media->taken_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') 
                            : 'Waktu tidak tersedia';

            // --- BARU: REVERSE GEOCODING ---
            $locationLines = []; // Simpan semua baris lokasi di sini
            if ($media->latitude && $media->longitude) {
                try {
                    $apiKey = config('app.locationiq_api_key'); // Ambil dari config/app.php
                    if (!$apiKey) {
                        throw new \Exception('LOCATIONIQ_API_KEY tidak diatur di .env atau config/app.php');
                    }

                    $url = "https://us1.locationiq.com/v1/reverse.php";
                    // Panggil API menggunakan Guzzle (via helper Http Laravel)
                    $response = Http::timeout(5)->get($url, [ // Timeout 5 detik
                        'key' => $apiKey,
                        'lat' => $media->latitude,
                        'lon' => $media->longitude,
                        'format' => 'json',
                        'accept-language' => 'id', // Minta hasil dalam Bahasa Indonesia
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
                    \Log::error("Reverse Geocoding Gagal: " . $geoEx->getMessage());
                    $locationLines[] = "GPS: {$media->latitude}, {$media->longitude}";
                    $locationLines[] = "(Gagal mengambil data alamat)";
                }
            } else {
                $locationLines[] = "Lokasi tidak tersedia";
            }
            // --- AKHIR REVERSE GEOCODING ---

            // --- PERUBAHAN: Gabungkan teks + credit ---
            $allTextLines = [];
            $allTextLines[] = "Deskripsi: {$description}";
            $allTextLines[] = "Waktu: {$takenAt}";
            $allTextLines = array_merge($allTextLines, $locationLines); // Tambahkan semua baris lokasi
            $allTextLines[] = ""; // Baris spasi kosong
            $allTextLines[] = "createdBy Uploader-Zahradev"; // Tambah baris credit
            
            $watermarkText = implode("\n", $allTextLines);
            $lineCount = count($allTextLines); // Hitung jumlah baris total
            // --- AKHIR PERUBAHAN ---

            // --- PERBAIKAN: BLOK DUPLIKAT DIHAPUS ---
            // (Tidak ada kode duplikat di sini)
            // --- AKHIR PERBAIKAN ---

            // --- PERBAIKAN UTAMA: MENAMBAHKAN FONT ---
            // Tentukan path ke file font Anda
            $fontPath = public_path('fonts/Inter_28pt-Regular.ttf');

            if (!file_exists($fontPath)) {
                // Fallback atau error jika font tidak ditemukan
                throw new \Exception('File font tidak ditemukan di ' . $fontPath);
            }
            
            // --- PERBAIKAN: UKURAN WATERMARK DINAMIS ---
            
            // 1. Ukuran Font Dinamis: (mis: 1/45 dari lebar, dengan min 16px)
            $fontSize = max(16, (int) round($imageWidth / 45));
            
            // 2. Padding Dinamis: (mis: 75% dari ukuran font, dengan min 10px)
            $padding = max(10, (int) round($fontSize * 0.75));
            
            // --- PERUBAHAN: HITUNG TINGGI BOX BARU ---
            // 2 baris (Deskripsi, Waktu) + jumlah baris lokasi + 1 baris kosong + 1 baris credit
            // Perkiraan tinggi per baris = 1.25 * $fontSize.
            $textBlockHeight = (int) round($fontSize * 1.25 * $lineCount);
            $boxHeight = $textBlockHeight + ($padding * 2); // Tinggi total = teks + padding atas & bawah
            // --- AKHIR PERUBAHAN ---

            // --- PERBAIKAN SINTAKS V3 (drawRectangle) ---
            $img->drawRectangle(0, $img->height() - $boxHeight, function ($rectangle) use ($img, $boxHeight) {
                $rectangle->width($img->width());
                $rectangle->height($boxHeight); // Gunakan $boxHeight dinamis
                $rectangle->background('rgba(0, 0, 0, 0.6)');
            });
            // --- AKHIR PERBAIKAN SINTAKS ---

            // Tentukan posisi teks (pojok kiri bawah, dengan padding)
            $img->text($watermarkText, $padding, $img->height() - $padding, function($font) use ($fontPath, $fontSize) {
                $font->file($fontPath); // PENTING: Aktifkan baris ini
                $font->size($fontSize); // Ukuran font dinamis
                $font->color('#ffffff'); // Warna putih
                $font->align('left');
                $font->valign('bottom');
            });
            // --- AKHIR PERBAIKAN UTAMA ---
            
            // --- PERBAIKAN: Mendapatkan ekstensi file ---

            // --- PERBAIKAN: Undefined property $img->extension ---
            $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
            $extension = strtolower($extension); // Pastikan lowercase
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg'; // Default ke jpg jika ekstensi tidak valid/kosong
            }

            // Siapkan nama file download baru
            $newFileName = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '-watermarked.' . $extension;

            // --- PERBAIKAN SINTAKS v3: Menggunakan helper Response() Laravel ---
            // 1. Encode gambar terlebih dahulu
            $encoded = $img->encodeByExtension($extension, quality: 90);
            
            // 2. Ambil data mentah (raw) sebagai string
            $imageData = $encoded->toString();
            
            // 3. Ambil mimetype
            $mime = $encoded->mimetype();

            // 4. Buat response manual menggunakan helper Laravel
            return response($imageData)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="'.$newFileName.'"')
                ->header('Content-Length', strlen($imageData));
            // --- AKHIR PERBAIKAN ---

        } catch (\Throwable $e) { // --- PERBAIKAN: Menangkap \Throwable (lebih luas dari \Exception)
            // Jika ada error, catat dan redirect kembali
            \Log::error("Error generating watermark for media ID {$media->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString() // Log trace lengkap untuk info lebih
            ]);
            
            // --- PERBAIKAN: KEMBALIKAN KE MODE NORMAL ---
            return redirect()->back()->withErrors('Gagal membuat watermark pada gambar. Error: ' . $e->getMessage());
            // --- AKHIR PERBAIKAN ---
        }
    }
}

