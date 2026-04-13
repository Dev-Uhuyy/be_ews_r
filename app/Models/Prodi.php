<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prodi extends Model
{
    use SoftDeletes;

    protected $table = 'prodis';

    protected $fillable = [
        'nama',
        'kode_prodi',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function users()
    {
        return $this->hasMany(User::class, 'prodi_id', 'id');
    }

    public function dosens()
    {
        return $this->hasMany(Dosen::class, 'prodi_id', 'id');
    }

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id', 'id');
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'prodi_id', 'id');
    }

    public function mataKuliahPeminatans()
    {
        return $this->hasMany(MataKuliahPeminatan::class, 'prodi_id', 'id');
    }
}
