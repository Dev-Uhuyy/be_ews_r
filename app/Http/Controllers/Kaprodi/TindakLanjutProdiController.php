<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\Kaprodi\TindakLanjutProdiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\TindakLanjutExport;

/**
 * @tags Kaprodi - Tindak Lanjut Prodi
 */
class TindakLanjutProdiController extends Controller
{
    protected $tindakLanjutProdiService;

    public function __construct(TindakLanjutProdiService $tindakLanjutProdiService)
    {
        $this->tindakLanjutProdiService = $tindakLanjutProdiService;
    }

    /**
     * Get data kartu statistik (dashboard cards)
     */
    public function getCardSummary()
    {
        try {
            $summary = $this->tindakLanjutProdiService->getCardSummary();
            return $this->successResponse($summary, 'Data statistik tindak lanjut berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardSummary');
        }
    }

    /**
     * Export data tindak lanjut mahasiswa ke XLSX
     */
    public function exportCsv(Request $request)
    {
        try {
            $search = $request->query('search');
            $tahunMasuk = $request->query('tahun_masuk');
            $category = $request->query('kategori');
            $status = $request->query('status');

            $data = $this->tindakLanjutProdiService->getTindakLanjutExport($search, $tahunMasuk, $category, $status);

            if ($data->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            $fileName = 'Tindak Lanjut ' . date('Y-m-d') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\TindakLanjutExport($data),
                $filePath,
                'public'
            );

            return $this->successResponse(
                ['url' => asset('storage/' . $filePath)],
                'File export tindak lanjut berhasil digenerate'
            );

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportCsv');
        }
    }

    /**
     * Get data tindak lanjut mahasiswa (tabel)
     * Query params:
     *   ?search=keyword (search by nama/nim)
     *   ?tahun_masuk=2023 (optional)
     *   ?kategori=rekomitmen|pindah_prodi (optional)
     *   ?status=telah_diverifikasi|belum_diverifikasi (optional)
     *   ?per_page=10
     */
    public function getTindakLanjut(Request $request)
    {
        try {
            $search = $request->query('search');
            $tahunMasuk = $request->query('tahun_masuk');
            $category = $request->query('kategori');
            $status = $request->query('status');
            $perPage = $request->query('per_page', 10);

            // Validasi kategori
            if ($category !== null && !in_array(strtolower($category), ['rekomitmen', 'pindah_prodi'])) {
                return $this->errorResponse('Parameter kategori harus berupa "rekomitmen" atau "pindah_prodi"', 400);
            }

            $data = $this->tindakLanjutProdiService->getTindakLanjutData($search, $tahunMasuk, $category, $status, $perPage);

            return $this->paginationResponse(
                $data,
                'Data tindak lanjut prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTindakLanjut');
        }
    }

    /**
     * Update status tindak lanjut mahasiswa
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $status = $request->input('status');

            if (!$status) {
                return $this->errorResponse('Parameter status wajib diisi', 400);
            }

            if (strtolower($status) !== 'telah_diverifikasi') {
                return $this->errorResponse('Parameter status harus berupa "telah_diverifikasi"', 400);
            }

            $result = $this->tindakLanjutProdiService->updateStatus($id, $status);
            if ($result['success']) {
                return $this->successResponse($result['data'], $result['message']);
            } else {
                return $this->errorResponse($result['message'], 404);
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'updateStatus');
        }
    }

    /**
     * Bulk update status tindak lanjut mahasiswa
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $ids = $request->input('ids');
            $status = $request->input('status');

            if (!$ids || !is_array($ids)) {
                return $this->errorResponse('Parameter ids (array) wajib diisi', 400);
            }

            if (!$status) {
                return $this->errorResponse('Parameter status wajib diisi', 400);
            }

            if (strtolower($status) !== 'telah_diverifikasi') {
                return $this->errorResponse('Parameter status harus berupa "telah_diverifikasi"', 400);
            }

            $result = $this->tindakLanjutProdiService->bulkUpdateStatus($ids, $status);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'bulkUpdateStatus');
        }
    }
}
