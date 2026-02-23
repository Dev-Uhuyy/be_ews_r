<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_kuliah_peminatans', function (Blueprint $table) {
            $table->foreignId('prodi_id')
                ->nullable()
                ->after('id')
                ->constrained('prodi')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mata_kuliah_peminatans', function (Blueprint $table) {
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');
        });
    }
};
