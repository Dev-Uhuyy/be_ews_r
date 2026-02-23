<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Services\DosenService;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    protected $dosenService;

    public function __construct(DosenService $dosenService)
    {
        $this->dosenService = $dosenService;
    }

    // Dashboard Dosen

    public function getStatusMahasiswa()
    {
        try {
            $statusMahasiswa = $this->dosenService->getStatusMahasiswa();
            return $this->successResponse(
                $statusMahasiswa,
                'Status mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getStatusMahasiswa');
        }
    }

    public function getRataIpkPerAngkatan()
    {
        try {
            $rataIpk = $this->dosenService->getRataIpkPerAngkatan();

            // Check if data is found
            if ($rataIpk->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa bimbingan', 404);
            }

            return $this->successResponse(
                $rataIpk,
                'Rata IPK per angkatan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getRataIpkPerAngkatan');
        }
    }

    public function getStatusKelulusan()
    {
        try {
            $statusKelulusan = $this->dosenService->getStatusKelulusan();
            return $this->successResponse(
                $statusKelulusan,
                'Status kelulusan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getStatusKelulusan');
        }
    }

    /**
     * Get table ringkasan mahasiswa per angkatan
     * Query params:
     *   ?per_page=10 (items per page)
     */
    public function getTableRingkasanMahasiswa(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $tableRingkasan = $this->dosenService->getTableRingkasanMahasiswa($perPage);

            // Check if data is found
            if ($tableRingkasan->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            return $this->paginationResponse(
                $tableRingkasan,
                'Tabel ringkasan mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableRingkasanMahasiswa');
        }
    }

    // General

    public function getDetailAngkatan(Request $request, $tahunMasuk)
    {
        try {
            // Validasi tahun_masuk
            if (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $search = $request->query('search', null);
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $result = $this->dosenService->getDetailAngkatan($tahunMasuk, $search, $perPage);

            // Check if data is found
            if ($result['paginated_data']->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            // Prepare additional data (summary)
            $additionalData = [
                'summary' => [
                    'rata_ips_per_semester' => $result['rata_ips_per_semester'],
                    'distribusi_status_ews' => $result['distribusi_status_ews'],
                    'total_mahasiswa' => $result['total_mahasiswa'],
                ]
            ];

            return $this->paginationResponse(
                $result['paginated_data'],
                'Detail angkatan berhasil diambil',
                200,
                $additionalData
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailAngkatan');
        }
    }

    public function getDetailMahasiswa($mahasiswaId)
    {
        try {
            // Validasi mahasiswa_id
            if (!is_numeric($mahasiswaId) || $mahasiswaId < 1) {
                return $this->errorResponse('Parameter mahasiswa_id harus berupa angka yang valid', 400);
            }

            $detail = $this->dosenService->getDetailMahasiswa($mahasiswaId);

            if (!$detail) {
                return $this->errorResponse('Mahasiswa tidak ditemukan atau bukan mahasiswa bimbingan Anda', 404);
            }

            return $this->successResponse(
                $detail,
                'Detail mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailMahasiswa');
        }
    }

    /**
     * Get all mahasiswa bimbingan with complete details
     * Query params:
     *   ?search=keyword (search by name or NIM - works for all modes)
     *   ?per_page=10 (items per page)
     *   ?mode=simple|detailed|perwalian
     *     - simple: nama/nim/doswal with filters
     *     - detailed: all fields without filters
     *     - perwalian: nama/nim/semester/ipk/SPS/status (dosen only, no filters)
     *
     * Filters (ONLY work with mode=simple):
     *   ?status_mahasiswa=aktif|cuti|mangkir (filter by status)
     *   ?status_ews=tepat_waktu|normal|perhatian|kritis (filter by EWS status)
     *   ?status_kelulusan=eligible|noneligible (filter by kelulusan status)
     *   ?tahun_masuk=2023 (filter by angkatan)
     *   ?semester_aktif=6 (filter by semester)
     *   ?mk_nasional=yes|no
     *   ?mk_fakultas=yes|no
     *   ?mk_prodi=yes|no
     *   ?nilai_d_melebihi_batas=yes|no
     *   ?nilai_e=yes|no
     *   ?SPS1=yes|no
     *   ?SPS2=yes|no
     *   ?SPS3=yes|no
     *
     * Note: All modes exclude mahasiswa with status 'lulus' and 'do'
     */
    public function getMahasiswaAll(Request $request)
    {
        try {
            $search = $request->query('search', null);
            $perPage = $request->query('per_page', 10);
            $mode = $request->query('mode', 'simple');

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            // Validasi mode
            if (!in_array($mode, ['simple', 'detailed', 'perwalian'])) {
                return $this->errorResponse('Parameter mode hanya boleh "simple", "detailed", atau "perwalian"', 400);
            }

            // Extract filters
            $filters = [];

            // Validasi dan extract status_mahasiswa
            if ($request->has('status_mahasiswa')) {
                $statusMahasiswa = $request->query('status_mahasiswa');
                $validStatusMahasiswa = ['aktif', 'cuti', 'mangkir', 'lulus', 'do'];
                if (!in_array(strtolower($statusMahasiswa), $validStatusMahasiswa)) {
                    return $this->errorResponse('Parameter status_mahasiswa harus salah satu dari: aktif, cuti, mangkir, lulus, do', 400);
                }
                $filters['status_mahasiswa'] = $statusMahasiswa;
            }

            // Validasi dan extract status_ews
            if ($request->has('status_ews')) {
                $statusEws = $request->query('status_ews');
                $validStatusEws = ['tepat_waktu', 'normal', 'perhatian', 'kritis'];
                if (!in_array($statusEws, $validStatusEws)) {
                    return $this->errorResponse('Parameter status_ews harus salah satu dari: tepat_waktu, normal, perhatian, kritis', 400);
                }
                $filters['status_ews'] = $statusEws;
            }

            // Validasi dan extract status_kelulusan
            if ($request->has('status_kelulusan')) {
                $statusKelulusan = $request->query('status_kelulusan');
                $validStatusKelulusan = ['eligible', 'noneligible'];
                if (!in_array($statusKelulusan, $validStatusKelulusan)) {
                    return $this->errorResponse('Parameter status_kelulusan harus salah satu dari: eligible, noneligible', 400);
                }
                $filters['status_kelulusan'] = $statusKelulusan;
            }

            // Validasi dan extract tahun_masuk
            if ($request->has('tahun_masuk')) {
                $tahunMasuk = $request->query('tahun_masuk');
                if (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100) {
                    return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
                }
                $filters['tahun_masuk'] = $tahunMasuk;
            }

            // Validasi dan extract semester_aktif
            if ($request->has('semester_aktif')) {
                $semesterAktif = $request->query('semester_aktif');
                if (!is_numeric($semesterAktif) || $semesterAktif < 1 || $semesterAktif > 14) {
                    return $this->errorResponse('Parameter semester_aktif harus berupa angka antara 1-14', 400);
                }
                $filters['semester_aktif'] = $semesterAktif;
            }

            // Validasi dan extract yes/no fields
            $yesNoFields = ['mk_nasional', 'mk_fakultas', 'mk_prodi', 'nilai_d_melebihi_batas', 'nilai_e', 'SPS1', 'SPS2', 'SPS3'];
            foreach ($yesNoFields as $field) {
                if ($request->has($field)) {
                    $value = strtolower($request->query($field));
                    if (!in_array($value, ['yes', 'no'])) {
                        return $this->errorResponse("Parameter {$field} harus berupa 'yes' atau 'no'", 400);
                    }
                    $filters[$field] = $value;
                }
            }

            $mahasiswaAll = $this->dosenService->getMahasiswaAll($search, $perPage, $mode, $filters);

            // Check if data is found
            if ($mahasiswaAll->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->paginationResponse(
                $mahasiswaAll,
                'Data mahasiswa bimbingan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaAll');
        }
    }

    // Status Mahasiswa

    public function getDistribusiStatusEws(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk', null);

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $distribusi = $this->dosenService->getDistribusiStatusEws($tahunMasuk);

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

    public function getTableRingkasanStatus()
    {
        try {
            $tableData = $this->dosenService->getTableRingkasanStatus();

            // Check if data is found
            if (empty($tableData)) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa bimbingan', 404);
            }

            return $this->successResponse($tableData, 'Table ringkasan status berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableRingkasanStatus');
        }
    }

    // Statistik Kelulusan

    public function getCardStatistikKelulusan(Request $request)
    {
        try {
            $tahunMasuk = $request->query('tahun_masuk');

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $statistikKelulusan = $this->dosenService->getCardStatistikKelulusan($tahunMasuk);

            // Check if data is found when filter is applied
            if ($tahunMasuk && !$statistikKelulusan) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->successResponse(
                $statistikKelulusan,
                'Statistik kelulusan berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardStatistikKelulusan');
        }
    }

    /**
     * Get table statistik kelulusan per angkatan
     * Query params:
     *   ?per_page=10 (items per page)
     */
    public function getTableStatistikKelulusan(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);

            // Validasi per_page
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                return $this->errorResponse('Parameter per_page harus berupa angka antara 1-100', 400);
            }

            $tableData = $this->dosenService->getTableStatistikKelulusan($perPage);

            // Check if data is found
            if ($tableData->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai', 404);
            }

            return $this->paginationResponse($tableData, 'Table statistik kelulusan berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableStatistikKelulusan');
        }
    }
}
