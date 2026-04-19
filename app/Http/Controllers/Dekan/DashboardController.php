<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Dekan - Dashboard
 */
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get all dashboard data for Dekan in a single call (no N+1)
     * Uses batch queries to get all prodis data efficiently
     */
    public function getAllProdiDashboard()
    {
        $user = Auth::user();
        $prodis = \App\Models\Prodi::all();

        if ($prodis->isEmpty()) {
            return [];
        }

        $prodiIds = $prodis->pluck('id')->toArray();
        $dashboardData = $this->dashboardService->getDashboardBatch($prodiIds);

        // Return as array indexed by prodi
        return array_values($dashboardData);
    }

    /**
     * Dashboard Fakultas (Per Prodi)
     *
     * Mengembalikan data agregasi matriks lengkap namun **dikelompokkan per program studi**.
     * Dekan dapat langsung melihat perbandingan status keaktifan, tren IPK angkatan,
     * dan kelayakan lulus antar program studi secara sekaligus (gabungan).
     *
     * @tags Dekan - Dashboard
     * @response 200 { "meta": {"status":"success"}, "data": [ { "prodi": {"id": 1, "nama": "Teknik Informatika"}, "status_mahasiswa": {}, "rata_ipk_per_angkatan": [], "status_kelulusan": {} } ] }
     */
    public function getDashboard()
    {
        try {
            $user = Auth::user();

            // Jika ada explicit filter prodi_id, tampilkan 1 prodi saja
            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $prodiId = request('prodi_id');
                $dashboardData = $this->dashboardService->getDashboardBatch([$prodiId]);

                if (empty($dashboardData)) {
                    return $this->errorResponse('Prodi tidak ditemukan', 404);
                }

                return $this->successResponse(
                    array_values($dashboardData)[0],
                    'Dashboard data berhasil diambil'
                );
            }

            // Jika tidak difilter, tampilkan "gabungan per prodi" dengan batch query (no N+1)
            $dashboardData = $this->getAllProdiDashboard();

            return $this->successResponse(
                $dashboardData,
                'Dashboard Fakultas per Prodi berhasil diambil'
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
            $perPage = $request->query('per_page', 50);

            $tableRingkasan = $this->dashboardService->getTableRingkasanMahasiswa($perPage);

            if ($tableRingkasan->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            // Transformasi menjadi Grouped by Prodi
            $groupedData = collect($tableRingkasan->items())->groupBy('kode_prodi')->map(function ($items) {
                $prodiInfo = [
                    'nama_prodi' => $items->first()->nama_prodi,
                    'kode_prodi' => $items->first()->kode_prodi,
                ];

                $detailAngkatan = $items->map(function ($item) {
                    $data = $item->toArray();
                    unset($data['nama_prodi']);
                    unset($data['kode_prodi']);
                    return $data;
                })->values();

                // Hitung total akumulasi per prodi
                $totals = [
                    'jumlah_mahasiswa' => $detailAngkatan->sum('jumlah_mahasiswa'),
                    'aktif' => $detailAngkatan->sum('aktif'),
                    'cuti' => $detailAngkatan->sum('cuti'),
                    'mangkir' => $detailAngkatan->sum('mangkir'),
                    'rata_ipk' => round($detailAngkatan->avg('rata_ipk'), 2),
                    'tepat_waktu' => $detailAngkatan->sum('tepat_waktu'),
                    'normal' => $detailAngkatan->sum('normal'),
                    'perhatian' => $detailAngkatan->sum('perhatian'),
                    'kritis' => $detailAngkatan->sum('kritis'),
                ];

                return array_merge($prodiInfo, [
                    'total_angkatan' => $totals,
                    'detail_angkatan' => $detailAngkatan
                ]);
            })->values();

            return $this->paginationResponse(
                $groupedData,
                'Tabel ringkasan mahasiswa berhasil diambil',
                200,
                null,
                [
                    'total' => $tableRingkasan->total(),
                    'per_page' => $tableRingkasan->perPage(),
                    'current_page' => $tableRingkasan->currentPage(),
                ]
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
