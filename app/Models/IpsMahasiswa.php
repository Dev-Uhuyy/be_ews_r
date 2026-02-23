<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpsMahasiswa extends Model
{
    protected $table = 'ips_mahasiswa';

    protected $fillable = [
        'mahasiswa_id',
        'ips_1',
        'ips_2',
        'ips_3',
        'ips_4',
        'ips_5',
        'ips_6',
        'ips_7',
        'ips_8',
        'ips_9',
        'ips_10',
        'ips_11',
        'ips_12',
        'ips_13',
        'ips_14',
    ];

public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }
}
