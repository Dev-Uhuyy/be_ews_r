<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\DetailDashboardService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Detail Dashboard
 */
class DetailDashboardController extends Controller
{
    protected $detailDashboardService;

    public function __construct(DetailDashboardService $detailDashboardService)
    {
        $this->detailDashboardService = $detailDashboardService;
    }

    /**
     * Get Detail Dashboard - data per prodi dan tahun angkatan
     *
     * Query params: prodi_id (optional)
     */
    public function getDetailDashboard()
    {
        try {
            $prodiId = request()->query('prodi_id');
            $data = $this->detailDashboardService->getDetailDashboard($prodiId);

            return $this->successResponse(
                $data,
                'Detail dashboard berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailDashboard');
        }
    }

    /**
     * Get list mahasiswa dengan kriteria spesifik per prodi
     *
     * Query params:
     * - prodi_id (required)
     * - tahun_masuk (optional)
     * - criteria: 'aktif', 'cuti_2x', 'tepat_waktu', 'perhatian', 'kritis'
     *
     * Contoh:
     * - GET /dashboard/mahasiswa?prodi_id=1&criteria=aktif
     * - GET /dashboard/mahasiswa?prodi_id=1&tahun_masuk=2023&criteria=kritis
     */
    public function getMahasiswaListByCriteria()
    {
        try {
            $prodiId = request()->query('prodi_id');
            $tahunMasuk = request()->query('tahun_masuk');
            $criteria = request()->query('criteria');

            if (!$prodiId) {
                return $this->errorResponse('prodi_id wajib diisi', 400);
            }

            $data = $this->detailDashboardService->getMahasiswaListByCriteria(
                $prodiId,
                $tahunMasuk,
                $criteria
            );

            return $this->successResponse(
                $data,
                'List mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaListByCriteria');
        }
    }
}
