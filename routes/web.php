<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sinilah Anda dapat mendaftarkan rute web untuk aplikasi Anda.
|
*/

// Rute untuk menampilkan halaman utama (Frontend)
// URL: http://media-uploader.test/
Route::get('/', [MediaController::class, 'index']);

// --- Rute API untuk JavaScript ---

// 1. (API) Mengambil semua data media
// URL: http://media-uploader.test/media
Route::get('/media', [MediaController::class, 'getAllMedia'])->name('media.get');

// 2. (API) Menyimpan file baru
// URL: http://media-uploader.test/media/upload
Route::post('/media/upload', [MediaController::class, 'store'])->name('media.store');

// 3. (API) Menghapus file
// URL: http://media-uploader.test/media/123
// {media} adalah model-route-binding, Laravel akan otomatis mencari Media berdasarkan ID.
Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

// --- PENAMBAHAN BARU: Rute Download File ---
// URL: http://media-uploader.test/media/123/download
Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');
// --- AKHIR PENAMBAHAN ---

