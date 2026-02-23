<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            // Tambah field untuk menyimpan semester saat mahasiswa mengambil mata kuliah
            $table->integer('semester_ambil')->nullable()->after('kelompok_id');
        });
    }

    public function down(): void
    {
        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->dropColumn('semester_ambil');
        });
    }
};
