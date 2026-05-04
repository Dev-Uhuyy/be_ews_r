<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Services\Kaprodi\KaprodiCapaianMahasiswaService;

/**
 * @tags Kaprodi - Capaian Mahasiswa
 */
class KaprodiCapaianMahasiswaController extends Controller
{
    protected $capaianMahasiswaService;

    public function __construct(KaprodiCapaianMahasiswaService $capaianMahasiswaService)
    {
        $this->capaianMahasiswaService = $capaianMahasiswaService;
    }

    /**
     * Get Top 10 Mata Kuliah Gagal (E) untuk Prodi Kaprodi
     *
     * Mengembalikan data mata kuliah dengan nilai E (gagal) terbanyak
     * beserta jumlah mahasiswa yang gagal per matakuliah tersebut.
     *
     * Query params (optional):
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getTop10MatakuliahGagal()
    {
        try {
            $filters = [];

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getTop10MatakuliahGagal($filters);

            return $this->successResponse(
                $data,
                'Top 10 mata kuliah gagal berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTop10MatakuliahGagal');
        }
    }

    /**
     * Get Rata-rata IPS per Tahun Angkatan untuk Prodi Kaprodi
     *
     * Mengembalikan data rata-rata IPS mahasiswa per tahun angkatan
     * untuk prodi Kaprodi.
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getRataRataIpsPerTahunProdi()
    {
        try {
            $data = $this->capaianMahasiswaService->getRataRataIpsPerTahunProdi();

            return $this->successResponse(
                $data,
                'Rata-rata IPS per tahun berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getRataRataIpsPerTahunProdi');
        }
    }

    /**
     * Get Tabel Capaian Mahasiswa untuk Prodi Kaprodi
     *
     * Mengembalikan ringkasan capaian mahasiswa yang meliputi:
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal)
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getTabelCapaianMahasiswa()
    {
        try {
            $data = $this->capaianMahasiswaService->getTabelCapaianMahasiswa();

            return $this->successResponse(
                $data,
                'Tabel capaian mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTabelCapaianMahasiswa');
        }
    }

    /**
     * Get Detail Tabel Capaian Mahasiswa (per Tahun Angkatan) untuk Prodi Kaprodi
     *
     * Mengembalikan ringkasan capaian mahasiswa per tahun angkatan yang meliputi:
     * - Tahun Angkatan
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per angkatan
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getDetailTabelCapaianMahasiswa()
    {
        try {
            $data = $this->capaianMahasiswaService->getDetailTabelCapaianMahasiswa();

            return $this->successResponse(
                $data,
                'Detail tabel capaian mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailTabelCapaianMahasiswa');
        }
    }

    /**
     * Get List Mata Kuliah Gagal untuk Prodi Kaprodi
     *
     * Mengembalikan daftar mata kuliah yang memiliki nilai E (gagal)
     * untuk prodi Kaprodi, beserta detail jumlah mahasiswa yang gagal.
     *
     * Query params (optional):
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getListMataKuliahPerProdi()
    {
        try {
            $filters = [];

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getListMataKuliahPerProdi($filters);

            return $this->successResponse(
                $data,
                'List mata kuliah gagal berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getListMataKuliahPerProdi');
        }
    }

    /**
     * Get List Mahasiswa Gagal per Mata Kuliah untuk Prodi Kaprodi
     *
     * Mengembalikan daftar mahasiswa yang mendapat nilai E (gagal)
     * pada mata kuliah tertentu.
     *
     * Query params:
     * - matakuliah_id: ID Mata Kuliah yang akan dicek (required)
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getListMahasiswaGagalPerMataKuliah()
    {
        try {
            $filters = [];

            if (request()->has('matakuliah_id') && request('matakuliah_id') != '') {
                $filters['matakuliah_id'] = request('matakuliah_id');
            }

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getListMahasiswaGagalPerMataKuliah($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa gagal per mata kuliah berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getListMahasiswaGagalPerMataKuliah');
        }
    }

    /**
     * Get List Mahasiswa Gagal per Tahun Angkatan
     *
     * Mengembalikan daftar mahasiswa yang mendapat nilai E (gagal)
     * pada tahun angkatan tertentu, beserta mata kuliah yang gagal.
     *
     * Query params:
     * - tahun_masuk: Tahun angkatan yang akan dicek (required)
     *
     * @tags Kaprodi - Capaian Mahasiswa
     */
    public function getListMahasiswaGagalByAngkatan()
    {
        try {
            $filters = [];

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getListMahasiswaGagalByAngkatan($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa gagal per angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getListMahasiswaGagalByAngkatan');
        }
    }
}
