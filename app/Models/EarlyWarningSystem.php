<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarlyWarningSystem extends Model
{
    protected $table = 'early_warning_system';

    protected $fillable = [
        'akademik_mahasiswa_id',
        'status',
        'status_kelulusan',
        'status_rekomitmen',
        'id_rekomitmen',
        'link_rekomitmen',
        'tanggal_pengajuan_rekomitmen',
        'SPS1',
        'SPS2',
        'SPS3',
    ];

    protected $casts = [
        'tanggal_pengajuan_rekomitmen' => 'date',
    ];


    public function akademik_mahasiswa()
    {
        return $this->belongsTo(AkademikMahasiswa::class,'akademik_mahasiswa_id', 'id');
    }
}
