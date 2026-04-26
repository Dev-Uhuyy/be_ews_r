<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\Kaprodi\KaprodiMahasiswaListService;
use App\Services\Kaprodi\KaprodiNilaiMahasiswaService;
use App\Http\Controllers\Controller;

/**
 * @tags Kaprodi - Mahasiswa List
 */
class KaprodiMahasiswaController extends Controller
{
    protected $mahasiswaListService;
    protected $nilaiMahasiswaService;

    public function __construct(
        KaprodiMahasiswaListService  $mahasiswaListService,
        KaprodiNilaiMahasiswaService $nilaiMahasiswaService
    ) {
        $this->mahasiswaListService  = $mahasiswaListService;
        $this->nilaiMahasiswaService = $nilaiMahasiswaService;
    }

    /**
     * Get list mahasiswa dengan filter fleksibel
     *
     * Query params:
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     * - ipk_max: IPK kurang dari nilai (contoh: 2.0) (optional)
     * - sks_max: SKS lulus kurang dari nilai (contoh: 144) (optional)
     * - has_nilai_d: true/false - memiliki nilai D melebihi batas (optional)
     * - has_nilai_e: true/false - memiliki nilai E (optional)
     * - status_kelulusan: 'eligible' atau 'noneligible' (optional)
     * - ews_status: 'tepat_waktu', 'normal', 'perhatian', 'kritis' (optional)
     *
     * Contoh:
     * - GET /ews/kaprodi/mahasiswa/list
     * - GET /ews/kaprodi/mahasiswa/list?tahun_masuk=2025
     * - GET /ews/kaprodi/mahasiswa/list?sks_max=144&status_kelulusan=noneligible
     *
     * @tags Kaprodi - Mahasiswa List
     */
    public function getMahasiswaList()
    {
        try {
            $filters = request()->query();
            $data = $this->mahasiswaListService->getMahasiswaList($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaList');
        }
    }

    /**
     * Get list mahasiswa berdasarkan status_mahasiswa dan/atau ews_status
     *
     * Query params:
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     * - status_mahasiswa: 'aktif', 'cuti', 'mangkir' (optional)
     * - ews_status: 'tepat_waktu', 'normal', 'perhatian', 'kritis' (optional)
     *
     * Contoh:
     * - GET /ews/kaprodi/mahasiswa/by-status?status_mahasiswa=aktif
     * - GET /ews/kaprodi/mahasiswa/by-status?ews_status=kritis
     * - GET /ews/kaprodi/mahasiswa/by-status?status_mahasiswa=aktif&ews_status=kritis
     * - GET /ews/kaprodi/mahasiswa/by-status?tahun_masuk=2025&ews_status=perhatian
     *
     * @tags Kaprodi - Mahasiswa List
     */
    public function getMahasiswaByStatus()
    {
        try {
            $filters = request()->query();
            $data = $this->mahasiswaListService->getMahasiswaByStatus($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa berdasarkan status berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaByStatus');
        }
    }

    /**
     * Get list mahasiswa dengan data lengkap Nilai D, Nilai E, SKS tidak lulus, dan MK kurang
     * Scoped ke prodi Kaprodi yang sedang login.
     *
     * Query params:
     * - tahun_masuk        : Filter berdasarkan tahun angkatan (optional)
     * - has_nilai_d        : true/false — memiliki nilai D melebihi batas (optional)
     * - has_nilai_e        : true/false — memiliki nilai E (optional)
     * - mk_nasional_kurang : true/false — belum lulus MK nasional (optional)
     * - mk_fakultas_kurang : true/false — belum lulus MK fakultas (optional)
     * - mk_prodi_kurang    : true/false — belum lulus MK prodi (optional)
     * - status_kelulusan   : 'eligible' atau 'noneligible' (optional)
     * - mahasiswa_id       : ID mahasiswa spesifik, menonaktifkan pagination (optional)
     * - search             : Pencarian berdasarkan nama atau NIM (optional)
     * - per_page           : Items per halaman (default 10)
     *
     * Contoh:
     * - GET /ews/kaprodi/mahasiswa/nilai-detail
     * - GET /ews/kaprodi/mahasiswa/nilai-detail?tahun_masuk=2023
     * - GET /ews/kaprodi/mahasiswa/nilai-detail?has_nilai_d=true
     * - GET /ews/kaprodi/mahasiswa/nilai-detail?mahasiswa_id=5
     *
     * @tags Kaprodi - Mahasiswa List
     */
    public function getNilaiMahasiswaList()
    {
        try {
            $filters = request()->query();
            $perPage = (int) request()->query('per_page', 10);
            $search  = request()->query('search');

            $data = $this->nilaiMahasiswaService->getNilaiMahasiswaList($filters, $perPage, $search);

            return $this->successResponse(
                $data,
                'List nilai mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getNilaiMahasiswaList');
        }
    }
}