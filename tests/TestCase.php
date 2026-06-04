<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * Karena migrations 002 (MODIFY COLUMN) dan 011 (SHOW INDEX) MySQL-specific,
     * kita skip migrate:fresh di test env dan create schema manual di sini.
     * Hanya tables yang dipakai oleh EwsService + tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaExists()) {
            $this->createTestSchema();
        }
    }

    protected function schemaExists(): bool
    {
        try {
            return Schema::hasTable('prodis');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Create minimal schema untuk test.
     * Hanya fields yang dipakai EwsService calculation + FK relations.
     */
    protected function createTestSchema(): void
    {
        // ─── prodis ───
        if (! Schema::hasTable('prodis')) {
            Schema::create('prodis', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode_prodi');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ─── users ───
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedBigInteger('prodi_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ─── dosen ───
        if (! Schema::hasTable('dosen')) {
            Schema::create('dosen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('prodi_id')->nullable();
                $table->string('gelar_depan')->nullable();
                $table->string('gelar_belakang')->nullable();
                $table->string('bidang_kajian')->nullable();
                $table->string('scholar_link')->nullable();
                $table->string('npp')->nullable();
                $table->string('telepon')->nullable();
                $table->string('ttd')->nullable();
                $table->string('status_dosen')->nullable();
                $table->integer('jumlah_lulusan')->default(0);
                $table->decimal('lulus_persen', 5, 2)->default(0);
                $table->integer('total_mhs_ta')->default(0);
                $table->integer('total_mhs_saat_ini')->default(0);
                $table->integer('kuota_ta_baru')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ─── mahasiswa ───
        if (! Schema::hasTable('mahasiswa')) {
            Schema::create('mahasiswa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('prodi_id')->nullable();
                $table->string('nim')->unique();
                $table->text('transkrip')->nullable();
                $table->string('telepon')->nullable();
                $table->string('minat')->nullable();
                $table->string('cuti_2')->default('no');
                $table->string('status_mahasiswa')->default('aktif');
                $table->date('tanggal_yusidium')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ─── mata_kuliah_peminatans ───
        if (! Schema::hasTable('mata_kuliah_peminatans')) {
            Schema::create('mata_kuliah_peminatans', function (Blueprint $table) {
                $table->id();
                $table->string('peminatan');
                $table->unsignedBigInteger('prodi_id')->nullable();
                $table->timestamps();
            });
        }

        // ─── mata_kuliahs ───
        if (! Schema::hasTable('mata_kuliahs')) {
            Schema::create('mata_kuliahs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prodi_id');
                $table->string('kode')->unique();
                $table->string('name');
                $table->integer('sks');
                $table->integer('semester')->nullable();
                $table->string('tipe_mk')->default('prodi');
                $table->unsignedBigInteger('koordinator_mk')->nullable();
                $table->unsignedBigInteger('peminatan_id')->nullable();
                $table->timestamps();
            });
        }

        // ─── kelompok_mata_kuliah ───
        if (! Schema::hasTable('kelompok_mata_kuliah')) {
            Schema::create('kelompok_mata_kuliah', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mata_kuliah_id');
                $table->string('kode');
                $table->unsignedBigInteger('dosen_pengampu_id');
                $table->timestamps();
            });
        }

        // ─── akademik_mahasiswa ───
        if (! Schema::hasTable('akademik_mahasiswa')) {
            Schema::create('akademik_mahasiswa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mahasiswa_id');
                $table->unsignedBigInteger('dosen_wali_id');
                $table->integer('semester_aktif')->default(1);
                $table->integer('tahun_masuk')->nullable();
                $table->decimal('ipk', 3, 2)->nullable();
                $table->string('mk_nasional')->default('no');
                $table->string('mk_fakultas')->default('no');
                $table->string('mk_prodi')->default('no');
                $table->integer('sks_tempuh')->nullable();
                $table->integer('sks_now')->nullable();
                $table->integer('sks_lulus')->nullable();
                $table->integer('sks_gagal')->nullable();
                $table->string('nilai_d_melebihi_batas')->default('no');
                $table->string('nilai_e')->default('no');
                $table->timestamps();
            });
        }

        // ─── ips_mahasiswa ───
        if (! Schema::hasTable('ips_mahasiswa')) {
            Schema::create('ips_mahasiswa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mahasiswa_id');
                for ($i = 1; $i <= 14; $i++) {
                    $table->decimal("ips_{$i}", 4, 2)->nullable();
                }
                $table->timestamps();
            });
        }

        // ─── khs_krs_mahasiswa ───
        if (! Schema::hasTable('khs_krs_mahasiswa')) {
            Schema::create('khs_krs_mahasiswa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mahasiswa_id');
                $table->unsignedBigInteger('matakuliah_id');
                $table->unsignedBigInteger('kelompok_id');
                $table->integer('semester_ambil')->nullable();
                $table->string('status')->default('B');
                $table->integer('absen')->nullable();
                $table->integer('nilai_uts')->nullable();
                $table->integer('nilai_uas')->nullable();
                $table->integer('nilai_akhir_angka')->nullable();
                $table->string('nilai_akhir_huruf', 5)->nullable();
                $table->timestamps();
            });
        }

        // ─── early_warning_system ───
        if (! Schema::hasTable('early_warning_system')) {
            Schema::create('early_warning_system', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('akademik_mahasiswa_id');
                $table->string('status')->default('tepat_waktu');
                $table->string('status_kelulusan', 50)->nullable();
                $table->string('SPS1')->default('no');
                $table->string('SPS2')->default('no');
                $table->string('SPS3')->default('no');
                $table->timestamps();
            });
        }

        // ─── tindak_lanjuts ───
        if (! Schema::hasTable('tindak_lanjuts')) {
            Schema::create('tindak_lanjuts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_ews');
                $table->string('kategori');
                $table->string('link')->nullable();
                $table->string('status')->default('belum_diverifikasi');
                $table->dateTime('tanggal_pengajuan')->nullable();
                $table->timestamps();
            });
        }

        // ─── Spatie roles ───
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });
        }
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });
        }
        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
            });
        }
    }
}
