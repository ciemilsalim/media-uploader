<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'file_path',
        'download_url',
        'file_type',
        'description',
        'uploader_id',
        'taken_at',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * (INI ADALAH PERBAIKAN PENTING KEDUA)
     * Ini akan secara otomatis mengubah 'taken_at' dari string di database
     * menjadi objek Carbon di PHP, sehingga ->locale('id') berfungsi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taken_at' => 'datetime',
        // --- PENAMBAHAN YANG DISARANKAN ---
        'latitude' => 'float',
        'longitude' => 'float',
        // --- AKHIR PENAMBAHAN ---
    ];
    // --- AKHIR PENAMBAHAN ---
}

