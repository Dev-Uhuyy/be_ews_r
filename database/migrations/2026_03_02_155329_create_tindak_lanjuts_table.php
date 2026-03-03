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
        Schema::create('tindak_lanjuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ews')->constrained('early_warning_system')->onDelete('cascade');
            $table->enum('kategori', ['rekomitmen', 'pindah_prodi']);
            $table->string('link')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['diterima', 'ditolak', 'belum diverifikasi'])->default('belum diverifikasi');
            $table->dateTime('tanggal_pengajuan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjuts');
    }
};
