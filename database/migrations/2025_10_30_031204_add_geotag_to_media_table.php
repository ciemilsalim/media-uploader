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
        Schema::table('media', function (Blueprint $table) {
            // Menambahkan kolom untuk Waktu Pengambilan (dari JS)
            $table->timestamp('taken_at')->nullable()->after('description');
            
            // Menambahkan kolom untuk Geotag (GPS)
            // Presisi 10 digit total, 7 di belakang koma (standar GPS)
            $table->decimal('latitude', 10, 7)->nullable()->after('taken_at');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['taken_at', 'latitude', 'longitude']);
        });
    }
};
