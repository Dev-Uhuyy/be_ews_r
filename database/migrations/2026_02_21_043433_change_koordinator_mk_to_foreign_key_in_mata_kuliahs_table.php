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
            // Drop kolom koordinator_mk yang lama (string)
            $table->dropColumn('koordinator_mk');
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            // Tambah kolom koordinator_mk sebagai foreign key ke tabel dosen
            $table->foreignId('koordinator_mk')->nullable()->after('name')->constrained('dosen', 'id')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            // Drop foreign key dan kolom
            $table->dropForeign(['koordinator_mk']);
            $table->dropColumn('koordinator_mk');
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            // Kembalikan ke kolom string
            $table->string('koordinator_mk')->nullable()->after('name');
        });
    }
};
