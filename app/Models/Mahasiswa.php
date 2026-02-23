<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Mahasiswa extends Model
{
    use SoftDeletes, HasFactory, Notifiable;

    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'nim',
        'transkrip',
        'telepon',
        'minat',
        'cuti_2',
        'status_mahasiswa',
        'tanggal_yusidium',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function prodi()
    {
        return $this->hasOne(Prodi::class, 'id', 'prodi_id');
    }

    public function ipsmahasiswa()
    {
        return $this->hasOne(IpsMahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function akademikmahasiswa()
    {
        return $this->hasOne(AkademikMahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function khskrsmahasiswa()
    {
        return $this->hasMany(KhsKrsMahasiswa::class, 'mahasiswa_id', 'id');
    }

}
