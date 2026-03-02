<?php

namespace App\Http\Controllers\Koor;

use App\Services\Koor\CapaianMahasiswaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CapaianMahasiswaController extends Controller
{
    protected $capaianMahasiswaService;

    public function __construct(CapaianMahasiswaService $capaianMahasiswaService)
    {
        $this->capaianMahasiswaService = $capaianMahasiswaService;
    }

    /**
     * Get tren IPS all mahasiswa (tren naik/turun, mk_gagal, mk_ulang)
     * Query params:
     *   ?tahun_masuk=2023 (optional - filter by angkatan)
     */
    public function getTrenIPSAll(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $trenIps = $this->capaianMahasiswaService->getTrenIPSAll($tahunMasuk);

            // Check if data is found when filter is applied
            if ($tahunMasuk && empty($trenIps)) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->successResponse(
                $trenIps,
                'Tren IPS semua mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTrenIPSAll');
        }
    }

    /**
     * Get capaian mahasiswa (rata-rata IPS, mahasiswa naik/turun IP)
     * Query params:
     *   ?tahun_masuk=2023 (optional - filter by angkatan)
     */
    public function getCardCapaianMahasiswa(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $capaian = $this->capaianMahasiswaService->getCardCapaianMahasiswa($tahunMasuk);

            // Check if data is found when filter is applied
            if ($tahunMasuk && $capaian['total_mahasiswa'] == 0) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->successResponse(
                $capaian,
                'Capaian mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardCapaianMahasiswa');
        }
    }

    public function getTopTenMKGagalAll()
    {
        try {
            $topTenMKGagal = $this->capaianMahasiswaService->getTopTenMKGagalAll();

            // Check if data is found
            if (empty($topTenMKGagal) || count($topTenMKGagal) == 0) {
                return $this->errorResponse('Tidak ditemukan data mata kuliah dengan nilai E', 404);
            }

            return $this->successResponse(
                $topTenMKGagal,
                'Top 10 MK gagal all time dari semua mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTopTenMKGagalAll');
        }
    }

    /**
     * Get mahasiswa dengan MK gagal (nilai E terakhir)
     * Query params:
     *   ?search=keyword (search by nama)
     *   ?per_page=10 (items per page)
     *   ?nama_matkul=keyword (filter by nama mata kuliah)
     *   ?kode_kelompok=A11.4501 (filter by kode kelompok)
     */
    public function getMahasiswaMKGagal(Request $request)
    {
        try {
            $search = $request->query('search', null);
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            // Extract filters
            $filters = [];
            if ($request->has('nama_matkul')) {
                $filters['nama_matkul'] = $request->query('nama_matkul');
            }
            if ($request->has('kode_kelompok')) {
                $filters['kode_kelompok'] = $request->query('kode_kelompok');
            }

            $mahasiswaMKGagal = $this->capaianMahasiswaService->getMahasiswaMKGagal($search, $perPage, $filters);

            // Check if data is found
            if ($mahasiswaMKGagal->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->paginationResponse(
                $mahasiswaMKGagal,
                'Data mahasiswa dengan MK gagal berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaMKGagal');
        }
    }
}
