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
            $table->enum('nilai_d', ['yes', 'no'])->default('no')->after('sks_gagal');
            $table->enum('nilai_e', ['yes', 'no'])->default('no')->after('nilai_d');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->dropColumn(['nilai_d', 'nilai_e']);
        });
    }
};
