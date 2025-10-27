<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            
            // --- PERBAIKAN ---
            // Mengganti nama kolom dari 'storage_path' menjadi 'file_path'
            // agar konsisten dengan Model dan Controller.
            $table->string('file_path'); // Path internal (mis: public/uploads/file.jpg)
            // --- AKHIR PERBAIKAN ---

            $table->string('download_url'); // Path publik (mis: /storage/uploads/file.jpg)
            $table->string('file_name'); // Nama file asli
            $table->string('file_type'); // Tipe MIME (mis: image/png)
            $table->text('description')->nullable(); // Deskripsi dari user
            
            // Kolom ini ada di migrasi awal Anda, jadi kita pertahankan
            $table->string('uploader_id')->nullable(); 
            
            $table->timestamps(); // Menambahkan created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

