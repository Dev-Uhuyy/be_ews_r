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

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'id');
    }

    public function dosenPengampu()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pengampu_id', 'id');
    }

    public function khsKrs()
    {
        return $this->hasMany(KhsKrsMahasiswa::class, 'kelompok_id', 'id');
    }
}
