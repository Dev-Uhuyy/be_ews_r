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

    public function earlyWarningSystem()
    {
        return $this->hasOne(EarlyWarningSystem::class, 'akademik_mahasiswa_id', 'id');
    }
}
