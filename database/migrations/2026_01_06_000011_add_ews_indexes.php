<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIndexIfNotExists('mahasiswa', 'idx_mahasiswa_prodi_status', ['prodi_id', 'status_mahasiswa']);
        $this->createIndexIfNotExists('akademik_mahasiswa', 'idx_akademik_mhs_tahun', ['mahasiswa_id', 'tahun_masuk']);
        $this->createIndexIfNotExists('akademik_mahasiswa', 'idx_akademik_tahun_ipk', ['tahun_masuk', 'ipk']);
        $this->createIndexIfNotExists('khs_krs_mahasiswa', 'idx_khs_nilai', ['mahasiswa_id', 'nilai_akhir_huruf']);
        $this->createIndexIfNotExists('khs_krs_mahasiswa', 'idx_khs_mk_nilai', ['matakuliah_id', 'nilai_akhir_huruf']);
        $this->createIndexIfNotExists('early_warning_system', 'idx_ews_status_kelulusan', ['status', 'status_kelulusan']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('mahasiswa', 'idx_mahasiswa_prodi_status');
        $this->dropIndexIfNotExists('akademik_mahasiswa', 'idx_akademik_mhs_tahun');
        $this->dropIndexIfNotExists('akademik_mahasiswa', 'idx_akademik_tahun_ipk');
        $this->dropIndexIfNotExists('khs_krs_mahasiswa', 'idx_khs_nilai');
        $this->dropIndexIfNotExists('khs_krs_mahasiswa', 'idx_khs_mk_nilai');
        $this->dropIndexIfNotExists('early_warning_system', 'idx_ews_status_kelulusan');
    }

    private function createIndexIfNotExists(string $table, string $index, array $columns): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        $exists = collect($indexes)->contains('Key_name', $index);

        if (! $exists) {
            Schema::table($table, function (Blueprint $table) use ($columns, $index) {
                $table->index($columns, $index);
            });
        }
    }

    private function dropIndexIfNotExists(string $table, string $index): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        $exists = collect($indexes)->contains('Key_name', $index);

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }
};