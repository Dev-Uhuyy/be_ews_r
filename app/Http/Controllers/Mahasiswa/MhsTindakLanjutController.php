<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\TindakLanjutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mahasiswa - Modul Tindak Lanjut 
 * 
 * Modul ini diakses oleh Mahasiswa untuk mengajukan *Follow-Up* (tindak lanjut) ketika
 * berstatus Kritis/Perhatian. Mahasiswa dapat mengajukan form Rekomitmen atau Pindah Prodi,
 * meng-upload URL dokumen SP (Surat Peringatan), dan melihat history respon dari Kaprodi.
 * 
 * @tags Mahasiswa - Tindak Lanjut
 * @unauthenticated false
 */
class MhsTindakLanjutController extends Controller
{
    protected $tindakLanjutService;

    public function __construct(TindakLanjutService $tindakLanjutService)
    {
        $this->tindakLanjutService = $tindakLanjutService;
    }

    /**
     * Riwayat Pengajuan (History)
     * 
     * Mengambil seluruh data pengajuan tindak lanjut EWS historis (beserta keterangan dan status approval dari Kaprodi).
     * 
     * @response 200 { "data": [ { "id": 1, "kategori": "rekomitmen", "link": "https://drive.google...", "status": "menunggu", "keterangan": null } ] }
     */
    public function index()
    {
        try {
            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->successResponse([], 'Riwayat tindak lanjut berhasil diambil');
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $history = $this->tindakLanjutService->getHistory($mahasiswaId);
            return $this->successResponse($history, 'Riwayat tindak lanjut berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'index');
        }
    }


    /**
     * Card Summary Notifikasi
     * 
     * Mengembalikan ringkasan total pengajuan berstatus: disetujui, ditolak, dan menunggu. 
     * Sangat berguna untuk di-inject ke UI *Badge Notification* Mahasiswa.
     * 
     * @response 200 { "data": { "menunggu": 1, "disetujui": 0, "ditolak": 0 } }
     */
    public function getCardSummary()
    {
        try {
            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->successResponse([
                    'dalam_proses' => 0,
                    'selesai' => 0,
                ], 'Ringkasan tindak lanjut berhasil diambil');
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $summary = $this->tindakLanjutService->getCardSummary($mahasiswaId);
            return $this->successResponse($summary, 'Ringkasan tindak lanjut berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardSummary');
        }
    }

    /**
     * Ajukan Tindak Lanjut Baru (Create)
     * 
     * Membuat tiket pengajuan (rekomitmen akademik / perpindahan program studi) ke sisi Kaprodi.
     * Sistem akan otomatis menolak jika masih ada tiket yang menggantung berstatus "menunggu".
     * 
     * @param Request $request
     * @bodyParam kategori string required Kategori formulir. Example: rekomitmen ATAU pindah_prodi
     * @bodyParam link string required URL/Link drive dokumen PDF bukti komitmen/pernyataan orang tua. Example: https://docs.google.com/viewer
     * @response 200 { "meta": {"status":"success", "message": "Tindak lanjut berhasil diajukan"}, "data": {"id": 2, "status": "menunggu"} }
     * @response 400 { "meta": {"status":"error", "message": "Gagal: Masih ada pengajuan yang belum diproses kaprodi"} }
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'kategori' => 'required|in:rekomitmen,pindah_prodi',
                'link' => 'required|url',
            ]);

            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->errorResponse('Data akademik mahasiswa tidak ditemukan', 404);
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $result = $this->tindakLanjutService->submit($mahasiswaId, $request->only(['kategori', 'link']));

            if ($result['success']) {
                return $this->successResponse($result['data'], $result['message']);
            } else {
                return $this->errorResponse($result['message'], 400);
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'store');
        }
    }

    /**
     * Info Unduhan Template Form
     * 
     * Endpoint pembantu bagi Mahasiswa untuk mendownload/mendapatkan URL statis dokumen PDF template.
     * Template ini harus di-print, ditandatangani, dan di-upload ulang via link Drive saat fungsi `store` (create).
     * 
     * @param string $kategori Jenis form (rekomitmen / pindah_prodi)
     * @response 200 { "data": { "template_url": "http://domain.com/templates/template_rekomitmen.pdf" } }
     */
    public function getTemplate($kategori)
    {
        try {
            if (!in_array($kategori, ['rekomitmen', 'pindah_prodi', 'akademik'])) {
                return $this->errorResponse('Kategori tidak valid', 400);
            }

            // Placeholder for template logic
            // In a real scenario, this would return a static URL or binary file
            return $this->successResponse([
                'template_url' => asset("templates/template_{$kategori}.pdf")
            ], "Info template {$kategori} berhasil diambil");
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTemplate');
        }
    }
}
