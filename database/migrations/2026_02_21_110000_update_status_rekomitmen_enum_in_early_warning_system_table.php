<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Ubah kolom jadi VARCHAR dulu untuk bisa update data
        DB::statement("ALTER TABLE early_warning_system MODIFY COLUMN status_rekomitmen VARCHAR(50)");

        // Step 2: Update existing data
        // 'yes' -> 'diterima'
        // 'no' -> 'ditolak'
        // NULL -> 'belum diverifikasi'
        DB::table('early_warning_system')
            ->where('status_rekomitmen', 'yes')
            ->update(['status_rekomitmen' => 'diterima']);

        DB::table('early_warning_system')
            ->where('status_rekomitmen', 'no')
            ->update(['status_rekomitmen' => 'ditolak']);

        DB::table('early_warning_system')
            ->whereNull('status_rekomitmen')
            ->update(['status_rekomitmen' => 'belum diverifikasi']);

        // Step 3: Ubah jadi ENUM dengan nilai baru
        DB::statement("ALTER TABLE early_warning_system MODIFY COLUMN status_rekomitmen ENUM('diterima', 'ditolak', 'belum diverifikasi') DEFAULT 'belum diverifikasi' NOT NULL");
    }

    public function down(): void
    {
        // Step 1: Ubah jadi VARCHAR dulu
        DB::statement("ALTER TABLE early_warning_system MODIFY COLUMN status_rekomitmen VARCHAR(50)");

        // Step 2: Revert data
        DB::table('early_warning_system')
            ->where('status_rekomitmen', 'diterima')
            ->update(['status_rekomitmen' => 'yes']);

        DB::table('early_warning_system')
            ->where('status_rekomitmen', 'ditolak')
            ->update(['status_rekomitmen' => 'no']);

        DB::table('early_warning_system')
            ->where('status_rekomitmen', 'belum diverifikasi')
            ->update(['status_rekomitmen' => NULL]);

        // Step 3: Revert back to original enum values
        DB::statement("ALTER TABLE early_warning_system MODIFY COLUMN status_rekomitmen ENUM('yes', 'no') NULL");
    }
};
