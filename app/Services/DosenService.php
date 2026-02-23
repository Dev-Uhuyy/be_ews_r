<?php

namespace App\Services;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Dosen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DosenService
{
    protected $dosenId;

    public function __construct()
    {
        // Get dosen_id from authenticated user
        $user = Auth::user();
        if ($user) {
            $dosen = Dosen::where('user_id', $user->id)->first();
            $this->dosenId = $dosen ? $dosen->id : null;
        }
    }

    // Dashboard Dosen

    public function getStatusMahasiswa()
    {
        // Exclude mahasiswa yang sudah lulus dan DO dari total
        // Hanya mahasiswa yang di-wali-kan oleh dosen ini
        $totalMahasiswa = Mahasiswa::join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
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
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    public function getStatusKelulusan()
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
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
        $tahunMasukQuery = AkademikMahasiswa::select(
                'tahun_masuk',
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as total_mahasiswa')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc');

        // Get paginated tahun_masuk
        $paginatedTahunMasuk = $tahunMasukQuery->paginate($perPage);

        // Get detailed stats for each tahun_masuk in current page
        $tahunMasukList = $paginatedTahunMasuk->pluck('tahun_masuk');

        $result = [];

        foreach ($tahunMasukList as $tahunMasuk) {
            $totalMahasiswa = AkademikMahasiswa::where('tahun_masuk', $tahunMasuk)
                ->where('dosen_wali_id', $this->dosenId)
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->count();

            $statusEwsData = EarlyWarningSystem::select('status', DB::raw('COUNT(*) as jumlah'))
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $result[] = [
                'tahun_masuk' => $tahunMasuk,
                'total_mahasiswa' => $totalMahasiswa,
                'tepat_waktu' => $statusEwsData->get('tepat_waktu')->jumlah ?? 0,
                'normal' => $statusEwsData->get('normal')->jumlah ?? 0,
                'perhatian' => $statusEwsData->get('perhatian')->jumlah ?? 0,
                'kritis' => $statusEwsData->get('kritis')->jumlah ?? 0,
            ];
        }

        // Replace items with detailed result
        $paginatedTahunMasuk->setCollection(collect($result));

        return $paginatedTahunMasuk;
    }

    // General

