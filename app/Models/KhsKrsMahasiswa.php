<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhsKrsMahasiswa extends Model
{
    protected $table = 'khs_krs_mahasiswa';

    protected $fillable = [
        'mahasiswa_id',
        'matakuliah_id',
        'kelompok_id',
        'semester_ambil',
        'absen',
        'status',
        'nilai_uts',
        'nilai_uas',
        'nilai_akhir_angka',
        'nilai_akhir_huruf',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function mata_kuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'matakuliah_id');
    }

    public function kelompok_mata_kuliah()
    {
        return $this->belongsTo(KelompokMataKuliah::class, 'kelompok_id');
    }
}
