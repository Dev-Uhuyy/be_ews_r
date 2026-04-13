<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            $user = \Illuminate\Support\Facades\Auth::user();
            
            // Jika ada explicit filter prodi_id, tampilkan 1 prodi saja
            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $dashboard = [
                    'status_mahasiswa' => $this->dashboardService->getStatusMahasiswa(),
                    'rata_ipk_per_angkatan' => $this->dashboardService->getRataIpkPerAngkatan(),
                    'status_kelulusan' => $this->dashboardService->getStatusKelulusan(),
                ];
                return $this->successResponse($dashboard, 'Dashboard data berhasil diambil');
            }

            // Jika tidak difilter, tampilkan "gabungan per prodi"
            $prodis = \App\Models\Prodi::all();
            $dashboardData = [];

            foreach ($prodis as $prodi) {
                // Pinjam (spoof) scope prodi_id ke request config secara iteratif
                request()->merge(['prodi_id' => $prodi->id]);

                $dashboardData[] = [
                    'prodi' => [
                        'id' => $prodi->id,
                        'kode' => $prodi->kode_prodi,
                        'nama' => $prodi->nama,
                    ],
                    'status_mahasiswa' => $this->dashboardService->getStatusMahasiswa(),
                    'rata_ipk_per_angkatan' => $this->dashboardService->getRataIpkPerAngkatan(),
                    'status_kelulusan' => $this->dashboardService->getStatusKelulusan(),
                ];
            }

            // Clean up spoofed request param
            request()->request->remove('prodi_id');

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
            $filePath = 'exports/' . $fileName;

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\TableRingkasanMahasiswaExport($tableRingkasan),
                $filePath,
                'public'
            );

            return $this->successResponse(
                ['url' => asset('storage/' . $filePath)],
                'File export ringkasan mahasiswa berhasil digenerate'
            );

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTableRingkasanMahasiswaCsv');
        }
    }
}
