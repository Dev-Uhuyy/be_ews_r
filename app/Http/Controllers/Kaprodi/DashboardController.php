<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\Kaprodi\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * EWS Dashboard (Kaprodi Level)
 * 
 * Modul ini menangani grafik dan indikator matriks utama di laman Dashboard EWS Kaprodi.
 * Akses terbatas pada Kaprodi dan Dekan, menampilkan ringkasan data akademik dan prediksi status kelulusan.
 * 
 * @tags Kaprodi - Dashboard
 */
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dapatkan Full Matriks Dashboard
     * 
     * Mengembalikan 3 komponen data sekaligus dalam satu pemanggilan: 
     * 1) status_mahasiswa (Aktif, DO, Cuti, dll), 
     * 2) rata_ipk_per_angkatan (Tren IPK), dan 
     * 3) status_kelulusan (Eligible / Non-Eligible).
     * 
     * @tags Kaprodi - Dashboard
     * @response 200 { "meta": { "status": "success", "message": "..." }, "data": { "status_mahasiswa": {...}, "rata_ipk_per_angkatan": [...], "status_kelulusan": {...} } }
     */
    public function getDashboard()
    {
        try {
            $dashboard = [
                'status_mahasiswa' => $this->dashboardService->getStatusMahasiswa(),
                'rata_ipk_per_angkatan' => $this->dashboardService->getRataIpkPerAngkatan(),
                'status_kelulusan' => $this->dashboardService->getStatusKelulusan(),
            ];

            return $this->successResponse(
                $dashboard,
                'Dashboard data berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboard');
        }
    }

    /**
     * Grafik Status Keaktifan Mahasiswa
     * 
     * Mengambil distribusi persentase mahasiswa berdasarkan status akademik (aktif, mangkir, cuti)
     * untuk dirender pada grafik Pie Chart Dashboard.
     * 
     * @tags Kaprodi - Dashboard
     * @response 200 { "meta": {"status":"success"}, "data": { "total": 120, "aktif": 100, "mangkir": 10, "cuti": 10 } }
     */
    public function getStatusMahasiswa()
    {
        try {
            $statusMahasiswa = $this->dashboardService->getStatusMahasiswa();
            return $this->successResponse(
                $statusMahasiswa,
                'Status mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getStatusMahasiswa');
        }
    }

    /**
     * Grafik Rata-rata IPK per Angkatan
     * 
     * Mengembalikan data array berupa tren IPK mahasiswa per angkatan dari tahun tertua hingga terbaru.
     * Dapat dipetakan langsung pada Line Chart.
     * 
     * @tags Kaprodi - Dashboard
     * @response 200 { "meta": {"status":"success"}, "data": [ { "tahun_masuk": 2020, "rata_ipk": "3.55", "jumlah_mahasiswa": 45 } ] }
     */
    public function getRataIpkPerAngkatan()
    {
        try {
            $rataIpk = $this->dashboardService->getRataIpkPerAngkatan();

            // Check if data is found
            if ($rataIpk->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            return $this->successResponse(
                $rataIpk,
                'Rata IPK per angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getRataIpkPerAngkatan');
        }
    }

    /**
     * Grafik Eligible vs Non-Eligible
     * 
     * Mengecek prediksi status kemampuan mahasiswa untuk lulus tepat waktu dalam kriteria 
     * capaian minimal IPK dan pemenuhan mata kuliah wajib (MK Nasional, Prodi, dsb).
     * 
     * @tags Kaprodi - Dashboard
     * @response 200 { "meta": {"status":"success"}, "data": { "total": 100, "eligible": 60, "tidak_eligible": 40 } }
     */
    public function getStatusKelulusan()
    {
        try {
            $statusKelulusan = $this->dashboardService->getStatusKelulusan();
            return $this->successResponse(
                $statusKelulusan,
                'Status kelulusan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getStatusKelulusan');
        }
    }

    /**
     * List Ringkasan Angkatan (Paginated)
     * 
     * Menampilkan daftar progres kelulusan berdasarkan tiap tahun angkatan secara tabular.
     * Menyertakan jumlah mahasiswa yang diprediksi tepat waktu, kritis, dan normal.
     * 
     * @tags Kaprodi - Dashboard
     * @param Request $request
     * @queryParam per_page int Opsional. Jumlah angkatan yang ditampilkan per halaman. Default: 10.
     * @response 200 { "meta": {"status":"success"}, "data": { "current_page": 1, "data": [ { "tahun_masuk": 2021, "total_mahasiswa": 30, "tepat_waktu": 20, "normal": 5, "perhatian": 3, "kritis": 2 } ], "per_page": 10 } }
     */
    public function getTableRingkasanMahasiswa(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $tableRingkasan = $this->dashboardService->getTableRingkasanMahasiswa($perPage);

            // Check if data is found
            if ($tableRingkasan->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            return $this->paginationResponse(
                $tableRingkasan,
                'Tabel ringkasan mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableRingkasanMahasiswa');
        }
    }

    /**
     * Export Excel Ringkasan Angkatan
     * 
     * Menghasilkan file Excel (.xlsx) berbentuk laporan ringkasan per angkatan mahasiswa di prodi 
     * yang sedang dilihat. Mengembalikan URL download public dari file yang digenerate.
     * 
     * @tags Kaprodi - Dashboard
     * @response 200 { "meta": { "status": "success", "message": "File export ringkasan mahasiswa berhasil digenerate" }, "data": { "url": "http://127.0.0.1:8000/storage/exports/Ringkasan_Mahasiswa.xlsx" } }
     */
    public function exportTableRingkasanMahasiswaCsv(Request $request)
    {
        try {
            $tableRingkasan = $this->dashboardService->getTableRingkasanMahasiswaExport();

            if ($tableRingkasan->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            $fileName = 'Ringkasan Mahasiswa ' . date('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\TableRingkasanMahasiswaExport(
                    $tableRingkasan,
                    'Ringkasan Data Mahasiswa per Angkatan',
                    ['Fakultas Ilmu Komputer']
                ),
                $fileName
            );

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTableRingkasanMahasiswaCsv');
        }
    }
}
