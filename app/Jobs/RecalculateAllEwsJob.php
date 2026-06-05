<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Admin\EwsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Recalculate seluruh status EWS di background.
 *
 * - prodiId null  → seluruh fakultas (dipakai super_fakultas)
 * - prodiId diisi → hanya 1 prodi (dipakai admin / super_fakultas yang men-scope)
 *
 * Catatan: job berjalan di luar konteks Auth, jadi scope ditentukan murni
 * lewat $prodiId yang di-pass eksplisit ke updateAllStatus().
 */
class RecalculateAllEwsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    protected ?int $prodiId;

    public function __construct(?int $prodiId = null)
    {
        $this->prodiId = $prodiId;
    }

    public function handle(EwsService $ewsService): void
    {
        $result = $ewsService->updateAllStatus($this->prodiId);

        Log::info('RecalculateAllEwsJob selesai', [
            'prodi_id' => $this->prodiId,
            'total_processed' => $result['total_processed'] ?? 0,
            'total_updated' => $result['total_updated'] ?? 0,
        ]);
    }
}
