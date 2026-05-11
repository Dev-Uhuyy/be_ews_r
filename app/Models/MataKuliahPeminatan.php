<?php

namespace App\Models;

use App\Traits\ProdiBelongsTo;
use Illuminate\Database\Eloquent\Model;

class MataKuliahPeminatan extends Model
{
    use ProdiBelongsTo;

    protected $table = 'mata_kuliah_peminatans';

    // Semua field dari parent (sti-api) + tambahan EWS (prodi_id)
    protected $fillable = [
        'peminatan',
        'prodi_id',  // +EWS
    ];

    // ─── Relasi dari parent (sti-api) ────────────────────────────────────────

    public function mataKuliah()
    {
        return $this->hasMany(MataKuliah::class, 'peminatan_id');
    }

    // ─── Relasi EWS-specific ─────────────────────────────────────────────────
}
