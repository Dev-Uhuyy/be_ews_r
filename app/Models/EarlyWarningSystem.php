<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarlyWarningSystem extends Model
{
    protected $table = 'early_warning_system';

    protected $fillable = [
        'akademik_mahasiswa_id',
        'status',          // enum('tepat_waktu','normal','perhatian','kritis')
        'status_kelulusan',
        'SPS1',            // enum('yes','no')
        'SPS2',            // enum('yes','no')
        'SPS3',            // enum('yes','no')
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function akademikMahasiswa()
    {
        return $this->belongsTo(AkademikMahasiswa::class, 'akademik_mahasiswa_id', 'id');
    }

    public function tindakLanjut()
    {
        return $this->hasMany(TindakLanjut::class, 'id_ews', 'id');
    }
}
