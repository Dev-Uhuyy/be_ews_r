<?php

namespace App\Http\Controllers\Mahsiswa;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaService;
use Illuminate\Http\Request;

class MahasiswaController extends Controller
{
    protected $mahasiswaService;

    public function __construct(MahasiswaService $mahasiswaService)
    {
        $this->mahasiswaService = $mahasiswaService;
    }

    public function getDashboardMahasiswa()
    {
        try {
            $user = request()->user();
            $dashboardData = $this->mahasiswaService->getDashboardMahasiswa($user->id);

            return $this->successResponse($dashboardData, 'Dashboard mahasiswa berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDashboardMahasiswa');
        }
    }

    public function getCardStatusAkademik()
    {
        try {
            $user = request()->user();
            $statusAkademik = $this->mahasiswaService->getCardStatusAkademik($user->id);

            return $this->successResponse($statusAkademik, 'Status akademik berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardStatusAkademik');
        }
    }

    public function getKhsKrsMahasiswa(Request $request)
    {
        try {
            $user = request()->user();
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $khsKrsData = $this->mahasiswaService->getKhsKrsMahasiswa($user->id, $perPage, $page);

            return $this->paginationResponse(
                $khsKrsData['data'],
                'Data KHS dan KRS mahasiswa berhasil diambil',
                200,
                null,
                $khsKrsData['pagination']
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getKhsKrsMahasiswa');
        }
    }

    public function getDetailKhsKrs($khsKrsId)
    {
        try {
            $user = request()->user();
            $detailKhsKrs = $this->mahasiswaService->getDetailKhsKrs($user->id, $khsKrsId);

            // Check if error returned
            if (isset($detailKhsKrs['error'])) {
                return $this->errorResponse($detailKhsKrs['error'], 404);
            }

            return $this->successResponse($detailKhsKrs, 'Detail KHS dan KRS mahasiswa berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailKhsKrs');
        }
    }

    public function getPeringatan()
    {
        try {
            $user = request()->user();
            $peringatan = $this->mahasiswaService->getPeringatan($user->id);

            return $this->successResponse($peringatan, 'Peringatan berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getPeringatan');
        }
    }
}
