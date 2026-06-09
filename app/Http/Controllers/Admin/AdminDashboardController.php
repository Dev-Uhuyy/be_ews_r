<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\StatistikKelulusanService;

/**
 * EWS Dashboard (Admin Level)
 *
 * Modul ini menangani grafik dan indikator matriks utama di laminar Dashboard EWS Admin.
 * Akses terbatas pada Admin dan SuperFakultas, menampilkan ringkasan data akademik dan prediksi status kelulusan.
 *
 * @tags Admin - Dashboard
 */
class AdminDashboardController extends Controller
{
    protected $adminDashboardService;

    protected $statistikKelulusanService;

    public function __construct(
        AdminDashboardService $adminDashboardService,
        StatistikKelulusanService $statistikKelulusanService
    ) {
        $this->adminDashboardService = $adminDashboardService;
        $this->statistikKelulusanService = $statistikKelulusanService;
    }

    /**
     * Dashboard Admin - data lengkap (new format)
     */
    public function getDashboard()
    {
        try {
            $data = $this->adminDashboardService->getDashboard();

            return $this->successResponse(
                $data,
                'Dashboard admin berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboard');
        }
    }

    /**
     * Detail Dashboard - data per tahun angkatan
     */
    public function getDetailDashboard()
    {
        try {
            $data = $this->adminDashboardService->getDetailDashboard();

            return $this->successResponse(
                $data,
                'Detail dashboard berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailDashboard');
        }
    }

    /**
     * List mahasiswa dengan kriteria
     *
     * Query params:
     * - tahun_masuk (optional)
     * - criteria: 'aktif', 'cuti_2x', 'tepat_waktu', 'perhatian', 'kritis'
     */
    public function getMahasiswaListByCriteria()
    {
        try {
            $tahunMasuk = request()->query('tahun_masuk');
            $criteria = request()->query('criteria');

            $data = $this->adminDashboardService->getMahasiswaListByCriteria($tahunMasuk, $criteria);

            return $this->successResponse(
                $data,
                'List mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaListByCriteria');
        }
    }

    /**
     * Statistik Kelulusan dengan detail per tahun
     */
    public function getStatistikKelulusan()
    {
        try {
            $data = $this->statistikKelulusanService->getTableStatistikKelulusanPerProdiWithTahun();

            return $this->successResponse(
                $data,
                'Statistik kelulusan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getStatistikKelulusan');
        }
    }
}
