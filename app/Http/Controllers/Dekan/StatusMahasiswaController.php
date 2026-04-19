<?php

namespace App\Http\Controllers\Dekan;

use App\Services\Dekan\StatusMahasiswaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @tags Dekan - Status Mahasiswa
 */
class StatusMahasiswaController extends Controller
{
    protected $statusMahasiswaService;

    public function __construct(StatusMahasiswaService $statusMahasiswaService)
    {
        $this->statusMahasiswaService = $statusMahasiswaService;
    }

    /**
     * Get Detail Mahasiswa per Angkatan
     *
     * Menampilkan detail mahasiswa berdasarkan tahun masuk dengan informasi lengkap:
     * - Nama, NIM, IPK, SKS lulus
     * - Jumlah & SKS nilai D
     * - Jumlah & SKS nilai E
     * - Status Eligible/Tidak
     * - Nama Dosen Wali (dengan gelar)
     * - Status EWS
     *
     * @urlParam tahunMasuk integer required Tahun masuk mahasiswa (2000-2100). Example: 2023
     * @queryParam search string Search by nama mahasiswa. Example: John
     * @queryParam per_page integer Items per page (1-100). Example: 10
     *
     * @tags Dekan - Status Mahasiswa
     */
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

            $result = $this->statusMahasiswaService->getDetailAngkatan($tahunMasuk, $search, $perPage);

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

