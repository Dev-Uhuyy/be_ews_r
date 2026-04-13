<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Add prodi_id FK
            $table->foreignId('prodi_id')
                ->nullable()
                ->after('user_id')
                ->constrained('prodis')
                ->nullOnDelete();

            // Add minat column
            $table->string('minat', 255)->nullable()->after('telepon');

            // Add cuti_2 column
            $table->enum('cuti_2', ['yes', 'no'])->default('no')->after('minat');
        });

        // Update status_mahasiswa enum to include 'cuti' and 'DO'
        DB::statement("ALTER TABLE mahasiswa MODIFY COLUMN status_mahasiswa ENUM('lulus','aktif','mangkir','tidak_aktif','cuti','DO') DEFAULT 'aktif'");

        // Drop sks and ipk columns (moved to akademik_mahasiswa)
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropColumn(['sks', 'ipk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->double('ipk')->nullable();
            $table->integer('sks')->nullable();
        });

        DB::statement("ALTER TABLE mahasiswa MODIFY COLUMN status_mahasiswa ENUM('lulus','aktif','mangkir','tidak_aktif') DEFAULT 'aktif'");

        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropColumn(['cuti_2', 'minat']);
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');
        });
    }
};
