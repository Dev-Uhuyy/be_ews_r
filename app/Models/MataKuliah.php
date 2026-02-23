<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    protected $table = 'mata_kuliahs';

    protected $fillable = [
        'prodi_id',
        'kode',
        'name',
        'koordinator_mk',
        'sks',
        'semester',
        'tipe_mk',
        'peminatan_id',
    ];

    public function peminatan()
    {
        return $this->belongsTo(MataKuliahPeminatan::class, 'peminatan_id');
    }

    public function kelompok_mata_kuliah()
    {
        return $this->hasMany(KelompokMataKuliah::class, 'mata_kuliah_id');
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id', 'id');
    }

    public function koordinator()
    {
        return $this->belongsTo(Dosen::class, 'koordinator_mk', 'id');
    }
}