    /**
     * Export Detail Mahasiswa per Angkatan ke XLSX
     *
     * Export data detail mahasiswa per angkatan ke format Excel.
     * Termasuk detail MK Nasional, Fakultas, Prodi, serta nilai D dan E.
     *
     * @urlParam tahunMasuk integer required Tahun masuk mahasiswa. Example: 2023
     * @queryParam search string Search by nama mahasiswa. Example: John
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function exportDetailAngkatanCsv(Request $request, $tahunMasuk)
    {
        try {
            // Validasi tahun_masuk
            if (!is_numeric($tahunMasuk) || $tahunMasuk < 2000 || $tahunMasuk > 2100) {
                return $this->errorResponse('Parameter tahun_masuk harus berupa angka tahun yang valid (2000-2100)', 400);
            }

            $search = $request->query('search', null);
            $data = $this->statusMahasiswaService->getDetailAngkatanExport($tahunMasuk, $search);

            if ($data->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data untuk diexport', 404);
            }

            $fileName = "Detail Angkatan $tahunMasuk " . date('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\MahasiswaDetailAngkatanExport(
                    $data,
                    "Detail Mahasiswa Angkatan $tahunMasuk",
                    ["Angkatan: $tahunMasuk"]
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportDetailAngkatanCsv');
        }
    }

    /**
     * Get Detail Mahasiswa Lengkap
     *
     * Menampilkan detail lengkap satu mahasiswa:
     * - Data akademik (IPK, SKS, semester)
     * - Data dosen wali (dengan gelar)
     * - IP per semester
     * - Mata kuliah dengan nilai D (detail)
     * - Mata kuliah dengan nilai E (detail)
     * - Riwayat SPS
     * - Status EWS & kelulusan
     *
     * @urlParam mahasiswaId integer required ID mahasiswa. Example: 1
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function getDetailMahasiswa($mahasiswaId)
    {
        try {
            // Validasi mahasiswa_id
            if (!is_numeric($mahasiswaId) || $mahasiswaId < 1) {
                return $this->errorResponse('Parameter mahasiswa_id harus berupa angka yang valid', 400);
            }

            $detail = $this->statusMahasiswaService->getDetailMahasiswa($mahasiswaId);

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

    /**
     * Get Table Ringkasan Status per Angkatan
     *
     * Menampilkan ringkasan per angkatan:
     * - Jumlah mahasiswa
     * - IPK < 2
     * - Mangkir
     * - Cuti
     * - Status Perhatian
     *
     * **Note:** Exclude mahasiswa yang sudah lulus dan DO
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function getTableRingkasanStatus()
    {
        try {
            $tableDataFlat = $this->statusMahasiswaService->getTableRingkasanStatus();

            if (empty($tableDataFlat)) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            // Transformasi menjadi Grouped by Prodi
            $groupedData = collect($tableDataFlat)->groupBy('kode_prodi')->map(function ($items) {
                $prodiInfo = [
                    'nama_prodi' => $items->first()->nama_prodi,
                    'kode_prodi' => $items->first()->kode_prodi,
                ];

                $detailAngkatan = $items->map(function ($item) {
                    // Check if $item is object (Eloquent) or array (from standard DB query)
                    $data = is_object($item) ? (method_exists($item, 'toArray') ? $item->toArray() : (array) $item) : (array) $item;
                    unset($data['nama_prodi']);
                    unset($data['kode_prodi']);
                    return $data;
                })->values();

                // Hitung total akumulasi per prodi
                $totals = [
                    'jumlah_mahasiswa' => $detailAngkatan->sum('jumlah_mahasiswa'),
                    'ipk_kurang_dari_2' => $detailAngkatan->sum('ipk_kurang_dari_2'),
                    'mangkir' => $detailAngkatan->sum('mangkir'),
                    'cuti' => $detailAngkatan->sum('cuti'),
                    'perhatian' => $detailAngkatan->sum('perhatian'),
                ];

                return array_merge($prodiInfo, [
                    'total_status' => $totals,
                    'detail_angkatan' => $detailAngkatan
                ]);
            })->values();

            return $this->successResponse($groupedData, 'Table ringkasan status per prodi berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTableRingkasanStatus');
        }
    }

    /**
     * Export Table Ringkasan Status ke XLSX
     *
     * Export data ringkasan status per angkatan dalam format Excel.
     * File berisi: jumlah mahasiswa, IPK < 2, mangkir, cuti, perhatian.
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function exportTableRingkasanStatusCsv(Request $request)
    {
        try {
            $tableData = $this->statusMahasiswaService->getTableRingkasanStatusExport();

            if ($tableData->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data mahasiswa', 404);
            }

            $fileName = 'Ringkasan Status ' . date('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\TableRingkasanStatusExport(
                    $tableData,
                    'Ringkasan Status Mahasiswa per Angkatan',
                    ['Fakultas Ilmu Komputer']
                ),
                $fileName
            );

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportTableRingkasanStatusCsv');
        }
    }

    /**
     * Get All Mahasiswa dengan Filter Lengkap
     *
     * @queryParam search string Search by nama atau NIM. Example: John
     * @queryParam per_page integer Items per page 1-100. Example: 10
     * @queryParam mode string Mode simple atau detailed. Example: simple
     * @queryParam status_mahasiswa string Filter aktif cuti mangkir. Example: aktif
     * @queryParam status_ews string Filter tepat_waktu normal perhatian kritis. Example: perhatian
     * @queryParam status_kelulusan string Filter eligible noneligible. Example: eligible
     * @queryParam tahun_masuk integer Filter angkatan tahun. Example: 2023
     * @queryParam semester_aktif integer Filter semester 1-14. Example: 6
     * @queryParam semester_1_3 string Mahasiswa semester 1-3 yes no. Example: yes
     * @queryParam ipk_rendah string Mahasiswa IPK kurang dari 2 yes no. Example: yes
     * @queryParam mk_ulang string Mahasiswa dengan MK ulang yes no. Example: yes
     * @queryParam sks_kurang string Mahasiswa SKS kurang dari 144 yes no. Example: yes
     * @queryParam mk_nasional string Sudah ambil MK Nasional yes no. Example: yes
     * @queryParam mk_fakultas string Sudah ambil MK Fakultas yes no. Example: yes
     * @queryParam mk_prodi string Sudah ambil MK Prodi yes no. Example: yes
     * @queryParam nilai_d_melebihi_batas string Nilai D lebih dari 5 persen yes no. Example: yes
     * @queryParam nilai_e string Punya nilai E yes no. Example: yes
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function getMahasiswaAll(Request $request)
    {
        try {
            // Validasi semua parameters
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'mode' => 'nullable|string|in:simple,detailed',
                'status_mahasiswa' => 'nullable|string|in:aktif,cuti,mangkir',
                'status_ews' => 'nullable|string|in:tepat_waktu,normal,perhatian,kritis',
                'status_kelulusan' => 'nullable|string|in:eligible,noneligible',
                'tahun_masuk' => 'nullable|integer|min:2000|max:2100',
                'semester_aktif' => 'nullable|integer|min:1|max:14',
                'semester_1_3' => 'nullable|string|in:yes,no',
                'ipk_rendah' => 'nullable|string|in:yes,no',
                'mk_ulang' => 'nullable|string|in:yes,no',
                'sks_kurang' => 'nullable|string|in:yes,no',
                'mk_nasional' => 'nullable|string|in:yes,no',
                'mk_fakultas' => 'nullable|string|in:yes,no',
                'mk_prodi' => 'nullable|string|in:yes,no',
                'nilai_d_melebihi_batas' => 'nullable|string|in:yes,no',
                'nilai_e' => 'nullable|string|in:yes,no',
            ]);

            $search = $request->query('search', null);
            $perPage = $request->query('per_page', 10);
            $mode = $request->query('mode', 'simple');

            // Extract filters from request
            $filters = [];
            $filterFields = ['status_mahasiswa', 'status_ews', 'status_kelulusan', 'tahun_masuk', 'semester_aktif',
                            'semester_1_3', 'ipk_rendah', 'mk_ulang', 'sks_kurang', 'mk_nasional',
                            'mk_fakultas', 'mk_prodi', 'nilai_d_melebihi_batas', 'nilai_e'];

            foreach ($filterFields as $field) {
                if ($request->has($field)) {
                    $filters[$field] = $request->query($field);
                }
            }

            $mahasiswaAll = $this->statusMahasiswaService->getMahasiswaAll($search, $perPage, $mode, $filters);

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

    /**
     * Export All Mahasiswa ke XLSX
     *
     * Export data mahasiswa dengan filter yang sama seperti getMahasiswaAll.
     * File akan di-generate dan di-download sebagai .xlsx
     *
     * @queryParam search string Search by nama atau NIM. Example: John
     * @queryParam mode string Mode export: simple atau detailed (default: detailed). Example: detailed
     * @queryParam status_mahasiswa string Filter by status: aktif, cuti, mangkir. Example: aktif
     * @queryParam status_ews string Filter: tepat_waktu, normal, perhatian, kritis. Example: perhatian
     * @queryParam status_kelulusan string Filter: eligible, noneligible. Example: eligible
     * @queryParam tahun_masuk integer Filter by angkatan. Example: 2023
     * @queryParam semester_aktif integer Filter by semester. Example: 6
     * @queryParam mk_nasional string Filter syarat MK (yes/no). Example: yes
     * @queryParam mk_fakultas string Filter syarat MK (yes/no). Example: yes
     * @queryParam mk_prodi string Filter syarat MK (yes/no). Example: yes
     * @queryParam nilai_d_melebihi_batas string Filter nilai D (yes/no). Example: yes
     * @queryParam nilai_e string Filter nilai E (yes/no). Example: yes
     *
     * @tags Dekan - Status Mahasiswa
     */
    public function exportMahasiswaAllCsv(Request $request)
    {
        try {
            // Validasi semua parameters
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'mode' => 'nullable|string|in:simple,detailed',
                'status_mahasiswa' => 'nullable|string|in:aktif,cuti,mangkir',
                'status_ews' => 'nullable|string|in:tepat_waktu,normal,perhatian,kritis',
                'status_kelulusan' => 'nullable|string|in:eligible,noneligible',
                'tahun_masuk' => 'nullable|integer|min:2000|max:2100',
                'semester_aktif' => 'nullable|integer|min:1|max:14',
                'semester_1_3' => 'nullable|string|in:yes,no',
                'ipk_rendah' => 'nullable|string|in:yes,no',
                'mk_ulang' => 'nullable|string|in:yes,no',
                'sks_kurang' => 'nullable|string|in:yes,no',
                'mk_nasional' => 'nullable|string|in:yes,no',
                'mk_fakultas' => 'nullable|string|in:yes,no',
                'mk_prodi' => 'nullable|string|in:yes,no',
                'nilai_d_melebihi_batas' => 'nullable|string|in:yes,no',
                'nilai_e' => 'nullable|string|in:yes,no',
            ]);

