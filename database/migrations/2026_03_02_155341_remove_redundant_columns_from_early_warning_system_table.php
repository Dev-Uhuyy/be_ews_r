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
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropColumn([
                'status_rekomitmen',
                'link_rekomitmen',
                'tanggal_pengajuan_rekomitmen',
                'id_rekomitmen'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->enum('status_rekomitmen', ['diterima', 'ditolak', 'belum diverifikasi'])->default('belum diverifikasi')->after('status_kelulusan');
            $table->string('link_rekomitmen')->nullable()->after('status_rekomitmen');
            $table->date('tanggal_pengajuan_rekomitmen')->nullable()->after('link_rekomitmen');
            $table->string('id_rekomitmen', 50)->nullable()->unique()->after('status_rekomitmen');
        });
    }
};
