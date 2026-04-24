<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\DetailAngkatanService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Detail Angkatan
 */
class DetailAngkatanController extends Controller
{
    protected $detailAngkatanService;

    public function __construct(DetailAngkatanService $detailAngkatanService)
    {
        $this->detailAngkatanService = $detailAngkatanService;
    }

    /**
     * Get list tahun angkatan dan prodi
     * Query params: prodi_id (optional) - filter by prodi
     */
    public function getTahunAngkatan()
    {
        try {
            $prodiId = request()->query('prodi_id');
            $data = $this->detailAngkatanService->getTahunAngkatan($prodiId);

            return $this->successResponse(
                $data,
                'Tahun angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTahunAngkatan');
        }
    }

    /**
     * Get detail mahasiswa per angkatan
     *
     * URL: GET /api/ews/dekan/detail-angkatan/{tahunMasuk}
     * Query params: prodi_id (optional)
     */
    public function getDetailAngkatan($tahunMasuk)
    {
        try {
            $prodiId = request()->query('prodi_id');

            $data = $this->detailAngkatanService->getDetailAngkatan($tahunMasuk, $prodiId);

            if (empty($data)) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa untuk angkatan tersebut', 404);
            }

            return $this->successResponse(
                $data,
                'Detail angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailAngkatan');
        }
    }
}
