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
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->renameColumn('nilai_d', 'nilai_d_melebihi_batas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->renameColumn('nilai_d_melebihi_batas', 'nilai_d');
        });
    }
};
