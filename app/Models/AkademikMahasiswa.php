<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkademikMahasiswa extends Model
{
    protected $table = 'akademik_mahasiswa';

    protected $fillable = [
        'mahasiswa_id',
        'dosen_wali_id',
        'semester_aktif',
        'tahun_masuk',
        'ipk',
        'mk_nasional',    // enum('yes','no')
        'mk_fakultas',    // enum('yes','no')
        'mk_prodi',       // enum('yes','no')
        'sks_tempuh',
        'sks_now',
        'sks_lulus',
        'sks_gagal',
        'nilai_d_melebihi_batas', // enum('yes','no')
        'nilai_e',                // enum('yes','no')
        // NFU (Nilai Fu) tracking - from Python logic.py
        'status_done_nfu_ganjil',  // enum('yes','no') - NFU completed for odd semesters
        'status_done_nfu_genap',   // enum('yes','no') - NFU completed for even semesters
        // IPS per semester for SPS calculation
        'ips_semester_1',          // decimal(3,2) - IPS semester 1
        'ips_semester_2',          // decimal(3,2) - IPS semester 2
        'ips_semester_3',          // decimal(3,2) - IPS semester 3
        // SPS fields (updated by EwsService)
        'sps1',                    // enum('yes','no') - IPS semester 1 < 2.0
        'sps2',                    // enum('yes','no') - IPS semester 2 < 2.0
        'sps3',                    // enum('yes','no') - IPS semester 3 < 2.0
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function dosenWali()
    {
        return $this->belongsTo(Dosen::class, 'dosen_wali_id', 'id');
    }

    /**
     * Alias for dosenWali() - handles snake_case access pattern
     */
    public function getDosenWaliRelation()
    {
        return $this->dosenWali();
    }

    public function earlyWarningSystem()
    {
        return $this->hasOne(EarlyWarningSystem::class, 'akademik_mahasiswa_id', 'id');
    }
}
