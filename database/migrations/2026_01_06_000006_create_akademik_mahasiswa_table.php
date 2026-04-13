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
        Schema::create('akademik_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('dosen_wali_id')->constrained('dosen')->cascadeOnDelete();
            $table->integer('semester_aktif')->default(1);
            $table->year('tahun_masuk')->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->enum('mk_nasional', ['yes', 'no'])->default('no');
            $table->enum('mk_fakultas', ['yes', 'no'])->default('no');
            $table->enum('mk_prodi', ['yes', 'no'])->default('no');
            $table->integer('sks_tempuh')->nullable();
            $table->integer('sks_now')->nullable();
            $table->integer('sks_lulus')->nullable();
            $table->integer('sks_gagal')->nullable();
            $table->enum('nilai_d_melebihi_batas', ['yes', 'no'])->default('no');
            $table->enum('nilai_e', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_mahasiswa');
    }
};
