<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute utama untuk menampilkan halaman upload dan galeri
Route::get('/', [MediaController::class, 'index'])->name('home');

// Rute API untuk mengambil semua media (digunakan oleh JavaScript)
Route::get('/media', [MediaController::class, 'getAllMedia']);

// Rute API untuk menyimpan file yang di-upload
Route::post('/media/upload', [MediaController::class, 'store']);

// Rute API untuk menghapus file tertentu
Route::delete('/media/{media}', [MediaController::class, 'destroy']);

// Rute untuk download file original
Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');

// --- PENAMBAHAN BARU: Rute untuk download gambar dengan watermark ---
Route::get('/media/{media}/download-watermark', [MediaController::class, 'downloadWatermark'])->name('media.download.watermark');
// --- AKHIR PENAMBAHAN ---
