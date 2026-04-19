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
        'SPS1',            // enum('yes','no') - IPS semester 1 < 2.0
        'SPS2',            // enum('yes','no') - IPS semester 2 < 2.0
        'SPS3',            // enum('yes','no') - IPS semester 3 < 2.0
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
