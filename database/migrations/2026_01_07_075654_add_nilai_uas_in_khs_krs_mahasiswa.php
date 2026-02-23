<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->foreignId('nilai_uas')
                ->nullable()
                ->after('nilai_uts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->dropColumn('nilai_uas');
        });
    }
};
