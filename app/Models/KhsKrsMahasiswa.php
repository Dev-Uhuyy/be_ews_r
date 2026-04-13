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
        'status',             // enum('B','U') — Baru/Ulang
        'absen',
        'nilai_uts',
        'nilai_uas',
        'nilai_akhir_angka',
        'nilai_akhir_huruf',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'matakuliah_id', 'id');
    }

    // Alias untuk backwards compatibility
    public function mata_kuliah()
    {
        return $this->mataKuliah();
    }

    public function kelompok()
    {
        return $this->belongsTo(KelompokMataKuliah::class, 'kelompok_id', 'id');
    }

    public function kelompok_mata_kuliah()
    {
        return $this->kelompok();
    }
}
