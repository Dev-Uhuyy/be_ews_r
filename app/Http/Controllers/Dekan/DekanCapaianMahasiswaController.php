<?php

namespace App\Http\Controllers\Dekan;

use App\Http\Controllers\Controller;
use App\Services\Dekan\CapaianMahasiswaService;

/**
 * @tags Dekan - Capaian Mahasiswa
 */
class DekanCapaianMahasiswaController extends Controller
{
    protected $capaianMahasiswaService;

    public function __construct(CapaianMahasiswaService $capaianMahasiswaService)
    {
        $this->capaianMahasiswaService = $capaianMahasiswaService;
    }

    /**
     * Get Top 10 Mata Kuliah Gagal (E) per Prodi
     *
     * Mengembalikan data mata kuliah dengan nilai E (gagal) terbanyak
     * beserta jumlah mahasiswa yang gagal per matakuliah tersebut per prodi.
     *
     * Query params (optional):
     * - prodi_id: Filter berdasarkan ID Prodi
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getTop10MatakuliahGagal()
    {
        try {
            $filters = [];

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
            }

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getTop10MatakuliahGagal($filters);

            return $this->successResponse(
                $data,
                'Top 10 mata kuliah gagal per prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTop10MatakuliahGagal');
        }
    }

    /**
     * Get Rata-rata IPS per Tahun per Prodi
     *
     * Mengembalikan data rata-rata IPS mahasiswa per tahun angkatan
     * yang dikelompokkan per prodi.
     *
     * Query params (optional):
     * - prodi_id: Filter berdasarkan ID Prodi
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getRataRataIpsPerTahunProdi()
    {
        try {
            $filters = [];

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
            }

            $data = $this->capaianMahasiswaService->getRataRataIpsPerTahunProdi($filters);

            return $this->successResponse(
                $data,
                'Rata-rata IPS per tahun per prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getRataRataIpsPerTahunProdi');
        }
    }

    /**
     * Get Tabel Capaian Mahasiswa
     *
     * Mengembalikan ringkasan capaian mahasiswa per prodi yang meliputi:
     * - Nama Prodi
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per prodi
     *
     * Query params (optional):
     * - prodi_id: Filter berdasarkan ID Prodi
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getTabelCapaianMahasiswa()
    {
        try {
            $filters = [];

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
            }

            $data = $this->capaianMahasiswaService->getTabelCapaianMahasiswa($filters);

            return $this->successResponse(
                $data,
                'Tabel capaian mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTabelCapaianMahasiswa');
        }
    }

    /**
     * Get Detail Tabel Capaian Mahasiswa (per Tahun Angkatan)
     *
     * Mengembalikan ringkasan capaian mahasiswa per prodi per tahun angkatan yang meliputi:
     * - Prodi info
     * - Tahun Angkatan
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per prodi per angkatan
     *
     * Query params (optional):
     * - prodi_id: Filter berdasarkan ID Prodi
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getDetailTabelCapaianMahasiswa()
    {
        try {
            $filters = [];

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
            }

            $data = $this->capaianMahasiswaService->getDetailTabelCapaianMahasiswa($filters);

            return $this->successResponse(
                $data,
                'Detail tabel capaian mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailTabelCapaianMahasiswa');
        }
    }

    /**
     * Get List Mata Kuliah Gagal per Prodi
     *
     * Mengembalikan daftar mata kuliah yang memiliki nilai E (gagal)
     * per prodi, beserta detail jumlah mahasiswa yang gagal per matakuliah.
     *
     * Query params:
     * - prodi_id: Filter berdasarkan ID Prodi (required)
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getListMataKuliahPerProdi()
    {
        try {
            $filters = [];

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
            }

            if (request()->has('tahun_masuk') && request('tahun_masuk') != '') {
                $filters['tahun_masuk'] = request('tahun_masuk');
            }

            $data = $this->capaianMahasiswaService->getListMataKuliahPerProdi($filters);

            return $this->successResponse(
                $data,
                'List mata kuliah gagal per prodi berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getListMataKuliahPerProdi');
        }
    }

    /**
     * Get List Mahasiswa Gagal per Mata Kuliah
     *
     * Mengembalikan daftar mahasiswa yang mendapat nilai E (gagal)
     * pada mata kuliah tertentu.
     *
     * Query params:
     * - matakuliah_id: ID Mata Kuliah yang akan dicek (required)
     * - prodi_id: Filter berdasarkan ID Prodi (optional)
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     *
     * @tags Dekan - Capaian Mahasiswa
     */
    public function getListMahasiswaGagalPerMataKuliah()
    {
        try {
            $filters = [];

            if (request()->has('matakuliah_id') && request('matakuliah_id') != '') {
                $filters['matakuliah_id'] = request('matakuliah_id');
            }

            if (request()->has('prodi_id') && request('prodi_id') != '') {
                $filters['prodi_id'] = request('prodi_id');
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
}