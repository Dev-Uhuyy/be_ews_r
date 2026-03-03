<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\DashboardService;

/**
 * @tags Mahasiswa - Dashboard
 */
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getDashboardMahasiswa()
    {
        try {
            $user = request()->user();
            $dashboardData = $this->dashboardService->getDashboardMahasiswa($user->id);

            return $this->successResponse($dashboardData, 'Dashboard mahasiswa berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboardMahasiswa');
        }
    }

    public function getCardStatusAkademik()
    {
        try {
            $user = request()->user();
            $statusAkademik = $this->dashboardService->getCardStatusAkademik($user->id);

            return $this->successResponse($statusAkademik, 'Status akademik berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardStatusAkademik');
        }
    }
}
