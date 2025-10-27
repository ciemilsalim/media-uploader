<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi (mass assignable).
     * Pastikan 'file_path' dan 'uploader_id' ada di sini.
     */
    protected $fillable = [
        'file_name',
        'file_path', // Nama ini harus cocok dengan migrasi dan controller
        'download_url',
        'file_type',
        'description',
        
        // --- PERBAIKAN ---
        // Mengganti 'uploaderid' menjadi 'uploader_id' agar cocok
        // dengan nama kolom di database.
        'uploader_id',
        // --- AKHIR PERBAIKAN ---
    ];
}

