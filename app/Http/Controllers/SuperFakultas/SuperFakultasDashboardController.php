<?php

namespace App\Http\Controllers\SuperFakultas;

use App\Http\Controllers\Controller;
use App\Services\SuperFakultas\SuperFakultasDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * @queryParam prodi_id int ID Prodi untuk filter (opsional). Jika tidak diberikan, menampilkan semua prodi.
     *
     * @tags SuperFakultas - Dashboard
     */
    public function getDashboard(Request $request)
    {
        try {
            $prodiId = $request->query('prodi_id') ? (int) $request->query('prodi_id') : null;

            $dashboard = $this->superFakultasDashboardService->getDashboard($prodiId);

            return $this->successResponse(
                $dashboard,
                'Dashboard super fakultas berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboard');
        }
    }
}
