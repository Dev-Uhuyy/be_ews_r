<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\StatistikKelulusanService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Statistik Kelulusan
 */
class DekanStatistikKelulusanController extends Controller
{
    protected $statistikKelulusanService;

    public function __construct(StatistikKelulusanService $statistikKelulusanService)
    {
        $this->statistikKelulusanService = $statistikKelulusanService;
    }

    /**
     * Table Statistik Kelulusan per Prodi
     *
     * Query params: prodi_id (optional)
     *
     * Mengembalikan data list per prodi:
     * - Nama Prodi, Jumlah Mahasiswa
     * - IPK < 2, SKS < 144, Nilai D > 5%, Ada Nilai E
     * - Eligible / Tidak Eligible
     * - IPK rata-rata per prodi
     * - Detail per tahun angkatan
     *
     * @tags Dekan - Statistik Kelulusan
     */
    public function getTableStatistikKelulusan()
    {
        try {
            $prodiId = request()->query('prodi_id');
            $data = $this->statistikKelulusanService->getTableStatistikKelulusanPerProdiWithTahun($prodiId);

            return $this->successResponse(
                $data,
                'Tabel statistik kelulusan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableStatistikKelulusan');
        }
    }
}
