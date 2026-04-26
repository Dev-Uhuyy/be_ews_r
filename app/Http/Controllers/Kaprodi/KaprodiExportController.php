<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\Kaprodi\Export\DashboardExportService;
use App\Services\Kaprodi\Export\StatistikKelulusanExportService;
use App\Services\Kaprodi\Export\MahasiswaListExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @tags Kaprodi - Export
 */
class KaprodiExportController extends Controller
{
    public function __construct(
        private DashboardExportService $dashboardExport,
        private StatistikKelulusanExportService $statistikExport,
        private MahasiswaListExportService $mahasiswaListExport
    ) {}

    /**
     * Export Kaprodi Dashboard to XLSX
     *
     * Query params: tahun_masuk (optional - filter per tahun angkatan)
     *
     * @tags Kaprodi - Export
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
     * Export Kaprodi Dashboard Detail to XLSX
     *
     * Query params: tahun_masuk (optional - filter per tahun angkatan)
     *
     * @tags Kaprodi - Export
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
     * @tags Kaprodi - Export
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
     * @tags Kaprodi - Export
     */
    /**
     * Export Mahasiswa List to XLSX (Kaprodi)
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
     * Export Mahasiswa By Status to XLSX (Kaprodi)
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