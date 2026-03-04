<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the new value to ENUM
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('belum_diverifikasi', 'diterima', 'telah_diverifikasi') NOT NULL DEFAULT 'belum_diverifikasi'");

        // 2. Update data
        DB::table('tindak_lanjuts')->where('status', 'diterima')->update(['status' => 'telah_diverifikasi']);

        // 3. Remove old value from ENUM
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('belum_diverifikasi', 'telah_diverifikasi') NOT NULL DEFAULT 'belum_diverifikasi'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add back the old value
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('belum_diverifikasi', 'telah_diverifikasi', 'diterima') NOT NULL DEFAULT 'belum_diverifikasi'");

        // 2. Revert data
        DB::table('tindak_lanjuts')->where('status', 'telah_diverifikasi')->update(['status' => 'diterima']);

        // 3. Remove new value from ENUM
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('belum_diverifikasi', 'diterima') NOT NULL DEFAULT 'belum_diverifikasi'");
    }
};
