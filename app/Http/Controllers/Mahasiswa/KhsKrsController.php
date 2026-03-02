<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\KhsKrsService;
use Illuminate\Http\Request;

class KhsKrsController extends Controller
{
    protected $khsKrsService;

    public function __construct(KhsKrsService $khsKrsService)
    {
        $this->khsKrsService = $khsKrsService;
    }

    public function getKhsKrsMahasiswa(Request $request)
    {
        try {
            $user = request()->user();
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $khsKrsData = $this->khsKrsService->getKhsKrsMahasiswa($user->id, $perPage, $page);

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
            $detailKhsKrs = $this->khsKrsService->getDetailKhsKrs($user->id, $khsKrsId);

            // Check if error returned
            if (isset($detailKhsKrs['error'])) {
                return $this->errorResponse($detailKhsKrs['error'], 404);
            }

            return $this->successResponse($detailKhsKrs, 'Detail KHS dan KRS mahasiswa berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailKhsKrs');
        }
    }
}
