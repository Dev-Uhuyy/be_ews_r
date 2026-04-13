<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ProdiBelongsTo;

class MataKuliah extends Model
{
    use ProdiBelongsTo;
    protected $table = 'mata_kuliahs';

    // Semua field dari parent (sti-api) + tambahan EWS (prodi_id, koordinator_mk, tipe_mk)
    protected $fillable = [
        'prodi_id',       // +EWS
        'kode',
        'name',
        'sks',
        'semester',
        'tipe_mk',        // +EWS enum('nasional','fakultas','prodi','peminatan')
        'koordinator_mk', // +EWS FK ke dosen
        'peminatan_id',
    ];

    // ─── Relasi dari parent (sti-api) ────────────────────────────────────────

    public function peminatan()
    {
        return $this->belongsTo(MataKuliahPeminatan::class, 'peminatan_id');
    }

    // ─── Relasi EWS-specific ─────────────────────────────────────────────────

    public function koordinator()
    {
        return $this->belongsTo(Dosen::class, 'koordinator_mk', 'id');
    }

    public function kelompokMataKuliah()
    {
        return $this->hasMany(KelompokMataKuliah::class, 'mata_kuliah_id');
    }

    public function khsKrs()
    {
        return $this->hasMany(KhsKrsMahasiswa::class, 'matakuliah_id');
    }
}
