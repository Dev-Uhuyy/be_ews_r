<?php

namespace App\Services\Koor;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;

class StatusMahasiswaService
{
    public function getDetailAngkatan($tahunMasuk, $search = null, $perPage = 10)
    {
        // Get detail mahasiswa per angkatan
        // Exclude mahasiswa yang sudah lulus dan DO
        $query = AkademikMahasiswa::select(
                    //'akademik_mahasiswa.id as akademik_id',
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    'dosen_users.name as nama_dosen_wali',
                    //'mahasiswa.status_mahasiswa',
                    'akademik_mahasiswa.semester_aktif',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.mk_nasional',
                    'akademik_mahasiswa.mk_fakultas',
                    'akademik_mahasiswa.mk_prodi',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'early_warning_system.status as status_ews',
                    'early_warning_system.status_kelulusan'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")'); // Exclude mahasiswa yang sudah lulus dan DO

        // Filter berdasarkan nama jika ada pencarian
        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        // Get total count before pagination for statistics
        $totalMahasiswa = $query->count();

        $mahasiswaList = $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);

        // Hitung rata-rata IPS per semester untuk angkatan ini
        $rataIpsPerSemester = [];
        for ($sem = 1; $sem <= 14; $sem++) {
            $avgIps = DB::table('ips_mahasiswa')
                ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereNotNull('ips_mahasiswa.ips_' . $sem)
                ->avg('ips_mahasiswa.ips_' . $sem);

            if ($avgIps !== null) {
                $rataIpsPerSemester[] = [
                    'semester' => $sem,
                    'rata_ips' => round($avgIps, 2)
                ];
            }
        }

        // Hitung distribusi status EWS untuk angkatan ini (exclude lulus dan DO)
        $distribusiEws = DB::table('early_warning_system')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select('early_warning_system.status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('early_warning_system.status')
            ->get()
            ->keyBy('status');

        $statsEws = [
            'tepat_waktu' => $distribusiEws->get('tepat_waktu')->jumlah ?? 0,
            'normal' => $distribusiEws->get('normal')->jumlah ?? 0,
            'perhatian' => $distribusiEws->get('perhatian')->jumlah ?? 0,
            'kritis' => $distribusiEws->get('kritis')->jumlah ?? 0,
        ];

        return [
            'paginated_data' => $mahasiswaList,
            'rata_ips_per_semester' => $rataIpsPerSemester,
            'distribusi_status_ews' => $statsEws,
            'total_mahasiswa' => $totalMahasiswa,
        ];
    }

    public function getDetailMahasiswa($mahasiswaId)
    {
        // Get detail mahasiswa dengan relasi yang dibutuhkan
        $mahasiswa = Mahasiswa::with([
                'user',
                'akademikmahasiswa.dosen_wali.user',
                'akademikmahasiswa.early_warning_systems',
                'ipsmahasiswa'
            ])
            ->where('id', $mahasiswaId)
            ->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademikMhs = $mahasiswa->akademikmahasiswa;
        $ews = $akademikMhs->early_warning_systems->first();

        // Get IP per semester dari IpsMahasiswa
        $ipPerSemester = [];
        if ($mahasiswa->ipsmahasiswa) {
            for ($i = 1; $i <= 14; $i++) {
                $ipsKey = "ips_$i";
                if ($mahasiswa->ipsmahasiswa->$ipsKey !== null) {
                    $ipPerSemester[] = [
                        'semester' => $i,
                        'ips' => round($mahasiswa->ipsmahasiswa->$ipsKey, 2)
                    ];
                }
            }
        }

        // Get detail mata kuliah dengan nilai D (hanya nilai TERAKHIR per mata kuliah)
        $matkulNilaiD = [];
        $totalSksNilaiD = 0;
        if ($akademikMhs->nilai_d_melebihi_batas === 'yes') {
            $matkulNilaiD = DB::table('khs_krs_mahasiswa as khs1')
                ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
                ->whereIn('khs1.id', function($query) use ($mahasiswaId) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('khs_krs_mahasiswa as khs2')
                        ->where('khs2.mahasiswa_id', $mahasiswaId)
                        ->groupBy('khs2.matakuliah_id');
                })
                ->where('khs1.mahasiswa_id', $mahasiswaId)
                ->where('khs1.nilai_akhir_huruf', 'D')
                ->select(
                    'mata_kuliahs.kode',
                    'mata_kuliahs.name as nama',
                    'mata_kuliahs.sks',
                    'khs1.nilai_akhir_huruf',
                    'khs1.nilai_akhir_angka',
                    'khs1.status'
                )
                ->get()
                ->toArray();

            // Hitung total SKS nilai D
            $totalSksNilaiD = array_sum(array_column($matkulNilaiD, 'sks'));
        }

        // Get detail mata kuliah dengan nilai E (hanya nilai TERAKHIR per mata kuliah)
        $matkulNilaiE = [];
        if ($akademikMhs->nilai_e === 'yes') {
            $matkulNilaiE = DB::table('khs_krs_mahasiswa as khs1')
                ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
                ->whereIn('khs1.id', function($query) use ($mahasiswaId) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('khs_krs_mahasiswa as khs2')
                        ->where('khs2.mahasiswa_id', $mahasiswaId)
                        ->groupBy('khs2.matakuliah_id');
                })
                ->where('khs1.mahasiswa_id', $mahasiswaId)
                ->where('khs1.nilai_akhir_huruf', 'E')
                ->select(
                    'mata_kuliahs.kode',
                    'mata_kuliahs.name as nama',
                    'mata_kuliahs.sks',
                    'khs1.nilai_akhir_huruf',
                    'khs1.nilai_akhir_angka',
                    'khs1.status'
                )
                ->get()
                ->toArray();
        }

