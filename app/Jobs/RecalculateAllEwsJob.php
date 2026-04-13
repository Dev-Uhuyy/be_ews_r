<?php

namespace App\Jobs;

use App\Services\Kaprodi\EwsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateAllEwsJob implements ShouldQueue
{
    use Queueable;

    protected $prodiId;

    /**
     * Create a new job instance.
     */
    public function __construct($prodiId = null)
    {
        $this->prodiId = $prodiId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting RecalculateAllEwsJob...');

        try {
            $ewsService = app(EwsService::class);
            $result = $ewsService->updateAllStatus($this->prodiId);

            Log::info('RecalculateAllEwsJob completed', $result);
        } catch (\Exception $e) {
            Log::error('RecalculateAllEwsJob failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
