<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\NilaiMahasiswaService;
use App\Http\Controllers\Controller;

/**
 * @tags Dekan - Nilai Mahasiswa
 */
class NilaiMahasiswaController extends Controller
{
    protected $nilaiMahasiswaService;

    public function __construct(NilaiMahasiswaService $nilaiMahasiswaService)
    {
        $this->nilaiMahasiswaService = $nilaiMahasiswaService;
    }

    /**
     * Get list mahasiswa dengan data lengkap Nilai D, Nilai E, SKS tidak lulus, dan MK kurang
     *
     * Query params:
     * - prodi_id: Filter berdasarkan ID Prodi
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     * - has_nilai_d: true/false - mahasiswa yang memiliki nilai D
     * - has_nilai_e: true/false - mahasiswa yang memiliki nilai E
     * - mk_nasional_kurang: true/false - MK nasional yang belum lulus
     * - mk_fakultasan_kurang: true/false - MK fakultas yang belum lulus
     * - status_kelulusan: 'eligible' atau 'noneligible'
     * - search: Pencarian berdasarkan nama atau NIM
     * - mahasiswa_id: Filter untuk satu mahasiswa spesifik (mengabaikan per_page & pagination)
     * - per_page: Jumlah item per halaman (default: 10)
     *
     * Response (single mahasiswa_id):
     * - Data satu mahasiswa dengan:
     *   - mata_kuliah_nilai_d: Array matakuliah dengan nilai D
     *   - mata_kuliah_nilai_e: Array matakuliah dengan nilai E
     *   - total_sks_nilai_d: Total SKS nilai D
     *   - total_sks_nilai_e: Total SKS nilai E
     *   - total_sks_tidak_lulus: Total SKS tidak lulus (D + E)
     *   - mk_nasional_kurang: Array MK nasional yang belum lulus
     *   - mk_fakultas_kurang: Array MK fakultas yang belum lulus
     *
     * Contoh:
     * - GET /api/ews/dekan/nilai-mahasiswa?prodi_id=1
     * - GET /api/ews/dekan/nilai-mahasiswa?has_nilai_e=true
     * - GET /api/ews/dekan/nilai-mahasiswa?tahun_masuk=2023&mk_nasional_kurang=true
     * - GET /api/ews/dekan/nilai-mahasiswa?mahasiswa_id=5 (single mahasiswa)
     *
     * @tags Dekan - Nilai Mahasiswa
     */
    public function getNilaiMahasiswaList()
    {
        try {
            $filters = request()->query();
            $perPage = request()->query('per_page', 10);
            $search = request()->query('search');

            $data = $this->nilaiMahasiswaService->getNilaiMahasiswaList($filters, $perPage, $search);

            return $this->successResponse(
                $data,
                'List nilai mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getNilaiMahasiswaList');
        }
    }

    /**
     * Get summary statistics nilai D, E, dan MK kurang
     *
     * Query params:
     * - prodi_id: Filter berdasarkan ID Prodi
     * - tahun_masuk: Filter berdasarkan tahun angkatan
     *
     * Response:
     * - total_mahasiswa: Total mahasiswa
     * - mahasiswa_dengan_nilai_d: Jumlah mahasiswa yang memiliki nilai D
     * - mahasiswa_dengan_nilai_e: Jumlah mahasiswa yang memiliki nilai E
     * - mk_nasional_belum_lulus: Jumlah mahasiswa yang belum lulus MK nasional
     * - mk_fakultas_belum_lulus: Jumlah mahasiswa yang belum lulus MK fakultas
     *
     * @tags Dekan - Nilai Mahasiswa
     */
    public function getNilaiMahasiswaSummary()
    {
        try {
            $filters = request()->query();
            $data = $this->nilaiMahasiswaService->getNilaiMahasiswaSummary($filters);

            return $this->successResponse(
                $data,
                'Summary nilai mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getNilaiMahasiswaSummary');
        }
    }
}