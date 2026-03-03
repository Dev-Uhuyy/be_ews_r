<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Services\Dosen\StatistikKelulusanService;
use Illuminate\Http\Request;

/**
 * @tags Dosen - Statistik Kelulusan
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
        try {
            $tahunMasuk = $request->query('tahun_masuk');

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $statistikKelulusan = $this->statistikKelulusanService->getCardStatistikKelulusan($tahunMasuk);

            // Check if data is found when filter is applied
            if ($tahunMasuk && !$statistikKelulusan) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->successResponse(
                $statistikKelulusan,
                'Statistik kelulusan berhasil diambil'
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
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $tableData = $this->statistikKelulusanService->getTableStatistikKelulusan($perPage);

            // Check if data is found
            if ($tableData->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai', 404);
            }

            return $this->paginationResponse($tableData, 'Table statistik kelulusan berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableStatistikKelulusan');
        }
    }
}
