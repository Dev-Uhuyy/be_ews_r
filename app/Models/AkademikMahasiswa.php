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
        'mk_nasional',
        'mk_fakultas',
        'mk_prodi',
        'sks_tempuh',
        'sks_now',
        'sks_lulus',
        'sks_gagal',
        'nilai_d_melebihi_batas',
        'nilai_e',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function dosen_wali()
    {
        return $this->belongsTo(Dosen::class, 'dosen_wali_id', 'id');
    }

    public function early_warning_systems()
    {
        return $this->hasMany(EarlyWarningSystem::class, 'akademik_mahasiswa_id', 'id');
    }
}
