<?php

namespace App\Http\Controllers\SuperFakultas;

use App\Http\Controllers\Controller;
use App\Services\SuperFakultas\SuperFakultasDashboardService;

/**
 * @tags SuperFakultas - Dashboard
 */
class SuperFakultasDashboardController extends Controller
{
    protected $superFakultasDashboardService;

    public function __construct(SuperFakultasDashboardService $superFakultasDashboardService)
    {
        $this->superFakultasDashboardService = $superFakultasDashboardService;
    }

    /**
     * Dashboard SuperFakultas - Semua data dalam satu endpoint
     *
     * Mengembalikan:
     * - Statistik global (total mahasiswa, aktif, mangkir, cuti, DO)
     * - Rata-rata IPK per tahun
     * - Statistik kelulusan (eligible & non eligible)
     * - Tabel ringkasan per prodi
     *
     * @tags SuperFakultas - Dashboard
     */
    public function getDashboard()
    {
        try {
            $dashboard = $this->superFakultasDashboardService->getDashboard();

            return $this->successResponse(
                $dashboard,
                'Dashboard super fakultas berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboard');
        }
    }
}
