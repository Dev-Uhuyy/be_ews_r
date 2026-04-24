<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\MahasiswaListService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Mahasiswa List
 */
class MahasiswaListController extends Controller
{
    protected $mahasiswaListService;

    public function __construct(MahasiswaListService $mahasiswaListService)
    {
        $this->mahasiswaListService = $mahasiswaListService;
    }

    /**
     * Get list mahasiswa dengan filter fleksibel
     *
     * Query params:
     * - prodi_id: Filter berdasarkan ID Prodi
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     * - ipk_max: IPK kurang dari nilai (contoh: 2.0)
     * - sks_max: SKS lulus kurang dari nilai (contoh: 144)
     * - has_nilai_d: true/false - memiliki nilai D melebihi batas
     * - has_nilai_e: true/false - memiliki nilai E
     * - status_kelulusan: 'eligible' atau 'noneligible'
     * - ews_status: 'tepat_waktu', 'normal', 'perhatian', 'kritis'
     *
     * Contoh:
     * - GET /mahasiswa/list?prodi_id=1
     * - GET /mahasiswa/list?ipk_max=2
     * - GET /mahasiswa/list?status_kelulusan=noneligible
     * - GET /mahasiswa/list?prodi_id=1&tahun_masuk=2023&has_nilai_e=true
     *
     * @tags Dekan - Mahasiswa List
     */
    public function getMahasiswaList()
    {
        try {
            $filters = request()->query();
            $data = $this->mahasiswaListService->getMahasiswaList($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaList');
        }
    }

    /**
     * Get list kriteria/filter yang tersedia
     *
     * @tags Dekan - Mahasiswa List
     */
    public function getAvailableKriteria()
    {
        try {
            $data = $this->mahasiswaListService->getAvailableKriteria();

            return $this->successResponse(
                $data,
                'Kriteria filter berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getAvailableKriteria');
        }
    }
}
