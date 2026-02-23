<?php

namespace App\Http\Controllers\Koor;

use App\Http\Controllers\Controller;
use App\Services\EwsService;
use App\Services\KoorService;
use App\Jobs\RecalculateAllEwsJob;
use App\Models\AkademikMahasiswa;
use Illuminate\Http\Request;

class EwsController extends Controller
{
    protected $ewsService;
    protected $koorService;

    public function __construct(EwsService $ewsService, KoorService $koorService)
    {
        $this->ewsService = $ewsService;
        $this->koorService = $koorService;
    }

    /**
     * Get distribusi status EWS
     * Query params: ?tahun_masuk=2023 (optional, untuk filter by angkatan)
     */
    public function getDistribusiStatusEws(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $distribusi = $this->koorService->getDistribusiStatusEws($tahunMasuk);

            // Check if any mahasiswa found when filter is applied
            if ($tahunMasuk && array_sum($distribusi) == 0) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->successResponse(
                $distribusi,
                'Distribusi status EWS berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDistribusiStatusEws');
        }
    }

    /**
     * Recalculate status EWS untuk 1 mahasiswa (real-time)
     * @param int $mahasiswaId - ID dari tabel mahasiswa
     */
    public function recalculateMahasiswaStatus($mahasiswaId)
    {
        try {
            // Validasi mahasiswa_id
            if (!is_numeric($mahasiswaId) || $mahasiswaId < 1) {
                return $this->errorResponse('Parameter mahasiswa_id harus berupa angka yang valid', 400);
            }

            // Cari akademik mahasiswa by mahasiswa_id
            $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)
                ->with('mahasiswa.user')
                ->first();

            if (!$akademik) {
                return $this->errorResponse('Mahasiswa tidak ditemukan', 404);
            }

            // Update status EWS
            $result = $this->ewsService->updateStatus($akademik);

            // Get detail mahasiswa lengkap setelah recalculate
            $detailMahasiswa = $this->koorService->getDetailMahasiswa($mahasiswaId);

            if (!$detailMahasiswa) {
                return $this->errorResponse('Detail mahasiswa tidak ditemukan', 404);
            }

            // Return detail mahasiswa lengkap dengan status yang baru
            return $this->successResponse(
                $detailMahasiswa,
                'Status EWS berhasil di-recalculate'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'recalculateMahasiswaStatus');
        }
    }

    /**
     * Trigger bulk recalculate (background job)
     */
    public function recalculateAllStatus()
    {
        try {
            // Dispatch job to background
            RecalculateAllEwsJob::dispatch();

            return $this->successResponse(
                null,
                'Proses recalculate semua status EWS dimulai di background'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'recalculateAllStatus');
        }
    }
}
