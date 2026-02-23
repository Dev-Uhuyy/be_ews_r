<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnDelete();

            // Di dump Anda nama tabelnya "matakuliahs" (tanpa underscore)
            $table->foreignId('matakuliah_id')
                ->constrained('mata_kuliahs')
                ->cascadeOnDelete();

            // Pastikan tabel ini memang ada dan namanya sesuai
            $table->foreignId('kelompok_id')
                ->constrained('kelompok_mata_kuliah')
                ->cascadeOnDelete();

            $table->enum('status', ['B', 'U']);

            $table->integer('absen')->nullable();
            $table->integer('nilai_uts')->nullable();
            $table->integer('nilai_akhir_angka')->nullable();
            $table->string('nilai_akhir_huruf', 5)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khs_krs_mahasiswa');
    }
};
