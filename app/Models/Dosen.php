<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ProdiBelongsTo;

class Dosen extends Model
{
    use HasFactory, SoftDeletes, ProdiBelongsTo;

    protected $table = 'dosen';

    // Semua field dari parent (sti-api) + tambahan EWS (prodi_id)
    protected $fillable = [
        'user_id',
        'prodi_id',         // +EWS
        'gelar_depan',
        'gelar_belakang',
        'bidang_kajian',
        'scholar_link',
        'npp',
        'telepon',
        'ttd',
        'status_dosen',
        'jumlah_lulusan',
        'lulus_persen',
        'total_mhs_ta',
        'total_mhs_saat_ini',
        'kuota_ta_baru',
    ];

    protected $appends = ['nama_lengkap'];

    /**
     * Accessor: nama lengkap dosen (gelar_depan + nama user + gelar_belakang)
     */
    public function getNamaLengkapAttribute(): string
    {
        $nama        = $this->user ? $this->user->name : '';
        $gelarDepan  = $this->gelar_depan  ? trim($this->gelar_depan) . ' '  : '';
        $gelarBelkng = $this->gelar_belakang ? ' ' . trim($this->gelar_belakang) : '';

        return trim($gelarDepan . $nama . $gelarBelkng);
    }

    // ─── Relasi dari parent (sti-api) ────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ─── Relasi EWS-specific ─────────────────────────────────────────────────

    public function kelompokMataKuliah()
    {
        return $this->hasMany(KelompokMataKuliah::class, 'dosen_pengampu_id');
    }

    public function akademikMahasiswa()
    {
        return $this->hasMany(AkademikMahasiswa::class, 'dosen_wali_id', 'id');
    }

    public function mataKuliahKoordinator()
    {
        return $this->hasMany(MataKuliah::class, 'koordinator_mk', 'id');
    }
}
