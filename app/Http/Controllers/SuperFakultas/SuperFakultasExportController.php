<?php

namespace App\Http\Controllers\SuperFakultas;

use App\Http\Controllers\Controller;
use App\Services\SuperFakultas\Export\DashboardExportService;
use App\Services\SuperFakultas\Export\DetailAngkatanExportService;
use App\Services\SuperFakultas\Export\MahasiswaListExportService;
use App\Services\SuperFakultas\Export\NilaiMahasiswaExportService;
use App\Services\SuperFakultas\Export\StatistikKelulusanExportService;
use Illuminate\Http\Request;

/**
 * @tags SuperFakultas - Export
 */
class SuperFakultasExportController extends Controller
{
    public function __construct(
        private DashboardExportService $dashboardExport,
        private StatistikKelulusanExportService $statistikExport,
        private DetailAngkatanExportService $detailAngkatanExport,
        private MahasiswaListExportService $mahasiswaListExport,
        private NilaiMahasiswaExportService $nilaiMahasiswaExport
    ) {}

    /**
     * Export SuperFakultas Dashboard to XLSX
     *
     * @tags SuperFakultas - Export
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
     * Export SuperFakultas Dashboard Detail to XLSX
     *
     * Query params: prodi_id (optional - filter ke satu prodi saja)
     *
     * @tags SuperFakultas - Export
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
     * @tags SuperFakultas - Export
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
     * @param  string  $tahunMasuk  Tahun angkatan
     *                              Query params: prodi_id (optional - filter ke satu prodi saja)
     *
     * @tags SuperFakultas - Export
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
     * @tags SuperFakultas - Export
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
     * Export Mahasiswa By Status to XLSX
     *
     * Query params: prodi_id, tahun_masuk, status_mahasiswa, ews_status
     *
     * @tags SuperFakultas - Export
     */
    public function exportMahasiswaByStatus(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->mahasiswaListExport->exportMahasiswaByStatus($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportMahasiswaByStatus');
        }
    }

    /**
     * Export Nilai Detail to XLSX
     *
     * Query params: prodi_id, tahun_masuk, mahasiswa_id (optional - for single mahasiswa)
     *
     * @tags SuperFakultas - Export
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
     * @tags SuperFakultas - Export
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
