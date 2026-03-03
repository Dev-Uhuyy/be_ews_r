<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindakLanjut extends Model
{
    protected $table = 'tindak_lanjuts';

    protected $fillable = [
        'id_ews',
        'kategori',
        'link',
        'catatan',
        'status',
        'tanggal_pengajuan',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
    ];

    public function ews()
    {
        return $this->belongsTo(EarlyWarningSystem::class, 'id_ews');
    }
}
