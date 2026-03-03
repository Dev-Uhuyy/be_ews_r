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
        'SPS1',
        'SPS2',
        'SPS3',
    ];

    public function akademik_mahasiswa()
    {
        return $this->belongsTo(AkademikMahasiswa::class,'akademik_mahasiswa_id', 'id');
    }

    public function tindak_lanjuts()
    {
        return $this->hasMany(TindakLanjut::class, 'id_ews');
    }
}
