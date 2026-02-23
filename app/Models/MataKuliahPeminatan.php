<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataKuliahPeminatan extends Model
{
    protected $table = 'mata_kuliah_peminatans';

    protected $fillable = [
        'peminatan',
        'prodi_id',
    ];

    public function matakuliah()
    {
        return $this->hasMany(MataKuliah::class, 'peminatan_id');
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }
}



