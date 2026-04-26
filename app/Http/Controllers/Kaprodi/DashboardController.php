<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\Kaprodi\KaprodiDashboardService;
use App\Services\Kaprodi\StatistikKelulusanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * EWS Dashboard (Kaprodi Level)
 *
 * Modul ini menangani grafik dan indikator matriks utama di laminar Dashboard EWS Kaprodi.
 * Akses terbatas pada Kaprodi dan Dekan, menampilkan ringkasan data akademik dan prediksi status kelulusan.
 *
 * @tags Kaprodi - Dashboard
 */
class DashboardController extends Controller
{
    protected $kaprodiDashboardService;
    protected $statistikKelulusanService;

    public function __construct(
        KaprodiDashboardService $kaprodiDashboardService,
        StatistikKelulusanService $statistikKelulusanService
    ) {
        $this->kaprodiDashboardService = $kaprodiDashboardService;
        $this->statistikKelulusanService = $statistikKelulusanService;
    }

    /**
     * Dashboard Kaprodi - data lengkap (new format)
     */
    public function getDashboard()
    {
        try {
            $data = $this->kaprodiDashboardService->getDashboard();

            return $this->successResponse(
                $data,
                'Dashboard kaprodi berhasil diambil'
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
            $data = $this->kaprodiDashboardService->getDetailDashboard();

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

            $data = $this->kaprodiDashboardService->getMahasiswaListByCriteria($tahunMasuk, $criteria);

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
