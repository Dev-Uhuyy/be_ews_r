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
            $table->enum('mk_nasional', ['yes', 'no'])->default('no')->after('ipk');
            $table->enum('mk_fakultas', ['yes', 'no'])->default('no')->after('mk_nasional');
            $table->enum('mk_prodi', ['yes', 'no'])->default('no')->after('mk_fakultas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->dropColumn(['mk_nasional', 'mk_fakultas', 'mk_prodi']);
        });
    }
};
