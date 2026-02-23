<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->enum('status_mahasiswa', [
                'lulus',
                'aktif',
                'mangkir',
                'tidak_aktif',
                'cuti',
                'DO'
            ])
            ->default('aktif')
            ->change();
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->enum('status_mahasiswa', [
                'lulus',
                'aktif',
                'mangkir',
                'tidak_aktif'
            ])
            ->default('aktif')
            ->change();
        });
    }
};
