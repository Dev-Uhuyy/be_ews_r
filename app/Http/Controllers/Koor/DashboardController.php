<?php

namespace App\Http\Controllers\Koor;

use App\Services\Koor\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get complete dashboard data (status mahasiswa, rata IPK per angkatan, status kelulusan)
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
     * Get table ringkasan mahasiswa per angkatan
     * Query params:
     *   ?per_page=10 (items per page)
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
     * Export table ringkasan mahasiswa per angkatan ke XLSX
     */
    public function exportTableRingkasanMahasiswaCsv(Request $request)
    {
        try {
            $tableRingkasan = $this->dashboardService->getTableRingkasanMahasiswaExport();

            if ($tableRingkasan->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            $fileName = 'Ringkasan Mahasiswa ' . date('Y-m-d') . '.xlsx';
            $filePath = 'exports/' . $fileName;
            
            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\TableRingkasanMahasiswaExport($tableRingkasan), 
                $filePath, 
                'public'
            );

            return response()->json([
                'meta' => [
                    'status' => 'success',
                    'message' => 'File export ringkasan mahasiswa berhasil digenerate',
                    'timestamp' => now()->toIso8601String()
                ],
                'data' => [
                    'url' => asset('storage/' . $filePath)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTableRingkasanMahasiswaCsv');
        }
    }
}