        // Compile riwayat SPS
        $riwayatSps = [];
        if ($ews) {
            if ($ews->SPS1 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 1,
                    'status' => 'SPS1',
                    'keterangan' => 'IPS semester 1 < 2.0'
                ];
            }
            if ($ews->SPS2 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 2,
                    'status' => 'SPS2',
                    'keterangan' => 'IPS semester 2 < 2.0'
                ];
            }
            if ($ews->SPS3 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 3,
                    'status' => 'SPS3',
                    'keterangan' => 'IPS semester 3 < 2.0 (Wajib rekomitmen)'
                ];
            }
        }

        // Format data sesuai kebutuhan
        return [
            'id' => $mahasiswa->id,
            'nama' => $mahasiswa->user->name ?? null,
            'nim' => $mahasiswa->nim ?? null,
            'status_mahasiswa' => $mahasiswa->status_mahasiswa ?? null,
            'dosen_wali' => [
                'id' => $akademikMhs->dosen_wali->id ?? null,
                'nama' => $akademikMhs->dosen_wali->user->name ?? null,
            ],
            'akademik' => [
                'id' => $akademikMhs->id ?? null,
                'semester_aktif' => $akademikMhs->semester_aktif ?? null,
                'tahun_masuk' => $akademikMhs->tahun_masuk ?? null,
                'ipk' => $akademikMhs->ipk ?? 0,
                'sks_tempuh' => $akademikMhs->sks_tempuh ?? 0,
                'sks_lulus' => $akademikMhs->sks_lulus ?? 0,
                'mk_nasional' => $akademikMhs->mk_nasional ?? 'no',
                'mk_fakultas' => $akademikMhs->mk_fakultas ?? 'no',
                'mk_prodi' => $akademikMhs->mk_prodi ?? 'no',
                'nilai_d_melebihi_batas' => $akademikMhs->nilai_d_melebihi_batas ?? 'no',
                'nilai_e' => $akademikMhs->nilai_e ?? 'no',
                'total_sks_nilai_d' => $totalSksNilaiD,
                'max_sks_nilai_d' => round(($akademikMhs->sks_lulus ?? 144) * 0.05, 1), // 5% dari SKS lulus
            ],
            'status_ews' => $ews->status ?? null,
            'status_kelulusan' => $ews->status_kelulusan ?? null,
            'ip_per_semester' => $ipPerSemester,
            'mata_kuliah_nilai_d' => $matkulNilaiD,
            'mata_kuliah_nilai_e' => $matkulNilaiE,
            'riwayat_sps' => $riwayatSps,
        ];
    }

    /**
     * Get distribusi status EWS (tepat_waktu, normal, perhatian, kritis)
     * Exclude mahasiswa yang sudah lulus dan DO (include: aktif, cuti, mangkir)
     * @param $tahunMasuk Filter by tahun_masuk (optional)
     */
    public function getDistribusiStatusEws($tahunMasuk = null)
    {
        $query = EarlyWarningSystem::select('early_warning_system.status', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $distribusi = $query->groupBy('early_warning_system.status')
            ->get()
            ->keyBy('status');

        return [
            'tepat_waktu' => $distribusi->get('tepat_waktu')?->jumlah ?? 0,
            'normal' => $distribusi->get('normal')?->jumlah ?? 0,
            'perhatian' => $distribusi->get('perhatian')?->jumlah ?? 0,
            'kritis' => $distribusi->get('kritis')?->jumlah ?? 0,
        ];
    }

    public function getTableRingkasanStatus()
    {
        //Angkatan, total mhs, ipk<2, mangkir, cuti 2x, normal, perhatian, kritis
        // Exclude mahasiswa yang sudah lulus dan DO
        $tableData = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_kurang_dari_2'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    //DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
                    //DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();

        return $tableData;
    }

    /**
     * Get all mahasiswa with complete details
     * Include syarat kelulusan, status EWS, akademik data
     * Exclude mahasiswa yang sudah lulus dan DO
     * @param string|null $search Search by name or NIM (works for both modes)
     * @param int $perPage Items per page
     * @param string $mode 'simple' (nama, nim, doswal with filters) or 'detailed' (all fields, no filters)
     * @param array $filters Additional filters (ONLY applied in simple mode): status_mahasiswa, status_ews, status_kelulusan, tahun_masuk, etc
     */
    public function getMahasiswaAll($search = null, $perPage = 10, $mode = 'simple', $filters = [])
    {
        if ($mode === 'simple') {
            // Simple mode: hanya nama, nim, dosen wali (BISA filter by banyak field)
            $query = Mahasiswa::select(
                        'mahasiswa.id as mahasiswa_id',
                        'mahasiswa.nim',
                        'users.name as nama_lengkap',
                        'dosen_users.name as nama_dosen_wali'
                    )
                    ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                    ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                    ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                    ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        } else {
            // Detailed mode: semua field (TANPA filter tambahan, hanya tampilkan semua)
            $query = Mahasiswa::select(
                        'mahasiswa.id as mahasiswa_id',
                        'mahasiswa.nim',
                        'users.name as nama_lengkap',
                        'mahasiswa.status_mahasiswa',
                        'dosen_users.name as nama_dosen_wali',
                        'akademik_mahasiswa.semester_aktif',
                        'akademik_mahasiswa.tahun_masuk',
                        'akademik_mahasiswa.ipk',
                        'akademik_mahasiswa.sks_lulus',
                        'akademik_mahasiswa.sks_tempuh',
                        'akademik_mahasiswa.mk_nasional',
                        'akademik_mahasiswa.mk_fakultas',
                        'akademik_mahasiswa.mk_prodi',
                        'akademik_mahasiswa.nilai_d_melebihi_batas',
                        'akademik_mahasiswa.nilai_e',
                        'early_warning_system.status as status_ews',
                        'early_warning_system.status_kelulusan'
                    )
                    ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                    ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                    ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                    ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        }

        // Filter berdasarkan nama atau NIM jika ada pencarian
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Apply additional filters ONLY for simple mode
        if ($mode === 'simple') {
            if (!empty($filters['status_mahasiswa'])) {
                $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = ?', [strtolower($filters['status_mahasiswa'])]);
            }

            if (!empty($filters['status_ews'])) {
                $query->where('early_warning_system.status', $filters['status_ews']);
            }

            if (!empty($filters['status_kelulusan'])) {
                $query->where('early_warning_system.status_kelulusan', $filters['status_kelulusan']);
            }

            if (!empty($filters['tahun_masuk'])) {
                $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
            }

            if (!empty($filters['semester_aktif'])) {
                $query->where('akademik_mahasiswa.semester_aktif', $filters['semester_aktif']);
            }

            if (!empty($filters['mk_nasional'])) {
                $query->where('akademik_mahasiswa.mk_nasional', $filters['mk_nasional']);
            }

            if (!empty($filters['mk_fakultas'])) {
                $query->where('akademik_mahasiswa.mk_fakultas', $filters['mk_fakultas']);
            }

            if (!empty($filters['mk_prodi'])) {
                $query->where('akademik_mahasiswa.mk_prodi', $filters['mk_prodi']);
            }

            if (!empty($filters['nilai_d_melebihi_batas'])) {
                $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', $filters['nilai_d_melebihi_batas']);
            }

            if (!empty($filters['nilai_e'])) {
                $query->where('akademik_mahasiswa.nilai_e', $filters['nilai_e']);
            }
        }

        return $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);
    }
}
