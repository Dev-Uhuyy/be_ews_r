<?php

namespace App\Http\Controllers\Koor;

use App\Services\Koor\TindakLanjutProdiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TindakLanjutProdiController extends Controller
{
    protected $tindakLanjutProdiService;

    public function __construct(TindakLanjutProdiService $tindakLanjutProdiService)
    {
        $this->tindakLanjutProdiService = $tindakLanjutProdiService;
    }

    /**
     * Export data surat rekomitmen mahasiswa ke XLSX
     */
    public function exportSuratRekomitmenCsv(Request $request)
    {
        try {
            $search = $request->query('search');
            $tahunMasuk = $request->query('tahun_masuk');
            $statusRekomitmen = $request->query('status_rekomitmen');

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            // Validasi status_rekomitmen jika diberikan
            if ($statusRekomitmen !== null && !in_array(strtolower($statusRekomitmen), ['diterima', 'ditolak', 'belum diverifikasi'])) {
                return $this->errorResponse('Parameter status_rekomitmen harus berupa "diterima", "ditolak", atau "belum diverifikasi"', 400);
            }

            $suratRekomitmen = $this->tindakLanjutProdiService->getSuratRekomitmenExport($search, $tahunMasuk, $statusRekomitmen);

            if ($suratRekomitmen->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            $fileName = 'Surat Rekomitmen ' . date('Y-m-d') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\SuratRekomitmenExport($suratRekomitmen), 
                $filePath, 
                'public'
            );

            return response()->json([
                'meta' => [
                    'status' => 'success',
                    'message' => 'File export surat rekomitmen berhasil digenerate',
                    'timestamp' => now()->toIso8601String()
                ],
                'data' => [
                    'url' => asset('storage/' . $filePath)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportSuratRekomitmenCsv');
        }
    }

    /**
     * Get data surat rekomitmen mahasiswa
     * Query params:
     *   ?search=keyword (search by id_tiket)
     *   ?tahun_masuk=2023 (optional - filter by angkatan)
     *   ?status_rekomitmen=diterima|ditolak|belum diverifikasi (optional - filter by status tindak lanjut)
     *   ?per_page=10 (items per page)
     *
     * Returns: id_tiket, nama, nim, tanggal_pengajuan, dosen_wali, status_tindak_lanjut, link_rekomitmen
     */
    public function getSuratRekomitmen(Request $request)
    {
        try {
            $search = $request->query('search');
            $tahunMasuk = $request->query('tahun_masuk');
            $statusRekomitmen = $request->query('status_rekomitmen');
            $perPage = $request->query('per_page', 10);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            // Validasi status_rekomitmen jika diberikan
            if ($statusRekomitmen !== null && !in_array(strtolower($statusRekomitmen), ['diterima', 'ditolak', 'belum diverifikasi'])) {
                return $this->errorResponse('Parameter status_rekomitmen harus berupa "diterima", "ditolak", atau "belum diverifikasi"', 400);
            }

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $suratRekomitmen = $this->tindakLanjutProdiService->getSuratRekomitmen($search, $tahunMasuk, $statusRekomitmen, $perPage);

            // Check if data is found
            if ($suratRekomitmen->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->paginationResponse(
                $suratRekomitmen,
                'Data surat rekomitmen tindak lanjut prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getSuratRekomitmen');
        }
    }

    /**
     * Update status rekomitmen mahasiswa
     * Path params: {id_rekomitmen}
     * Body: {"status_rekomitmen": "diterima|ditolak"}
     */
    public function updateStatusRekomitmen(Request $request, $id_rekomitmen)
    {
        try {
            $status = $request->input('status_rekomitmen');

            // Validasi status_rekomitmen ada
            if (!$status) {
                return $this->errorResponse('Parameter status_rekomitmen wajib diisi', 400);
            }

            // Validasi nilai status_rekomitmen
            if (!in_array(strtolower($status), ['diterima', 'ditolak'])) {
                return $this->errorResponse('Parameter status_rekomitmen harus berupa "diterima" atau "ditolak"', 400);
            }

            $result = $this->tindakLanjutProdiService->updateStatusRekomitmen($id_rekomitmen, $status);
            if ($result['success']) {
                return $this->successResponse(null, 'Status rekomitmen berhasil diperbarui');
            } else {
                return $this->errorResponse($result['message'], 404);
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'updateStatusRekomitmen');
        }
    }
}
