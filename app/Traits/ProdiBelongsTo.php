<?php

namespace App\Traits;

use App\Models\Prodi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait ProdiBelongsTo
{
    /**
     * Get the prodi that owns the model.
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id', 'id');
    }

    /**
     * Scope a query untuk memfilter berdasarkan prodi_id.
     * Secara otomatis mengambil prodi_id dari Auth user jika merupakan kaprodi.
     * Jika role dekan, bisa memfilter berdasarkan request() ['prodi_id'] jika ada.
     */
    public function scopeFilterByProdi(Builder $query, $prodiId = null): Builder
    {
        $user = Auth::user();

        // Jika prodiId diberikan secara eksplisit, gunakan
        if ($prodiId !== null) {
            return $query->where($this->getTable() . '.prodi_id', $prodiId);
        }

        // Cek auth logic
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                // Kaprodi: Wajib limit sesuai prodi mereka
                return $query->where($this->getTable() . '.prodi_id', $user->prodi_id);
            }
            if ($user->hasRole('dekan')) {
                // Dekan: Bisa filter request jika ada (kalau tidak, munculkan semua)
                if (request()->has('prodi_id') && request('prodi_id') != '') {
                    return $query->where($this->getTable() . '.prodi_id', request('prodi_id'));
                }
            }
        }

        return $query;
    }
}
