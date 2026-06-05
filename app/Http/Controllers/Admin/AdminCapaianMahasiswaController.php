<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminCapaianMahasiswaService;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;

/**
 * @tags Admin - Capaian Mahasiswa
 */
class AdminCapaianMahasiswaController extends Controller
{
    protected $capaianMahasiswaService;

    public function __construct(AdminCapaianMahasiswaService $capaianMahasiswaService)
    {
        $this->capaianMahasiswaService = $capaianMahasiswaService;
    }

    /**
     * Get Top 10 Mata Kuliah Gagal (E) untuk Prodi Admin
     *
     * Mengembalikan data mata kuliah dengan nilai E (gagal) terbanyak
     * beserta jumlah mahasiswa yang gagal per matakuliah tersebut.
     *
     * Query params (optional):
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * @tags Admin - Capaian Mahasiswa
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
     * Get Rata-rata IPS per Tahun Angkatan untuk Prodi Admin
     *
     * Mengembalikan data rata-rata IPS mahasiswa per tahun angkatan
     * untuk prodi Admin.
     *
     * @tags Admin - Capaian Mahasiswa
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
     * Get Tabel Capaian Mahasiswa untuk Prodi Admin
     *
     * Mengembalikan ringkasan capaian mahasiswa yang meliputi:
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal)
     *
     * @tags Admin - Capaian Mahasiswa
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
     * Get Detail Tabel Capaian Mahasiswa (per Tahun Angkatan) untuk Prodi Admin
     *
     * Mengembalikan ringkasan capaian mahasiswa per tahun angkatan yang meliputi:
     * - Tahun Angkatan
     * - Tren IPS (Naik / Turun / Stabil)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per angkatan
     *
     * @tags Admin - Capaian Mahasiswa
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
     * Get List Mata Kuliah Gagal untuk Prodi Admin
     *
     * Mengembalikan daftar mata kuliah yang memiliki nilai E (gagal)
     * untuk prodi Admin, beserta detail jumlah mahasiswa yang gagal.
     *
     * Query params (optional):
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * @tags Admin - Capaian Mahasiswa
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
     * Get List Mahasiswa Gagal per Mata Kuliah untuk Prodi Admin
     *
     * Mengembalikan daftar mahasiswa yang mendapat nilai E (gagal)
     * pada mata kuliah tertentu.
     *
     * Query params:
     * - matakuliah_id: ID Mata Kuliah yang akan dicek (required)
     * - tahun_masuk: Filter berdasarkan tahun angkatan (optional)
     *
     * @tags Admin - Capaian Mahasiswa
     */
    #[QueryParameter('matakuliah_id', description: 'ID Mata Kuliah yang akan dicek', required: true, type: 'integer', example: 23)]
    #[QueryParameter('tahun_masuk', description: 'Filter berdasarkan tahun angkatan', required: false, type: 'integer', example: 2020)]
    public function getListMahasiswaGagalPerMataKuliah()
    {
        try {
            if (! request()->filled('matakuliah_id')) {
                return $this->errorResponse('Parameter matakuliah_id wajib diisi', 400);
            }

            $filters = [
                'matakuliah_id' => request('matakuliah_id'),
            ];

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
     * @tags Admin - Capaian Mahasiswa
     */
    #[QueryParameter('tahun_masuk', description: 'Tahun angkatan yang akan dicek', required: true, type: 'integer', example: 2020)]
    public function getListMahasiswaGagalByAngkatan()
    {
        try {
            if (! request()->filled('tahun_masuk')) {
                return $this->errorResponse('Parameter tahun_masuk wajib diisi', 400);
            }

            $filters = [
                'tahun_masuk' => request('tahun_masuk'),
            ];

            $data = $this->capaianMahasiswaService->getListMahasiswaGagalByAngkatan($filters);

            return $this->successResponse(
                $data,
                'List mahasiswa gagal per angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getListMahasiswaGagalByAngkatan');
        }
    }

    // ── Export Endpoints ─────────────────────────────────────────────────────

    /**
     * Export Top Mata Kuliah Gagal to XLSX
     *
     * Query params (optional): tahun_masuk
     *
     * @tags Admin - Capaian Mahasiswa
     */
    public function exportTopMatakuliahGagal(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->capaianMahasiswaService->exportTopMatakuliahGagal($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTopMatakuliahGagal');
        }
    }

    /**
     * Export Rata-rata IPS to XLSX
     *
     * @tags Admin - Capaian Mahasiswa
     */
    public function exportRataRataIps(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->capaianMahasiswaService->exportRataRataIps($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportRataRataIps');
        }
    }

    /**
     * Export Tabel Capaian to XLSX
     *
     * @tags Admin - Capaian Mahasiswa
     */
    public function exportTabelCapaian(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->capaianMahasiswaService->exportTabelCapaian($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTabelCapaian');
        }
    }

    /**
     * Export Detail Tabel Capaian to XLSX
     *
     * @tags Admin - Capaian Mahasiswa
     */
    public function exportTabelCapaianDetail(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->capaianMahasiswaService->exportTabelCapaianDetail($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTabelCapaianDetail');
        }
    }

    /**
     * Export List Mata Kuliah Gagal to XLSX
     *
     * Query params (optional): tahun_masuk
     *
     * @tags Admin - Capaian Mahasiswa
     */
    public function exportListMatakuliah(Request $request)
    {
        try {
            $filters = $request->query();

            return $this->capaianMahasiswaService->exportListMatakuliah($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportListMatakuliah');
        }
    }

    /**
     * Export Mahasiswa Gagal per Mata Kuliah to XLSX
     *
     * Query params (required): matakuliah_id
     * Query params (optional): tahun_masuk
     *
     * @tags Admin - Capaian Mahasiswa
     */
    #[QueryParameter('matakuliah_id', description: 'ID Mata Kuliah yang akan dicek', required: true, type: 'integer', example: 23)]
    #[QueryParameter('tahun_masuk', description: 'Filter berdasarkan tahun angkatan', required: false, type: 'integer', example: 2020)]
    public function exportMahasiswaGagal(Request $request)
    {
        try {
            if (! $request->filled('matakuliah_id')) {
                return $this->errorResponse('Parameter matakuliah_id wajib diisi', 400);
            }

            $filters = $request->query();

            return $this->capaianMahasiswaService->exportMahasiswaGagal($filters);
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportMahasiswaGagal');
        }
    }
}
