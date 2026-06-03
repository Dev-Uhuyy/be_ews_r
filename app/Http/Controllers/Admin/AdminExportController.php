<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Export\DashboardExportService;
use App\Services\Admin\Export\MahasiswaListExportService;
use App\Services\Admin\Export\StatistikKelulusanExportService;
use Illuminate\Http\Request;

/**
 * @tags Admin - Export
 */
class AdminExportController extends Controller
{
    public function __construct(
        private DashboardExportService $dashboardExport,
        private StatistikKelulusanExportService $statistikExport,
        private MahasiswaListExportService $mahasiswaListExport
    ) {}

    /**
     * Export Admin Dashboard to XLSX
     *
     * Query params: tahun_masuk (optional - filter per tahun angkatan)
     *
     * @tags Admin - Export
     */
    public function exportDashboard(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->dashboardExport->exportDashboard($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportDashboard');
        }
    }

    /**
     * Export Admin Dashboard Detail to XLSX
     *
     * Query params: tahun_masuk (optional - filter per tahun angkatan)
     *
     * @tags Admin - Export
     */
    public function exportDashboardDetail(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->dashboardExport->exportDashboardDetail($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportDashboardDetail');
        }
    }

    /**
     * Export Statistik Kelulusan to XLSX
     *
     * Query params: tahun_masuk (optional - filter per tahun angkatan)
     *
     * @tags Admin - Export
     */
    public function exportStatistikKelulusan(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->statistikExport->exportStatistikKelulusan($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportStatistikKelulusan');
        }
    }

    /**
     * Export Mahasiswa List to XLSX
     *
     * Query params:
     * - tahun_masuk (optional)
     * - ipk_max (optional)
     * - sks_max (optional)
     * - has_nilai_d (optional)
     * - has_nilai_e (optional)
     * - status_kelulusan (optional)
     * - ews_status (optional)
     *
     * @tags Admin - Export
     */
    /**
     * Export Mahasiswa List to XLSX (Admin)
     */
    public function exportMahasiswaList(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->mahasiswaListExport->exportMahasiswaList($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportMahasiswaList');
        }
    }

    /**
     * Export Mahasiswa By Status to XLSX (Admin)
     */
    public function exportMahasiswaByStatus(Request $request)
    {
        try {
            $filters = $request->query();

            // Reuse exportMahasiswaList as it now supports status_mahasiswa
            return $this->mahasiswaListExport->exportMahasiswaList($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportMahasiswaByStatus');
        }
    }
}
