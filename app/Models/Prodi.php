<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $table = 'prodi';

    protected $fillable = [
        'nama',
    ];

    public function prodi_users()
    {
        return $this->hasMany(ProdiUser::class, 'prodi_id', 'id');
    }

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id', 'id');
    }

    public function dosens()
    {
        return $this->hasMany(Dosen::class, 'prodi_id', 'id');
    }

    public function mata_kuliah_peminatans()
    {
        return $this->hasMany(MataKuliahPeminatan::class, 'prodi_id', 'id');
    }
}
