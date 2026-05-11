<?php

namespace App\Models;

use App\Traits\ProdiBelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Mahasiswa extends Model
{
    use HasFactory, Notifiable, ProdiBelongsTo, SoftDeletes;

    protected $table = 'mahasiswa';

    // Semua field dari parent (sti-api) + tambahan EWS (prodi_id, minat, cuti_2)
    protected $fillable = [
        'user_id',
        'prodi_id',          // +EWS
        'nim',
        'transkrip',
        'telepon',
        'minat',             // +EWS
        'cuti_2',            // +EWS enum('yes','no')
        'status_mahasiswa',  // enum extended: +cuti, DO
        'tanggal_yusidium',
    ];

    // ─── Relasi dari parent (sti-api) ────────────────────────────────────────

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    // ─── Relasi EWS-specific ─────────────────────────────────────────────────

    public function ipsMahasiswa()
    {
        return $this->hasOne(IpsMahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function akademikMahasiswa()
    {
        return $this->hasOne(AkademikMahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function khsKrsMahasiswa()
    {
        return $this->hasMany(KhsKrsMahasiswa::class, 'mahasiswa_id', 'id');
    }
}
