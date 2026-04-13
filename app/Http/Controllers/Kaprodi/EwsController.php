<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Services\Kaprodi\EwsService;
use App\Services\Kaprodi\StatusMahasiswaService;
use App\Jobs\RecalculateAllEwsJob;
use App\Models\AkademikMahasiswa;
use Illuminate\Http\Request;

/**
 * EWS Core Management (Kaprodi Level)
 * 
 * Controller ini mengatur fungsi utama EWS seperti summary distribusi peringatan akademik,
 * serta manual recalculation EWS untuk sinkronisasi nilai terbaru mahasiswa di tingkat Prodi.
 * Hanya pengguna dengan otorisasi `kaprodi` atau `dekan` yang memiliki akses ke modul ini.
 * 
 * @tags Kaprodi - EWS Core Actions
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
     * Hitung Distribusi EWS (Pie Chart / Total)
     * 
     * Endpoint ini mengembalikan matriks agregasi status peringatan EWS secara komprehensif, 
     * mengkategorikan populasi mahasiswa prodi ke dalam kelompok: lulus, tepat_waktu, normal, 
     * perhatian, kritis, do, mangkir, dan cuti.
     * 
     * @tags Kaprodi - EWS Core Actions
     * @param Request $request
     * @queryParam tahun_masuk string Opsional. Filter data distribusi berdasarkan tahun masuk (misal: 2023). Jika tidak diisi, maka menghitung seluruh populasi.
     * @response 200 { "meta": { "status": "success", "message": "..." }, "data": { "lulus": 10, "tepat_waktu": 5, "kritis": 1 } }
     */
    public function getDistribusiStatusEws(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $distribusi = $this->statusMahasiswaService->getDistribusiStatusEws($tahunMasuk);

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
     * Recalculate 1 Mahasiswa
     * 
     * Memicu perhitungan ulang profil akademik satu mahasiswa secara spesifik (Real-Time).
     * Sangat berguna jika update batch terlalu lama atau staf butuh mengkalkulasi satu mahasiswa 
     * setelah KHS baru dirilis SIADIN. Mengembalikan detail EWS terbaru mahasiswa tersebut.
     * 
     * @tags Kaprodi - EWS Core Actions
     * @param int $mahasiswaId ID mahasiswa yang di tuju pada database. (misal: 10)
     * @response 200 { "meta": { "status": "success", "message": "..." }, "data": { "id": 10, "status_ews": "kritis" } }
     * @response 404 { "meta": { "status": "error", "message": "Mahasiswa tidak ditemukan" }, "data": {} }
     */
    public function recalculateMahasiswaStatus($mahasiswaId)
    {
        try {
            // Validasi mahasiswa_id
            if (!is_numeric($mahasiswaId) || $mahasiswaId < 1) {
                return $this->errorResponse('Parameter mahasiswa_id harus berupa angka yang valid', 400);
            }

            // Cari akademik mahasiswa by mahasiswa_id dengan scope prodi agar kaprodi A tidak bisa recalculate mhs kaprodi B
            $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)
                ->whereHas('mahasiswa', function($query) {
                    $user = \Illuminate\Support\Facades\Auth::user();
                    if ($user && $user->hasRole('kaprodi')) {
                        $query->where('prodi_id', $user->prodi_id);
                    }
                })
                ->with('mahasiswa.user')
                ->first();

            if (!$akademik) {
                return $this->errorResponse('Mahasiswa tidak ditemukan', 404);
            }

            // Update status EWS
            $result = $this->ewsService->updateStatus($akademik);

            // Get detail mahasiswa lengkap setelah recalculate
            $detailMahasiswa = $this->statusMahasiswaService->getDetailMahasiswa($mahasiswaId);

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
     * Mass Recalculate (Background Job)
     * 
     * Fungsi Asynchronous (menjalankan Queue Job background worker). 
     * Memerintahkan sistem untuk menghitung ulang seluruh populasi Mahasiswa (1000+ data) 
     * dilingkup prodi saat ini terhadap formula prediksi EWS. Karena berat, respon
     * ini hanya bertindak sebagai "trigger".
     * 
     * @tags Kaprodi - EWS Core Actions
     * @response 200 { "meta": { "status": "success", "message": "Proses recalculate semua status EWS dimulai di background" } }
     */
    public function recalculateAllStatus()
    {
        try {
            // Retrieve prodiId based on role
            $prodiId = null;
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user && $user->hasRole('kaprodi')) {
                $prodiId = $user->prodi_id;
            } elseif ($user && $user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
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
