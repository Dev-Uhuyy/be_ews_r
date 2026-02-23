<?php

namespace App\Jobs;

use App\Services\EwsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateAllEwsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting RecalculateAllEwsJob...');

        try {
            $ewsService = app(EwsService::class);
            $result = $ewsService->updateAllStatus();

            Log::info('RecalculateAllEwsJob completed', $result);
        } catch (\Exception $e) {
            Log::error('RecalculateAllEwsJob failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
