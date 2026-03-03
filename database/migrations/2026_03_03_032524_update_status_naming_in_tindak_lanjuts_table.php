<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. First, expand enum to include the new value (to avoid truncation errors)
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('diterima', 'ditolak', 'belum diverifikasi', 'belum_diverifikasi') DEFAULT 'belum diverifikasi'");

        // 2. Update existing data to standard naming
        DB::table('tindak_lanjuts')
            ->where('status', 'belum diverifikasi')
            ->orWhere('status', 'ditolak')
            ->update(['status' => 'belum_diverifikasi']);

        // 3. Finalize enum to only allowed values
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('diterima', 'belum_diverifikasi') DEFAULT 'belum_diverifikasi'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tindak_lanjuts MODIFY COLUMN status ENUM('diterima', 'ditolak', 'belum diverifikasi') DEFAULT 'belum diverifikasi'");

        DB::table('tindak_lanjuts')
            ->where('status', 'belum_diverifikasi')
            ->update(['status' => 'belum diverifikasi']);
    }
};
