<?php

namespace App\Services;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;

class KoorService
{
    //Dashboard Koor

    public function getStatusMahasiswa()
    {
        // Exclude mahasiswa yang sudah lulus dan DO dari total
        $totalMahasiswa = Mahasiswa::whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status_mahasiswa')
            ->get()
            ->keyBy('status_mahasiswa');

        return [
            'total' => $totalMahasiswa,
            'aktif' => ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0),
            'mangkir' => ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0),
            'cuti' => ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0),
            'do' => ($statusBreakdown->get('do')->jumlah ?? 0) + ($statusBreakdown->get('DO')->jumlah ?? 0),
            'lulus' => ($statusBreakdown->get('lulus')->jumlah ?? 0) + ($statusBreakdown->get('Lulus')->jumlah ?? 0),
        ];
    }

    public function getRataIpkPerAngkatan()
    {
        return AkademikMahasiswa::select('tahun_masuk', DB::raw('AVG(ipk) as rata_ipk'), DB::raw('COUNT(*) as jumlah_mahasiswa'))
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    /**
     * Get status kelulusan dari table early_warning_system
     * Exclude mahasiswa yang sudah lulus dan DO
     */
    public function getStatusKelulusan()
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return [
            'total' => $eligible + $noneligible,
            'eligible' => $eligible,
            'tidak_eligible' => $noneligible,
        ];
    }

    public function getTableRingkasanMahasiswa($perPage = 10)
    {
        // Angkatan, jml mhs, aktif, cuti, mangkir, ipk rata2, tepat waktu, normal, perhatian, kritis
        // Exclude mahasiswa yang sudah lulus dan DO
        return AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as rata_ipk'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")') // Exclude mahasiswa yang sudah lulus dan DO
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->paginate($perPage);
    }

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


    // Satus Mahasiswa
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


    //Capaian Mahasiswa

    //angkatan, tren ips(naik/turun), mk_gagal ada berapa, mk_ulang ada berapa
    public function getTrenIPSAll($tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select(
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('AVG(akademik_mahasiswa.ipk) as rata_ipk'),
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as jumlah_mahasiswa')
            )
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereNotNull('akademik_mahasiswa.ipk')
            ->where('akademik_mahasiswa.ipk', '>', 0)
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $result = $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();

        // Hitung tren IPS untuk setiap angkatan
        $resultWithTren = [];

        foreach ($result as $index => $item) {
            // Ambil semester aktif mayoritas untuk angkatan ini
            $semesterAktif = DB::table('akademik_mahasiswa')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->select('akademik_mahasiswa.semester_aktif')
                ->groupBy('akademik_mahasiswa.semester_aktif')
                ->orderByRaw('COUNT(*) DESC')
                ->value('semester_aktif');

            $tren = 'stabil';

            if ($semesterAktif && $semesterAktif >= 3) {
                // Hitung rata-rata IPS semester (semester_aktif - 2)
                $semesterPrev2 = $semesterAktif - 2;
                $semesterPrev1 = $semesterAktif - 1;

                $avgIpsPrev2 = DB::table('ips_mahasiswa')
                    ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                    ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                    ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev2)
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                    ->avg('ips_mahasiswa.ips_' . $semesterPrev2);

                $avgIpsPrev1 = DB::table('ips_mahasiswa')
                    ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                    ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                    ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev1)
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                    ->avg('ips_mahasiswa.ips_' . $semesterPrev1);

                // Bandingkan average IPS semester prev1 dengan prev2
                if ($avgIpsPrev2 !== null && $avgIpsPrev1 !== null) {
                    if ($avgIpsPrev1 > $avgIpsPrev2) {
                        $tren = 'naik';
                    } elseif ($avgIpsPrev1 < $avgIpsPrev2) {
                        $tren = 'turun';
                    }
                }
            }

            // Hitung jumlah mata kuliah gagal (nilai E) per angkatan
            $mkGagal = DB::table('khs_krs_mahasiswa')
                ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->count();

            // Hitung jumlah mata kuliah ulang (retake - matakuliah_id yang muncul > 1x)
            $mkUlang = DB::table('khs_krs_mahasiswa')
                ->select('khs_krs_mahasiswa.mahasiswa_id', 'khs_krs_mahasiswa.matakuliah_id', DB::raw('COUNT(*) as jumlah'))
                ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('khs_krs_mahasiswa.mahasiswa_id', 'khs_krs_mahasiswa.matakuliah_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            $resultWithTren[] = [
                'tahun_masuk' => $item->tahun_masuk,
                'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                'tren_ips' => $tren,
                'mk_gagal' => $mkGagal,
                'mk_ulang' => $mkUlang,
            ];
        }

        return $resultWithTren;
    }

    //rata2 ips mahasiswa, mahasiswa turun ip, mahasiswa naik ip nanti pake params untuk angkatan
    public function getCardCapaianMahasiswa($tahunMasuk = null)
    {
        // Get all angkatan or specific one
        $query = AkademikMahasiswa::select('akademik_mahasiswa.tahun_masuk')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $angkatanList = $query->get();

        $totalMahasiswa = 0;
        $totalTurun = 0;
        $totalNaik = 0;
        $trenPerAngkatan = [];

        foreach ($angkatanList as $angkatan) {
            // Get semester aktif mayoritas untuk angkatan ini
            $semesterAktif = DB::table('akademik_mahasiswa')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $angkatan->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->select('akademik_mahasiswa.semester_aktif')
                ->groupBy('akademik_mahasiswa.semester_aktif')
                ->orderByRaw('COUNT(*) DESC')
                ->value('semester_aktif');

            if (!$semesterAktif || $semesterAktif < 3) {
                continue; // Skip if semester too early to compare
            }

            $semesterPrev2 = $semesterAktif - 2; // e.g., semester 4 if currently semester 6
            $semesterPrev1 = $semesterAktif - 1; // e.g., semester 5 if currently semester 6

            // Get all mahasiswa for this angkatan with their IPS for prev2 and prev1 semester
            $mahasiswaIps = DB::table('ips_mahasiswa')
                ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $angkatan->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev2)
                ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev1)
                ->select(
                    'ips_mahasiswa.mahasiswa_id',
                    'ips_mahasiswa.ips_' . $semesterPrev2 . ' as ips_prev2',
                    'ips_mahasiswa.ips_' . $semesterPrev1 . ' as ips_prev1'
                )
                ->get();

            $jumlahMahasiswa = $mahasiswaIps->count();
            $naik = 0;
            $turun = 0;
            $stabil = 0;
            $totalIpsPrev1 = 0;

            foreach ($mahasiswaIps as $mhs) {
                $totalIpsPrev1 += $mhs->ips_prev1;

                if ($mhs->ips_prev1 > $mhs->ips_prev2) {
                    $naik++;
                } elseif ($mhs->ips_prev1 < $mhs->ips_prev2) {
                    $turun++;
                } else {
                    $stabil++;
                }
            }

            $rataIps = $jumlahMahasiswa > 0 ? round($totalIpsPrev1 / $jumlahMahasiswa, 2) : 0;

            $totalMahasiswa += $jumlahMahasiswa;
            $totalNaik += $naik;
            $totalTurun += $turun;

            $trenPerAngkatan[] = [
                'tahun_masuk' => $angkatan->tahun_masuk,
                'semester_aktif' => $semesterAktif,
                'rata_ips' => $rataIps, // rata-rata IPS semester terakhir yang sudah selesai
                'jumlah_mahasiswa' => $jumlahMahasiswa,
                'mahasiswa_naik_ip' => $naik,
                'mahasiswa_turun_ip' => $turun,
                'mahasiswa_stabil_ip' => $stabil,
            ];
        }

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_turun_ip' => $totalTurun,
            'total_naik_ip' => $totalNaik,
            'tren_per_angkatan' => $trenPerAngkatan,
        ];
    }

    public function getTopTenMKGagalAll()
    {
        // Get top 10 mata kuliah dengan jumlah mahasiswa gagal (nilai E) terbanyak
        $topMKGagal = DB::table('khs_krs_mahasiswa')
            ->join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                'mata_kuliahs.kode',
                'mata_kuliahs.name as nama',
                'mata_kuliahs.koordinator_mk',
                DB::raw('COUNT(*) as jumlah_gagal')
            )
            ->groupBy('khs_krs_mahasiswa.matakuliah_id', 'mata_kuliahs.kode', 'mata_kuliahs.name', 'mata_kuliahs.koordinator_mk')
            ->orderByDesc('jumlah_gagal')
            ->limit(10)
            ->get();

        // Load nama lengkap koordinator menggunakan accessor dari model Dosen
        $koordinatorIds = $topMKGagal->pluck('koordinator_mk')->filter()->unique();
        $koordinators = \App\Models\Dosen::with('user')->whereIn('id', $koordinatorIds)->get()->keyBy('id');

        // Enhance data dengan nama lengkap koordinator
        foreach ($topMKGagal as $mk) {
            if ($mk->koordinator_mk && isset($koordinators[$mk->koordinator_mk])) {
                $mk->dosen_koordinator = $koordinators[$mk->koordinator_mk]->nama_lengkap;
            } else {
                $mk->dosen_koordinator = '-';
            }
            unset($mk->koordinator_mk); // Remove ID, hanya tampilkan nama
        }

        return $topMKGagal;
    }

    public function getMahasiswaMKGagal($search = null, $perPage = 10, $filters = [])
    {
        // Get mahasiswa yang nilai TERAKHIR per mata kuliah adalah E (belum memperbaiki)
        // Jika sudah retake dan tidak dapat E lagi, maka tidak termasuk dalam daftar
        $query = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('kelompok_mata_kuliah', 'khs1.kelompok_id', '=', 'kelompok_mata_kuliah.id')
            ->join('akademik_mahasiswa', 'khs1.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('users', 'mahasiswa.user_id', '=', 'users.id')
            ->whereIn('khs1.id', function($subquery) {
                // Ambil record terakhir (MAX id) per mahasiswa per mata kuliah
                $subquery->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->whereColumn('khs2.mahasiswa_id', 'khs1.mahasiswa_id')
                    ->whereColumn('khs2.matakuliah_id', 'khs1.matakuliah_id')
                    ->groupBy('khs2.mahasiswa_id', 'khs2.matakuliah_id');
            })
            ->where('khs1.nilai_akhir_huruf', 'E') // Filter hanya yang nilai terakhir adalah E
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        // Search by nama
        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        // Filter by nama_matkul
        if (!empty($filters['nama_matkul'])) {
            $query->where('mata_kuliahs.name', 'LIKE', '%' . $filters['nama_matkul'] . '%');
        }

        // Filter by kode_kelompok
        if (!empty($filters['kode_kelompok'])) {
            $query->where('kelompok_mata_kuliah.kode', $filters['kode_kelompok']);
        }

        $mahasiswaGagal = $query->select(
                'users.name as nama',
                'mahasiswa.nim',
                'mata_kuliahs.name as nama_matkul',
                'mata_kuliahs.kode as kode_matkul',
                'kelompok_mata_kuliah.kode as kode_kelompok',
                'khs1.absen as presensi'
            )
            ->orderBy('users.name')
            ->orderBy('mata_kuliahs.kode')
            ->paginate($perPage);

        return $mahasiswaGagal;
    }

    // Sattistik kelulusan

    public function getCardStatistikKelulusan($tahunMasuk = null)
    {
        // berisi mahasiswa eligible/noneligible, aktif, mangkir, cuti, sebaran IPK (< 2.5 , 2.5-3, > 3.0), Pemenuhan MK Nasional/Fakultas/Prodi
        // dengan filter angkatan (tahun_masuk) dan exclude mahasiswa yang sudah lulus dan DO
        $query = AkademikMahasiswa::select(
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as noneligible'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2.5 THEN 1 ELSE 0 END) as ipk_kurang_dari_2_5'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk >= 2.5 AND akademik_mahasiswa.ipk <= 3.0 THEN 1 ELSE 0 END) as ipk_antara_2_5_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk > 3.0 THEN 1 ELSE 0 END) as ipk_lebih_dari_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_nasional = "yes" THEN 1 ELSE 0 END) as mk_nasional'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_fakultas = "yes" THEN 1 ELSE 0 END) as mk_fakultas'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_prodi = "yes" THEN 1 ELSE 0 END) as mk_prodi')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->first();
    }

    public function getTableStatistikKelulusan($perPage = 10)
    {
        //berisi angkatan, jmlh mhs, ipk < 2 , sks<144, nilai D, nilai E, eligible, noneligible, ipk rata2.
        //exclude mahasiswa yang sudah lulus dan DO
        $tableData = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_kurang_dari_2'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_melebihi_batas'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as nilai_e'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as noneligible'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata2')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');

        return $tableData->paginate($perPage);
    }

    // Tindak lanjut prodi

    /**
     * Get data surat rekomitmen mahasiswa
     * @param string|null $search Search by id_rekomitmen
     * @param int|null $tahunMasuk Filter by angkatan
     * @param string|null $statusRekomitmen Filter by status_rekomitmen (yes/no)
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSuratRekomitmen($search = null, $tahunMasuk = null, $statusRekomitmen = null, $perPage = 10)
    {
        // Ambil data mahasiswa dengan surat rekomitmen
        // Hanya tampilkan yang sudah punya id_rekomitmen (sudah mengajukan rekomitmen)
        $query = EarlyWarningSystem::select(
                    'early_warning_system.id_rekomitmen as id_tiket',
                    'users.name as nama',
                    'mahasiswa.nim',
                    'early_warning_system.tanggal_pengajuan_rekomitmen as tanggal_pengajuan',
                    'dosen_users.name as dosen_wali',
                    'early_warning_system.status_rekomitmen as status_tindak_lanjut',
                    'early_warning_system.link_rekomitmen'
                )
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->whereNotNull('early_warning_system.id_rekomitmen'); // Hanya yang punya id_rekomitmen

        // Search by id_tiket
        if ($search) {
            $query->where('early_warning_system.id_rekomitmen', 'LIKE', '%' . $search . '%');
        }

        // Filter by tahun_masuk
        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        // Filter by status_tindak_lanjut (status_rekomitmen)
        if ($statusRekomitmen) {
            $query->where('early_warning_system.status_rekomitmen', $statusRekomitmen);
        }

        return $query->orderBy('early_warning_system.tanggal_pengajuan_rekomitmen', 'desc')
                    ->orderBy('mahasiswa.nim', 'asc')
                    ->paginate($perPage);
    }

    public function updateStatusRekomitmen($idRekomitmen, $status)
    {
        // Update status rekomitmen
        $rekomitmen = EarlyWarningSystem::where('id_rekomitmen', $idRekomitmen)->first();

        if (!$rekomitmen) {
            return ['success' => false, 'message' => 'Rekomitmen tidak ditemukan'];
        }
        $rekomitmen->status_rekomitmen = $status;
        $rekomitmen->save();

        return ['success' => true, 'message' => 'Status rekomitmen berhasil diperbarui'];
    }

}