            $search = $request->query('search', null);
            $mode = $request->query('mode', 'detailed'); // default to detailed for export

            // Extract filters from request
            $filters = [];
            $filterFields = ['status_mahasiswa', 'status_ews', 'status_kelulusan', 'tahun_masuk', 'semester_aktif',
                            'semester_1_3', 'ipk_rendah', 'mk_ulang', 'sks_kurang', 'mk_nasional',
                            'mk_fakultas', 'mk_prodi', 'nilai_d_melebihi_batas', 'nilai_e'];

            foreach ($filterFields as $field) {
                if ($request->has($field)) {
                    $filters[$field] = $request->query($field);
                }
            }

            $mahasiswaAll = $this->statusMahasiswaService->getMahasiswaAllExport($search, $mode, $filters);

            if ($mahasiswaAll->isEmpty()) {
                return $this->errorResponse('Tidak ditemukan data yang sesuai dengan filter', 404);
            }

            // Custom filename logic based on params
            $fileName = 'Data Mahasiswa ' . date('Y-m-d') . '.xlsx';

            if (($filters['ipk_rendah'] ?? null) === 'yes' && ($filters['semester_1_3'] ?? null) === 'yes') {
                $fileName = 'Daftar Mahasiswa Beresiko Semester 1-3.xlsx';
            } elseif (($filters['ipk_rendah'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa IPK di Bawah 2 Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['sks_kurang'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa SKS Kurang dari 144 Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['mk_ulang'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Mengulang Mata Kuliah Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['mk_nasional'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Mengulang Mata Kuliah Nasional Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['mk_fakultas'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Mengulang Mata Kuliah Fakultas Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['mk_prodi'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Mengulang Mata Kuliah Prodi Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['nilai_d_melebihi_batas'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Nilai D Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (($filters['nilai_e'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa Nilai E Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (!empty($filters['status_mahasiswa'])) {
                $fileName = 'Daftar Mahasiswa Status ' . ucfirst($filters['status_mahasiswa']) . '.xlsx';
            } elseif (!empty($filters['status_ews'])) {
                $fileName = 'Daftar Mahasiswa Status EWS ' . ucfirst($filters['status_ews']) . '.xlsx';
            } elseif (!empty($filters['status_kelulusan']) && !empty($filters['tahun_masuk'])) {
                $fileName = 'Daftar Mahasiswa ' . ucfirst($filters['status_kelulusan']) . ' Angkatan ' . $filters['tahun_masuk'] . '.xlsx';
            } elseif (!empty($filters['status_kelulusan'])) {
                $fileName = 'Daftar Mahasiswa Status Kelulusan ' . ucfirst($filters['status_kelulusan']) . '.xlsx';
            }

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\MahasiswaAllExport(
                    $mahasiswaAll,
                    $this->generateReportTitle($filters, 'Data Semua Mahasiswa'),
                    ['Fakultas Ilmu Komputer']
                ),
                $fileName
            );

        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportMahasiswaAllCsv');
        }
    }

    /**
     * Generate report title based on filters
     */
    private function generateReportTitle(array $filters, string $default): string
    {
        if (($filters['ipk_rendah'] ?? null) === 'yes' && ($filters['semester_1_3'] ?? null) === 'yes') {
            return 'Daftar Mahasiswa Beresiko Semester 1-3';
        } elseif (($filters['ipk_rendah'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa IPK di Bawah 2 Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['sks_kurang'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa SKS Kurang dari 144 Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['mk_ulang'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Mengulang Mata Kuliah Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['mk_nasional'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Mengulang MK Nasional Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['mk_fakultas'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Mengulang MK Fakultas Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['mk_prodi'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Mengulang MK Prodi Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['nilai_d_melebihi_batas'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Nilai D Angkatan {$filters['tahun_masuk']}";
        } elseif (($filters['nilai_e'] ?? null) === 'yes' && !empty($filters['tahun_masuk'])) {
            return "Daftar Mahasiswa Nilai E Angkatan {$filters['tahun_masuk']}";
        } elseif (!empty($filters['status_mahasiswa'])) {
            return 'Daftar Mahasiswa Status ' . ucfirst($filters['status_mahasiswa']);
        } elseif (!empty($filters['status_ews'])) {
            return 'Daftar Mahasiswa Status EWS ' . ucfirst($filters['status_ews']);
        } elseif (!empty($filters['status_kelulusan']) && !empty($filters['tahun_masuk'])) {
            return 'Daftar Mahasiswa ' . ucfirst($filters['status_kelulusan']) . ' Angkatan ' . $filters['tahun_masuk'];
        } elseif (!empty($filters['status_kelulusan'])) {
            return 'Daftar Mahasiswa Status Kelulusan ' . ucfirst($filters['status_kelulusan']);
        }
        return $default;
    }
}
