<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindakLanjut extends Model
{
    protected $table = 'tindak_lanjuts';

    protected $fillable = [
        'id_ews',
        'kategori',          // enum('rekomitmen','pindah_prodi')
        'link',
        'status',            // enum('belum_diverifikasi','telah_diverifikasi')
        'tanggal_pengajuan',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function earlyWarningSystem()
    {
        return $this->belongsTo(EarlyWarningSystem::class, 'id_ews', 'id');
    }
}
