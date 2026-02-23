<?php

namespace App\Http\Controllers\Koor;

use App\Services\KoorService;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KoorController extends Controller
{
    protected $koorService;

    public function __construct(KoorService $koorService)
    {
        $this->koorService = $koorService;
    }

    //Dashboard Koor

    public function getStatusMahasiswa()
    {
        try {
            $statusMahasiswa = $this->koorService->getStatusMahasiswa();
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
            $rataIpk = $this->koorService->getRataIpkPerAngkatan();

            // Check if data is found
            if ($rataIpk->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
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
            $statusKelulusan = $this->koorService->getStatusKelulusan();
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

            $tableRingkasan = $this->koorService->getTableRingkasanMahasiswa($perPage);

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

            $result = $this->koorService->getDetailAngkatan($tahunMasuk, $search, $perPage);

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

            $detail = $this->koorService->getDetailMahasiswa($mahasiswaId);

            if (!$detail) {
                return $this->errorResponse('Mahasiswa tidak ditemukan', 404);
            }

            return $this->successResponse(
                $detail,
                'Detail mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getDetailMahasiswa');
        }
    }

    // Ststus Mahasiswa

        public function getTableRingkasanStatus()
    {
        try {
            $tableData = $this->koorService->getTableRingkasanStatus();

            // Check if data is found
            if (empty($tableData)) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            return $this->successResponse($tableData, 'Table ringkasan status berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableRingkasanStatus');
        }
    }

    /**
     * Get all mahasiswa with complete details
     * Query params:
     *   ?search=keyword (search by name or NIM - works for both modes)
     *   ?per_page=10 (items per page)
     *   ?mode=simple|detailed (simple=nama/nim/doswal with filters, detailed=all fields without filters)
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
            if (!in_array($mode, ['simple', 'detailed'])) {
                return $this->errorResponse('Parameter mode hanya boleh "simple" atau "detailed"', 400);
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
            $yesNoFields = ['mk_nasional', 'mk_fakultas', 'mk_prodi', 'nilai_d_melebihi_batas', 'nilai_e'];
            foreach ($yesNoFields as $field) {
                if ($request->has($field)) {
                    $value = strtolower($request->query($field));
                    if (!in_array($value, ['yes', 'no'])) {
                        return $this->errorResponse("Parameter {$field} harus berupa 'yes' atau 'no'", 400);
                    }
                    $filters[$field] = $value;
                }
            }

            $mahasiswaAll = $this->koorService->getMahasiswaAll($search, $perPage, $mode, $filters);

            // Check if data is found
            if ($mahasiswaAll->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            return $this->paginationResponse(
                $mahasiswaAll,
                'Data semua mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getMahasiswaAll');
        }
    }

    //capaian mahasiswa

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

            $trenIps = $this->koorService->getTrenIPSAll($tahunMasuk);

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

            $capaian = $this->koorService->getCardCapaianMahasiswa($tahunMasuk);

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
            $topTenMKGagal = $this->koorService->getTopTenMKGagalAll();

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

            $mahasiswaMKGagal = $this->koorService->getMahasiswaMKGagal($search, $perPage, $filters);

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

    // Statisti Kelulusan

    public function getCardStatistikKelulusan(Request $request)
    {
        //dengan filter angkatan (tahun_masuk) dan exclude mahasiswa yang sudah lulus dan DO
        try {
            $tahunMasuk = $request->query('tahun_masuk');

            // Validasi tahun_masuk jika diberikan
            if ($tahunMasuk !== null && (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100)) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $statistikKelulusan = $this->koorService->getCardStatistikKelulusan($tahunMasuk);

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

            $tableData = $this->koorService->getTableStatistikKelulusan($perPage);

            // Check if data is found
            if ($tableData->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai', 404);
            }

            return $this->paginationResponse($tableData, 'Table statistik kelulusan berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableStatistikKelulusan');
        }
    }

    // Tindak Lanjut Prodi

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

            $suratRekomitmen = $this->koorService->getSuratRekomitmen($search, $tahunMasuk, $statusRekomitmen, $perPage);

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

            $result = $this->koorService->updateStatusRekomitmen($id_rekomitmen, $status);
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
