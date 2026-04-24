<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\Export\DashboardExportService;
use App\Services\Dekan\Export\StatistikKelulusanExportService;
use App\Services\Dekan\Export\DetailAngkatanExportService;
use App\Services\Dekan\Export\MahasiswaListExportService;
use App\Services\Dekan\Export\NilaiMahasiswaExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @tags Dekan - Export
 */
class DekanExportController extends Controller
{
    public function __construct(
        private DashboardExportService $dashboardExport,
        private StatistikKelulusanExportService $statistikExport,
        private DetailAngkatanExportService $detailAngkatanExport,
        private MahasiswaListExportService $mahasiswaListExport,
        private NilaiMahasiswaExportService $nilaiMahasiswaExport
    ) {}

    /**
     * Export Dekan Dashboard to XLSX
     *
     * @tags Dekan - Export
     */
    public function exportDashboard()
    {
        try {
            return $this->dashboardExport->exportDashboard();
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportDashboard');
        }
    }

    /**
     * Export Dekan Dashboard Detail to XLSX
     *
     * Query params: prodi_id (optional - filter ke satu prodi saja)
     *
     * @tags Dekan - Export
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
     * @tags Dekan - Export
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
     * Export Detail Angkatan to XLSX
     *
     * @param string $tahunMasuk Tahun angkatan
     * Query params: prodi_id (optional - filter ke satu prodi saja)
     *
     * @tags Dekan - Export
     */
    public function exportDetailAngkatan($tahunMasuk, Request $request)
    {
        try {
            $filters = $request->query();
            $filters['tahunMasuk'] = $tahunMasuk;
            return $this->detailAngkatanExport->exportDetailAngkatan($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportDetailAngkatan');
        }
    }

    /**
     * Export Mahasiswa List to XLSX
     *
     * Query params: prodi_id, tahun_masuk, ipk_max, sks_max, has_nilai_d, has_nilai_e, status_kelulusan, ews_status
     *
     * @tags Dekan - Export
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
     * Export Nilai Detail to XLSX
     *
     * Query params: prodi_id, tahun_masuk, mahasiswa_id (optional - for single mahasiswa)
     *
     * @tags Dekan - Export
     */
    public function exportNilaiDetail(Request $request)
    {
        try {
            $filters = $request->query();
            return $this->nilaiMahasiswaExport->exportNilaiDetail($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportNilaiDetail');
        }
    }

    /**
     * Export Nilai Summary to XLSX
     *
     * Query params: prodi_id, tahun_masuk
     *
     * @tags Dekan - Export
     */
    public function exportNilaiSummary(Request $request)
    {
        try {
            $filters = $request->query();
            return $this->nilaiMahasiswaExport->exportNilaiSummary($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportNilaiSummary');
        }
    }
}