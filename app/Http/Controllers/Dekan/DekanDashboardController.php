<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\DekanDashboardService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Dashboard
 */
class DekanDashboardController extends Controller
{
    protected $dekanDashboardService;

    public function __construct(DekanDashboardService $dekanDashboardService)
    {
        $this->dekanDashboardService = $dekanDashboardService;
    }

    /**
     * Dashboard Dekan - Semua data dalam satu endpoint
     *
     * Mengembalikan:
     * - Statistik global (total mahasiswa, aktif, mangkir, cuti, DO)
     * - Rata-rata IPK per tahun
     * - Statistik kelulusan (eligible & non eligible)
     * - Tabel ringkasan per prodi
     *
     * @tags Dekan - Dashboard
     */
    public function getDashboard()
    {
        try {
            $dashboard = $this->dekanDashboardService->getDashboard();

            return $this->successResponse(
                $dashboard,
                'Dashboard dekan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboard');
        }
    }
}
