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
        // Drop existing foreign keys and recreate with cascade delete

        // 1. Mahasiswa table - cascade delete from users
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // 2. Akademik Mahasiswa - cascade delete from mahasiswa
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa')
                ->onDelete('cascade');
        });

        // 3. IPS Mahasiswa - cascade delete from mahasiswa
        Schema::table('ips_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa')
                ->onDelete('cascade');
        });

        // 4. KHS KRS Mahasiswa - cascade delete from mahasiswa
        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa')
                ->onDelete('cascade');
        });

        // 5. Early Warning System - cascade delete from akademik_mahasiswa
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropForeign(['akademik_mahasiswa_id']);
            $table->foreign('akademik_mahasiswa_id')
                ->references('id')
                ->on('akademik_mahasiswa')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original foreign keys without cascade

        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa');
        });

        Schema::table('ips_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa');
        });

        Schema::table('khs_krs_mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswa');
        });

        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropForeign(['akademik_mahasiswa_id']);
            $table->foreign('akademik_mahasiswa_id')
                ->references('id')
                ->on('akademik_mahasiswa');
        });
    }
};
