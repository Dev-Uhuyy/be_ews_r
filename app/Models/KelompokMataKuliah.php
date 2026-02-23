<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokMataKuliah extends Model
{
    protected $table = 'kelompok_mata_kuliah';

    protected $fillable = [
        'mata_kuliah_id',
        'kode',
        'dosen_pengampu_id',
    ];

    public function mata_kuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function dosen_pengampu()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pengampu_id');
    }

    public function khs_krs_mahasiswa()
    {
        return $this->hasMany(KhsKrsMahasiswa::class, 'kelompok_id');
    }
}