    public function getDetailAngkatan($tahunMasuk, $search = null, $perPage = 10)
    {
        $query = AkademikMahasiswa::select(
                'mahasiswa.id as mahasiswa_id',
                'mahasiswa.nim',
                'users.name as nama_lengkap',
                'dosen_users.name as nama_dosen_wali',
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
                'early_warning_system.status_kelulusan',
                'early_warning_system.SPS1',
                'early_warning_system.SPS2',
                'early_warning_system.SPS3'
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
            ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
            ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Get total count before pagination for statistics
        $totalMahasiswa = $query->count();

        $paginatedData = $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);

        // Hitung rata-rata IPS per semester untuk angkatan ini
        $rataIpsPerSemester = [];
        for ($sem = 1; $sem <= 14; $sem++) {
            $avgIps = DB::table('ips_mahasiswa')
                ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
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
        $distribusiStatusEws = EarlyWarningSystem::select('status', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statsEws = [
            'tepat_waktu' => $distribusiStatusEws->get('tepat_waktu')->jumlah ?? 0,
            'normal' => $distribusiStatusEws->get('normal')->jumlah ?? 0,
            'perhatian' => $distribusiStatusEws->get('perhatian')->jumlah ?? 0,
            'kritis' => $distribusiStatusEws->get('kritis')->jumlah ?? 0,
        ];

        return [
            'paginated_data' => $paginatedData,
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
            ->whereHas('akademikmahasiswa', function($query) {
                $query->where('dosen_wali_id', $this->dosenId);
            })
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
                        ->from('khs_krs_mahasiswa')
                        ->where('mahasiswa_id', $mahasiswaId)
                        ->groupBy('matakuliah_id');
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
                        ->from('khs_krs_mahasiswa')
                        ->where('mahasiswa_id', $mahasiswaId)
                        ->groupBy('matakuliah_id');
                })
                ->where('khs1.mahasiswa_id', $mahasiswaId)
                ->where('khs1.nilai_akhir_huruf', 'E')
                ->select(
                    'mata_kuliahs.kode',
                    'mata_kuliahs.name as nama',
                    'mata_kuliahs.sks',
                    'khs1.nilai_akhir_huruf',
                    'khs1.nilai_akhir_angka'
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

    public function getMahasiswaAll($search = null, $perPage = 10, $mode = 'simple', $filters = [])
    {
        if ($mode === 'simple') {
            $query = Mahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    'dosen_users.name as nama_dosen_wali'
                )
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        } elseif ($mode === 'perwalian') {
            // Perwalian mode: untuk dosen wali - nama, nim, semester, ipk, SPS, status
            $query = Mahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    'akademik_mahasiswa.semester_aktif',
                    'akademik_mahasiswa.ipk',
                    'early_warning_system.SPS1',
                    'early_warning_system.SPS2',
                    'early_warning_system.SPS3',
                    'early_warning_system.status as status_ews'
                )
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        } else {
            // detailed mode
            $query = Mahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    'mahasiswa.status_mahasiswa',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.semester_aktif',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.sks_lulus',
                    'dosen_users.name as nama_dosen_wali',
                    'early_warning_system.status as status_ews',
                    'early_warning_system.status_kelulusan',
                    'akademik_mahasiswa.mk_nasional',
                    'akademik_mahasiswa.mk_fakultas',
                    'akademik_mahasiswa.mk_prodi',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'early_warning_system.SPS1',
                    'early_warning_system.SPS2',
                    'early_warning_system.SPS3'
                )
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        }

        // Filter berdasarkan nama atau NIM jika ada pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Apply additional filters ONLY for simple mode
        if ($mode === 'simple') {
            $ewsJoined = false; // Track if early_warning_system is already joined

            if (!empty($filters['status_mahasiswa'])) {
                $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = ?', [strtolower($filters['status_mahasiswa'])]);
            }

            if (!empty($filters['status_ews'])) {
                $query->join('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                    ->where('early_warning_system.status', $filters['status_ews']);
                $ewsJoined = true;
            }

            if (!empty($filters['status_kelulusan'])) {
                if (!$ewsJoined) {
                    $query->join('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id');
                    $ewsJoined = true;
                }
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

            // Filter SPS (requires early_warning_system join)
            if (!empty($filters['SPS1'])) {
                if (!$ewsJoined) {
                    $query->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id');
                    $ewsJoined = true;
                }
                $query->where('early_warning_system.SPS1', $filters['SPS1']);
            }

            if (!empty($filters['SPS2'])) {
                if (!$ewsJoined) {
                    $query->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id');
                    $ewsJoined = true;
                }
                $query->where('early_warning_system.SPS2', $filters['SPS2']);
            }

            if (!empty($filters['SPS3'])) {
                if (!$ewsJoined) {
                    $query->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id');
                    $ewsJoined = true;
                }
                $query->where('early_warning_system.SPS3', $filters['SPS3']);
            }
        }

        return $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);
    }

    // Status Mahasiswa

    public function getDistribusiStatusEws($tahunMasuk = null)
    {
        $query = EarlyWarningSystem::select('status', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $data = $query->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'tepat_waktu' => $data->get('tepat_waktu')->jumlah ?? 0,
            'normal' => $data->get('normal')->jumlah ?? 0,
            'perhatian' => $data->get('perhatian')->jumlah ?? 0,
            'kritis' => $data->get('kritis')->jumlah ?? 0,
        ];
    }

    public function getTableRingkasanStatus()
    {
        $tahunMasukList = AkademikMahasiswa::select('tahun_masuk')
            ->where('dosen_wali_id', $this->dosenId)
            ->whereNotNull('tahun_masuk')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->pluck('tahun_masuk');

        $result = [];

        foreach ($tahunMasukList as $tahunMasuk) {
            $totalMahasiswa = AkademikMahasiswa::where('tahun_masuk', $tahunMasuk)
                ->where('dosen_wali_id', $this->dosenId)
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->count();

            $statusEwsData = EarlyWarningSystem::select('status', DB::raw('COUNT(*) as jumlah'))
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $result[] = [
                'tahun_masuk' => $tahunMasuk,
                'total_mahasiswa' => $totalMahasiswa,
                'tepat_waktu' => $statusEwsData->get('tepat_waktu')->jumlah ?? 0,
                'normal' => $statusEwsData->get('normal')->jumlah ?? 0,
                'perhatian' => $statusEwsData->get('perhatian')->jumlah ?? 0,
                'kritis' => $statusEwsData->get('kritis')->jumlah ?? 0,
            ];
        }

        return $result;
    }

    // Statistik Kelulusan

    public function getCardStatistikKelulusan($tahunMasuk = null)
    {
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
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->first();
    }

    public function getTableStatistikKelulusan($perPage = 10)
    {
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
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');

        return $tableData->paginate($perPage);
    }
}
