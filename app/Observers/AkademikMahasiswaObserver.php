<?php

namespace App\Observers;

use App\Models\AkademikMahasiswa;
use App\Services\EwsService;
use Illuminate\Support\Facades\Log;

class AkademikMahasiswaObserver
{
    /**
     * Handle the AkademikMahasiswa "created" event.
     */
    public function created(AkademikMahasiswa $akademikMahasiswa): void
    {
        // Auto create EWS status saat akademik mahasiswa dibuat
        $this->updateEwsStatus($akademikMahasiswa);
    }

    /**
     * Handle the AkademikMahasiswa "updated" event.
     */
    public function updated(AkademikMahasiswa $akademikMahasiswa): void
    {
        // Skip jika sedang bulk update
        if (app()->bound('bulk_updating_ews')) {
            return;
        }

        // Auto update EWS status saat data berubah
        $this->updateEwsStatus($akademikMahasiswa);
    }

    /**
     * Handle the AkademikMahasiswa "deleted" event.
     */
    public function deleted(AkademikMahasiswa $akademikMahasiswa): void
    {
        // Hapus EWS record saat akademik mahasiswa dihapus
        $akademikMahasiswa->early_warning_systems()->delete();
    }

    /**
     * Update status EWS
     */
    private function updateEwsStatus(AkademikMahasiswa $akademikMahasiswa)
    {
        try {
            app(EwsService::class)->updateStatus($akademikMahasiswa);
        } catch (\Exception $e) {
            Log::error("Error updating EWS for akademik_id {$akademikMahasiswa->id}: " . $e->getMessage());
        }
    }
}
