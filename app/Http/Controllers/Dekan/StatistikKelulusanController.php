<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\StatistikKelulusanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @tags Dekan - Statistik Kelulusan
 */
class StatistikKelulusanController extends Controller
{
    protected $statistikKelulusanService;

    public function __construct(StatistikKelulusanService $statistikKelulusanService)
    {
        $this->statistikKelulusanService = $statistikKelulusanService;
    }

    public function getCardStatistikKelulusan(Request $request)
    {
        //dengan filter angkatan (tahun_masuk) dan exclude mahasiswa yang sudah lulus dan DO
        try {
            $tahunMasuk = $request->query('tahun_masuk');

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            // Jika ada specific filter prodi_id (misal ditekan dari Dropdown oleh dekan)
            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $statistikKelulusan = $this->statistikKelulusanService->getCardStatistikKelulusan($tahunMasuk);

                if ($tahunMasuk && !$statistikKelulusan) {
                    return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
                }

                return $this->successResponse($statistikKelulusan, 'Statistik kelulusan berhasil diambil');
            }

            // Jika tidak, tampilkan perbandingan seluruh Prodi (NO N+1 - batch query)
            $prodis = \App\Models\Prodi::all();
            $prodiIds = $prodis->pluck('id')->toArray();

            $batchData = $this->statistikKelulusanService->getCardStatistikKelulusanBatch($prodiIds, $tahunMasuk);

            $dataGabungan = [];
            foreach ($prodis as $prodi) {
                $dataGabungan[] = [
                    'prodi' => [
                        'id' => $prodi->id,
                        'kode' => $prodi->kode_prodi,
                        'nama' => $prodi->nama,
                    ],
                    'statistik' => $batchData[$prodi->id] ?? [
                        'eligible' => 0, 'noneligible' => 0, 'aktif' => 0, 'mangkir' => 0, 'cuti' => 0,
                        'ipk_kurang_dari_2_5' => 0, 'ipk_antara_2_5_3' => 0, 'ipk_lebih_dari_3' => 0,
                        'mk_nasional' => 0, 'mk_fakultas' => 0, 'mk_prodi' => 0
                    ]
                ];
            }

            return $this->successResponse(
                $dataGabungan,
                'Statistik kelulusan per Prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardStatistikKelulusan');
        }
    }

    /**
     * Get table statistik kelulusan per angkatan
     * Query params:
     *   ?per_page=10 (items per page)
     */
    public function getTableStatistikKelulusan(Request $request)
    {
        try {
            // Kita ambil data flat per tahun (paginate)
            $perPage = $request->query('per_page', 50); // Default diperbesar agar prodi tidak terpotong sering

            $tableData = $this->statistikKelulusanService->getTableStatistikKelulusan($perPage);

            if ($tableData->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai', 404);
            }

            // Transformasi menjadi Grouped by Prodi
            $groupedData = collect($tableData->items())->groupBy('kode_prodi')->map(function ($items) {
                $prodiInfo = [
                    'nama_prodi' => $items->first()->nama_prodi,
                    'kode_prodi' => $items->first()->kode_prodi,
                ];

                $detailAngkatan = $items->map(function ($item) {
                    // Gunakan toArray() agar internal Eloquent tidak ikut terpapar
                    $data = $item->toArray();
                    unset($data['nama_prodi']);
                    unset($data['kode_prodi']);
                    return $data;
                })->values();

                // Hitung total akumulasi per prodi
                $totals = [
                    'jumlah_mahasiswa' => $detailAngkatan->sum('jumlah_mahasiswa'),
                    'ipk_kurang_dari_2' => $detailAngkatan->sum('ipk_kurang_dari_2'),
                    'sks_kurang_dari_144' => $detailAngkatan->sum('sks_kurang_dari_144'),
                    'nilai_d_melebihi_batas' => $detailAngkatan->sum('nilai_d_melebihi_batas'),
                    'nilai_e' => $detailAngkatan->sum('nilai_e'),
                    'eligible' => $detailAngkatan->sum('eligible'),
                    'noneligible' => $detailAngkatan->sum('noneligible'),
                    'ipk_rata2' => round($detailAngkatan->avg('ipk_rata2'), 2),
                ];

                return array_merge($prodiInfo, [
                    'total_statistik' => $totals,
                    'detail_statistik' => $detailAngkatan
                ]);
            })->values();

            return $this->paginationResponse(
                $groupedData, 
                'Table statistik kelulusan berhasil diambil',
                200,
                null,
                [
                    'total' => $tableData->total(),
                    'per_page' => $tableData->perPage(),
                    'current_page' => $tableData->currentPage(),
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableStatistikKelulusan');
        }
    }
}
