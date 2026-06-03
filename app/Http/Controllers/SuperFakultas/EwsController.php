<?php

namespace App\Http\Controllers\SuperFakultas;

use App\Http\Controllers\Controller;
use App\Jobs\RecalculateAllEwsJob;
use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\SuperFakultas\EwsService;
use App\Services\SuperFakultas\StatusMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @tags SuperFakultas - EWS Management
 */
class EwsController extends Controller
{
    protected $ewsService;

    protected $statusMahasiswaService;

    public function __construct(EwsService $ewsService, StatusMahasiswaService $statusMahasiswaService)
    {
        $this->ewsService = $ewsService;
        $this->statusMahasiswaService = $statusMahasiswaService;
    }

    /**
     * Get distribusi status EWS (Pie Chart / Perbandingan Prodi)
     * Query params: ?tahun_masuk=2023 (optional, untuk filter by angkatan)
     */
    public function getDistribusiStatusEws(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (! is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            // Jika ada filter explicit prodi_id
            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $distribusi = $this->statusMahasiswaService->getDistribusiStatusEws($tahunMasuk);

                if ($tahunMasuk && array_sum($distribusi) == 0) {
                    return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
                }

                return $this->successResponse($distribusi, 'Distribusi status EWS berhasil diambil');
            }

            // Jika tidak ada filter, tampilkan gabungan per prodi
            $prodis = Prodi::all();
            $dataGabungan = [];

            foreach ($prodis as $prodi) {
                request()->merge(['prodi_id' => $prodi->id]);
                $dist = $this->statusMahasiswaService->getDistribusiStatusEws($tahunMasuk);

                $dataGabungan[] = [
                    'prodi' => [
                        'id' => $prodi->id,
                        'kode' => $prodi->kode_prodi,
                        'nama' => $prodi->nama,
                    ],
                    'distribusi' => $dist,
                ];
            }

            // Clean up
            request()->request->remove('prodi_id');

            return $this->successResponse(
                $dataGabungan,
                'Distribusi status EWS per Prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDistribusiStatusEws');
        }
    }

    /**
     * Recalculate status EWS untuk 1 mahasiswa (real-time)
     *
     * @param  int  $mahasiswaId  - ID dari tabel mahasiswa
     */
    public function recalculateMahasiswaStatus($mahasiswaId)
    {
        try {
            // Validasi mahasiswa_id
            if (! is_numeric($mahasiswaId) || $mahasiswaId < 1) {
                return $this->errorResponse('Parameter mahasiswa_id harus berupa angka yang valid', 400);
            }

            // Cari akademik mahasiswa by mahasiswa_id dengan scope prodi agar SuperFakultas A tidak bisa recalculate mhs SuperFakultas B
            $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)
                ->whereHas('mahasiswa', function ($query) {
                    $user = Auth::user();
                    if ($user && $user->hasRole('super_fakultas')) {
                        $query->where('prodi_id', $user->prodi_id);
                    }
                })
                ->with('mahasiswa.user')
                ->first();

            if (! $akademik) {
                return $this->errorResponse('Mahasiswa tidak ditemukan', 404);
            }

            // Update status EWS
            $result = $this->ewsService->updateStatus($akademik);

            // Get detail mahasiswa lengkap setelah recalculate
            $detailMahasiswa = $this->statusMahasiswaService->getDetailMahasiswa($mahasiswaId);

            if (! $detailMahasiswa) {
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
            // Retrieve prodiId based on role
            $prodiId = null;
            $user = Auth::user();
            if ($user && $user->hasRole('super_fakultas')) {
                $prodiId = $user->prodi_id;
            } elseif ($user && $user->hasRole('super_fakultas') && request()->has('prodi_id') && request('prodi_id') != '') {
                $prodiId = request('prodi_id');
            }

            // Dispatch job to background with optional prodiId filter
            RecalculateAllEwsJob::dispatch($prodiId);

            return $this->successResponse(
                null,
                'Proses recalculate semua status EWS dimulai di background'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'recalculateAllStatus');
        }
    }
}
