<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add NFU (Nilai Fu) tracking fields and per-semester IPS for SPS calculation.
     * Based on Python logic.py requirements:
     * - status_done_nfu_ganjil: NFU completion for odd semesters (1,3,5,7)
     * - status_done_nfu_genap: NFU completion for even semesters (2,4,6,8)
     * - ips_semester_1/2/3: For SPS (Surat Peringatan Studi) calculation
     */
    public function up(): void
    {
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            // NFU (Nilai Fu) completion status
            // Tracks whether student has completed all NFU courses for each parity
            $table->enum('status_done_nfu_ganjil', ['yes', 'no'])->default('no')->after('nilai_e');
            $table->enum('status_done_nfu_genap', ['yes', 'no'])->default('no')->after('status_done_nfu_ganjil');
            
            // IPS per semester untuk SPS (Surat Peringatan Studi) calculation
            // Based on EWS-LOGIC.md: SPS triggered when IPS semester N < 2.0
            $table->decimal('ips_semester_1', 3, 2)->nullable()->after('status_done_nfu_genap');
            $table->decimal('ips_semester_2', 3, 2)->nullable()->after('ips_semester_1');
            $table->decimal('ips_semester_3', 3, 2)->nullable()->after('ips_semester_2');

            // SPS (Surat Peringatan Studi) fields - computed from IPS thresholds
            // These are stored on akademik_mahasiswa for easy access by coordinators
            $table->enum('sps1', ['yes', 'no'])->default('no')->after('ips_semester_3');
            $table->enum('sps2', ['yes', 'no'])->default('no')->after('sps1');
            $table->enum('sps3', ['yes', 'no'])->default('no')->after('sps2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_mahasiswa', function (Blueprint $table) {
            $table->dropColumn([
                'status_done_nfu_ganjil',
                'status_done_nfu_genap',
                'ips_semester_1',
                'ips_semester_2',
                'ips_semester_3',
            ]);
        });
    }
};
