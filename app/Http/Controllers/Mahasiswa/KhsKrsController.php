<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\KhsKrsService;
use Illuminate\Http\Request;

/**
 * @tags Mahasiswa - KHS/KRS
 */
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

            // Handle case where user has no KHS records (null returned)
            if ($khsKrsData === null) {
                return $this->paginationResponse(
                    [],
                    'Data KHS dan KRS mahasiswa berhasil diambil',
                    200,
                    null,
                    [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                        'from' => 0,
                        'to' => 0,
                    ]
                );
            }

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

    public function exportKhsKrsCsv()
    {
        try {
            $user = request()->user();
            $khsKrsData = $this->khsKrsService->getKhsKrsMahasiswa($user->id, 100, 1);

            $data = [];
            if ($khsKrsData !== null && !empty($khsKrsData['data'])) {
                foreach ($khsKrsData['data'] as $item) {
                    $data[] = [
                        'semester' => $item->semester ?? '',
                        'kode' => $item->kode ?? '',
                        'nama' => $item->nama ?? '',
                        'sks' => $item->sks ?? '',
                        'nilai_huruf' => $item->nilai_akhir_huruf ?? '',
                        'nilai_angka' => $item->nilai_akhir_angka ?? '',
                        'status' => $item->status ?? '',
                    ];
                }
            }

            $fileName = 'KHS KRS ' . date('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\KhsKrsExport($data),
                $fileName
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportKhsKrsCsv');
        }
    }
}
