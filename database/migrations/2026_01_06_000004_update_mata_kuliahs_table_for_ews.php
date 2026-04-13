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
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            // Add prodi_id FK
            if (!Schema::hasColumn('mata_kuliahs', 'prodi_id')) {
                $table->foreignId('prodi_id')
                    ->after('id')
                    ->constrained('prodis')
                    ->cascadeOnDelete();
            }

            // Add koordinator_mk FK to dosen
            if (!Schema::hasColumn('mata_kuliahs', 'koordinator_mk')) {
                $table->foreignId('koordinator_mk')
                    ->nullable()
                    ->after('name')
                    ->constrained('dosen', 'id')
                    ->nullOnDelete();
            }

            // Add tipe_mk enum
            if (!Schema::hasColumn('mata_kuliahs', 'tipe_mk')) {
                $table->enum('tipe_mk', ['nasional', 'fakultas', 'prodi', 'peminatan'])
                    ->default('prodi')
                    ->after('semester');
            }

            // Add peminatan_id FK — kolom mungkin sudah ada di DB dari sti-api
            // sti_api.sql sudah punya peminatan_id, hanya perlu tambah FK constraint
            if (!Schema::hasColumn('mata_kuliahs', 'peminatan_id')) {
                $table->foreignId('peminatan_id')
                    ->nullable()
                    ->after('tipe_mk')
                    ->constrained('mata_kuliah_peminatans')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->dropForeign(['peminatan_id']);
            $table->dropColumn('peminatan_id');
            $table->dropColumn('tipe_mk');
            $table->dropForeign(['koordinator_mk']);
            $table->dropColumn('koordinator_mk');
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');
        });
    }
};
