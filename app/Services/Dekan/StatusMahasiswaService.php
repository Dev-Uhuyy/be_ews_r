<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatusMahasiswaService
{
    public function getDetailAngkatan($tahunMasuk, $search = null, $perPage = 10)
    {
        // Get detail mahasiswa per angkatan
        // Exclude mahasiswa yang sudah lulus dan DO
        $query = AkademikMahasiswa::query()
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->select(
                    'prodis.nama as nama_prodi',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    DB::raw("CONCAT(COALESCE(CONCAT(dosen.gelar_depan, ' '), ''), dosen_users.name, COALESCE(CONCAT(' ', dosen.gelar_belakang), '')) as nama_dosen_wali"),
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
                    'akademik_mahasiswa.mahasiswa_id'
                )
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        // Filter berdasarkan nama jika ada pencarian
        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        // Apply prodi scope
        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }

        // Get total count before pagination for statistics
        $totalMahasiswa = $query->count();

        $mahasiswaList = $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);

        // Get mandatory MKs once to avoid N+1 queries.
        $mandatoryQuery = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultas', 'prodi']);
            
        if ($user && $user->hasRole('kaprodi')) {
            $mandatoryQuery->where('prodi_id', $user->prodi_id);
        } elseif ($user && $user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
            $mandatoryQuery->where('prodi_id', request('prodi_id'));
        }

        $mandatoryMKsByCategory = $mandatoryQuery->get()->groupBy('tipe_mk');

        // Tambahkan informasi detail nilai D dan E serta MK yang belum lulus
        $mahasiswaList->getCollection()->transform(function ($mahasiswa) use ($mandatoryMKsByCategory) {
            // Get all latest grades for this student (nilai TERAKHIR per mata kuliah)
            $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
                ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
                ->whereIn('khs1.id', function($query) use ($mahasiswa) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('khs_krs_mahasiswa as khs2')
                        ->where('khs2.mahasiswa_id', $mahasiswa->mahasiswa_id)
                        ->groupBy('khs2.matakuliah_id');
                })
                ->where('khs1.mahasiswa_id', $mahasiswa->mahasiswa_id)
                ->select(
                    'mata_kuliahs.id as matakuliah_id',
                    'mata_kuliahs.name as nama',
                    'mata_kuliahs.kode',
                    'mata_kuliahs.sks',
                    'mata_kuliahs.tipe_mk',
                    'khs1.nilai_akhir_huruf'
                )
                ->get();

            // 1. Nilai E
            $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
            $mahasiswa->jumlah_nilai_e = $matkulNilaiE->count();
            $mahasiswa->sks_nilai_e = $matkulNilaiE->sum('sks');
            $mahasiswa->nilai_e_detail = $matkulNilaiE->pluck('nama')->toArray();
            
            // Map output nilai_e: 'yes' means requirement met (NO E grades)
            // Existing field in DB stores 'yes' if HAS E. So we invert it for the user logic.
            $mahasiswa->nilai_e = ($mahasiswa->jumlah_nilai_e === 0) ? 'yes' : 'no';

            // 2. Nilai D
            $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
            $mahasiswa->jumlah_nilai_d = $matkulNilaiD->count();
            $mahasiswa->sks_nilai_d = $matkulNilaiD->sum('sks');
            $mahasiswa->nilai_d_detail = $matkulNilaiD->pluck('nama')->toArray();

            // 3. MK Nasional/Fakultas/Prodi Missing (jika status 'no')
            $categories = ['nasional', 'fakultas', 'prodi'];
            foreach ($categories as $cat) {
                $field = "mk_$cat";
                $detailField = "mk_{$cat}_detail"; // Using _detail as requested implicitly
                
                if ($mahasiswa->$field === 'no') {
                    $prodiMandatory = $mandatoryMKsByCategory->get($cat) ?? collect();
                    
                    $missing = [];
                    foreach ($prodiMandatory as $mk) {
                        $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                        // Belum lulus: Belum ambil ATAU nilai E
                        if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                            $missing[] = $mk->name;
                        }
                    }
                    $mahasiswa->$detailField = $missing;
                } else {
                    $mahasiswa->$detailField = [];
                }
            }

            return $mahasiswa;
        });

        // Hitung rata-rata IPS per semester untuk angkatan ini
        $rataIpsPerSemester = [];
        for ($sem = 1; $sem <= 14; $sem++) {
            $avgIpsQuery = DB::table('ips_mahasiswa')
                ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereNotNull('ips_mahasiswa.ips_' . $sem);

            if ($user) {
                if ($user->hasRole('kaprodi')) {
                    $avgIpsQuery->where('mahasiswa.prodi_id', $user->prodi_id);
                } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                    $avgIpsQuery->where('mahasiswa.prodi_id', request('prodi_id'));
                }
            }

            $avgIps = $avgIpsQuery->avg('ips_mahasiswa.ips_' . $sem);

            if ($avgIps !== null) {
                $rataIpsPerSemester[] = [
                    'semester' => $sem,
                    'rata_ips' => round($avgIps, 2)
                ];
            }
        }

        // Hitung distribusi status EWS untuk angkatan ini (exclude lulus dan DO)
        $distribusiEwsQuery = DB::table('early_warning_system')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $distribusiEwsQuery->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $distribusiEwsQuery->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }

        $distribusiEws = $distribusiEwsQuery->select('early_warning_system.status', DB::raw('COUNT(*) as jumlah'))
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

    public function getDetailAngkatanExport($tahunMasuk, $search = null)
    {
        $query = AkademikMahasiswa::query()
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->select(
                    'prodis.nama as nama_prodi',
                    'mahasiswa.nim',
                    'users.name as nama_lengkap',
                    DB::raw("CONCAT(COALESCE(CONCAT(dosen.gelar_depan, ' '), ''), dosen_users.name, COALESCE(CONCAT(' ', dosen.gelar_belakang), '')) as nama_dosen_wali"),
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
                    'akademik_mahasiswa.mahasiswa_id'
                )
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }

        $data = $query->orderBy('mahasiswa.nim', 'asc')->get();

        $mandatoryQuery = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultas', 'prodi']);
            
        if ($user && $user->hasRole('kaprodi')) {
            $mandatoryQuery->where('prodi_id', $user->prodi_id);
        } elseif ($user && $user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
            $mandatoryQuery->where('prodi_id', request('prodi_id'));
        }

        $mandatoryMKsByCategory = $mandatoryQuery->get()->groupBy('tipe_mk');

        $data->transform(function ($mahasiswa) use ($mandatoryMKsByCategory) {
            $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
                ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
                ->whereIn('khs1.id', function($query) use ($mahasiswa) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('khs_krs_mahasiswa as khs2')
                        ->where('khs2.mahasiswa_id', $mahasiswa->mahasiswa_id)
                        ->groupBy('khs2.matakuliah_id');
                })
                ->where('khs1.mahasiswa_id', $mahasiswa->mahasiswa_id)
                ->select('mata_kuliahs.id as matakuliah_id', 'mata_kuliahs.name as nama', 'mata_kuliahs.sks', 'khs1.nilai_akhir_huruf')
                ->get();

            $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
            $mahasiswa->jumlah_nilai_e = $matkulNilaiE->count();
            $mahasiswa->nilai_e_detail = $matkulNilaiE->pluck('nama')->toArray();
            $mahasiswa->nilai_e = ($mahasiswa->jumlah_nilai_e === 0) ? 'yes' : 'no';

            $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
            $mahasiswa->jumlah_nilai_d = $matkulNilaiD->count();
            $mahasiswa->nilai_d_detail = $matkulNilaiD->pluck('nama')->toArray();

            $categories = ['nasional', 'fakultas', 'prodi'];
            foreach ($categories as $cat) {
                $field = "mk_$cat";
                $detailField = "mk_{$cat}_detail";
                if ($mahasiswa->$field === 'no') {
                    $prodiMandatory = $mandatoryMKsByCategory->get($cat) ?? collect();
                    $missing = [];
                    foreach ($prodiMandatory as $mk) {
                        $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                        if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                            $missing[] = $mk->name;
                        }
                    }
                    $mahasiswa->$detailField = $missing;
                } else {
                    $mahasiswa->$detailField = [];
                }
            }

            return $mahasiswa;
        });

        return $data;
    }

    public function getDetailMahasiswa($mahasiswaId)
    {
        // Get detail mahasiswa dengan relasi yang dibutuhkan
        $mahasiswa = Mahasiswa::with([
                'user',
                'akademikmahasiswa.dosenWali.user',
                'akademikmahasiswa.earlyWarningSystem',
                'ipsmahasiswa'
            ])
            ->where('id', $mahasiswaId)
            ->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademikMhs = $mahasiswa->akademikmahasiswa;
        if (!$akademikMhs) {
            return null;
        }
        $ews = $akademikMhs->earlyWarningSystem;

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

        $mandatoryQuery = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultas', 'prodi']);
            
        // Use student's prodi to determine mandatory courses
        if ($mahasiswa->prodi_id) {
            $mandatoryQuery->where('prodi_id', $mahasiswa->prodi_id);
        }

        $mandatoryMKsByCategory = $mandatoryQuery->get()->groupBy('tipe_mk');

        $mkNasionalMissing = [];
        if ($akademikMhs->mk_nasional === 'no') {
            $nasional = $mandatoryMKsByCategory->get('nasional') ?? collect();
            foreach ($nasional as $mk) {
                $status = DB::table('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->where('matakuliah_id', $mk->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if (!$status || $status->nilai_akhir_huruf === 'E') {
                    $mkNasionalMissing[] = $mk->name;
                }
            }
        }

        $mkFakultasMissing = [];
        if ($akademikMhs->mk_fakultas === 'no') {
            $fakultas = $mandatoryMKsByCategory->get('fakultas') ?? collect();
            foreach ($fakultas as $mk) {
                $status = DB::table('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->where('matakuliah_id', $mk->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if (!$status || $status->nilai_akhir_huruf === 'E') {
                    $mkFakultasMissing[] = $mk->name;
                }
            }
        }

        $mkProdiMissing = [];
        if ($akademikMhs->mk_prodi === 'no') {
            $prodi = $mandatoryMKsByCategory->get('prodi') ?? collect();
            foreach ($prodi as $mk) {
                $status = DB::table('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->where('matakuliah_id', $mk->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if (!$status || $status->nilai_akhir_huruf === 'E') {
                    $mkProdiMissing[] = $mk->name;
                }
            }
        }

        // Format data sesuai kebutuhan
        return [
            'id' => $mahasiswa->id,
            'nama' => $mahasiswa->user->name ?? null,
            'nim' => $mahasiswa->nim ?? null,
            'status_mahasiswa' => $mahasiswa->status_mahasiswa ?? null,
            'dosen_wali' => [
                'id' => $akademikMhs->dosenWali->id ?? null,
                'nama' => $akademikMhs->dosenWali->nama_lengkap ?? null,
            ],
            'akademik' => [
                'id' => $akademikMhs->id ?? null,
                'semester_aktif' => $akademikMhs->semester_aktif ?? null,
                'tahun_masuk' => $akademikMhs->tahun_masuk ?? null,
                'ipk' => $akademikMhs->ipk ?? 0,
                'sks_tempuh' => $akademikMhs->sks_tempuh ?? 0,
                'sks_lulus' => $akademikMhs->sks_lulus ?? 0,
                'mk_nasional' => $akademikMhs->mk_nasional ?? 'no',
                'mk_fakultas' => $akademikMhs->mk_fabilitas ?? 'no',
                'mk_prodi' => $akademikMhs->mk_prodi ?? 'no',
                'mk_nasional_detail' => $mkNasionalMissing,
                'mk_fakultas_detail' => $mkFakultasMissing,
                'mk_prodi_detail' => $mkProdiMissing,
                'nilai_d_melebihi_batas' => $akademikMhs->nilai_d_melebihi_batas ?? 'no',
                'nilai_e' => ($akademikMhs->nilai_e === 'no') ? 'yes' : 'no', // Inverted for consistency
                'total_sks_nilai_d' => $totalSksNilaiD,
                'max_sks_nilai_d' => 7.2, // Tetap 5% dari 144 SKS standar untuk konsistensi
            ],
            'status_ews' => $ews ? $ews->status : null,
            'status_kelulusan' => $ews ? $ews->status_kelulusan : null,
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

        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
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

    /**
     * Get distribusi status EWS for batch prodis - NO N+1 QUERIES
     * Returns data keyed by prodi_id
     */
    public function getDistribusiStatusEwsBatch(array $prodiIds, $tahunMasuk = null)
    {
        $result = [];

        // Single bulk query for all prodis
        $distribusiByProdi = EarlyWarningSystem::select(
                'mahasiswa.prodi_id',
                'early_warning_system.status',
                DB::raw('COUNT(*) as jumlah')
            )
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->groupBy('mahasiswa.prodi_id', 'early_warning_system.status')
            ->get()
            ->groupBy('prodi_id');

        foreach ($prodiIds as $prodiId) {
            $prodiDist = $distribusiByProdi->get($prodiId, collect())->keyBy('status');

            $result[$prodiId] = [
                'tepat_waktu' => $prodiDist->get('tepat_waktu')?->jumlah ?? 0,
                'normal' => $prodiDist->get('normal')?->jumlah ?? 0,
                'perhatian' => $prodiDist->get('perhatian')?->jumlah ?? 0,
                'kritis' => $prodiDist->get('kritis')?->jumlah ?? 0,
            ];
        }

        return $result;
    }

    private function getTableRingkasanStatusQuery()
    {
        $query = AkademikMahasiswa::select(
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_kurang_dari_2'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }

        return $query->groupBy('prodis.nama', 'prodis.kode_prodi', 'akademik_mahasiswa.tahun_masuk')
                ->orderBy('prodis.nama', 'asc')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');
    }

    public function getTableRingkasanStatus()
    {
        return $this->getTableRingkasanStatusQuery()->get();
    }

    public function getTableRingkasanStatusExport()
    {
        return $this->getTableRingkasanStatusQuery()->get();
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
    private function getMahasiswaAllQuery($search = null, $mode = 'simple', $filters = [])
    {
        if ($mode === 'simple') {
            $query = Mahasiswa::filterByProdi()
                    ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                    ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                    ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                    ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                    ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                    ->select(
                        'prodis.nama as nama_prodi',
                        'mahasiswa.id as mahasiswa_id',
                        'mahasiswa.nim',
                        'users.name as nama_lengkap',
                        DB::raw("CONCAT(COALESCE(CONCAT(dosen.gelar_depan, ' '), ''), dosen_users.name, COALESCE(CONCAT(' ', dosen.gelar_belakang), '')) as nama_dosen_wali")
                    );
        } else {
            $query = Mahasiswa::filterByProdi()
                    ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                    ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                    ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
                    ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                    ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                    ->select(
                        'prodis.nama as nama_prodi',
                        'mahasiswa.id as mahasiswa_id',
                        'mahasiswa.nim',
                        'users.name as nama_lengkap',
                        'mahasiswa.status_mahasiswa',
                        DB::raw("CONCAT(COALESCE(CONCAT(dosen.gelar_depan, ' '), ''), dosen_users.name, COALESCE(CONCAT(' ', dosen.gelar_belakang), '')) as nama_dosen_wali"),
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
                    );
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Apply additional filters
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
            // Inverted for user logic: yes means met/safe (no exceeding), no means not met (is exceeding)
            $dbValue = ($filters['nilai_d_melebihi_batas'] === 'yes') ? 'no' : 'yes';
            $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', $dbValue);
        }
        if (!empty($filters['nilai_e'])) {
            // Inverted for user logic: yes means met/safe (no E), no means not met (has E)
            $dbValue = ($filters['nilai_e'] === 'yes') ? 'no' : 'yes';
            $query->where('akademik_mahasiswa.nilai_e', $dbValue);
        }
        if (!empty($filters['semester_1_3']) && $filters['semester_1_3'] === 'yes') {
            $query->whereBetween('akademik_mahasiswa.semester_aktif', [1, 3]);
        }
        if (!empty($filters['ipk_rendah']) && $filters['ipk_rendah'] === 'yes') {
            $query->where('akademik_mahasiswa.ipk', '<', 2);
        }
        if (!empty($filters['sks_kurang']) && $filters['sks_kurang'] === 'yes') {
            $query->where('akademik_mahasiswa.sks_lulus', '<', 144);
        }
        if (!empty($filters['mk_ulang']) && $filters['mk_ulang'] === 'yes') {
            $query->whereExists(function($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('khs_krs_mahasiswa')
                    ->whereColumn('khs_krs_mahasiswa.mahasiswa_id', 'mahasiswa.id')
                    ->groupBy('khs_krs_mahasiswa.mahasiswa_id', 'khs_krs_mahasiswa.matakuliah_id')
                    ->havingRaw('COUNT(*) > 1');
            });
        }

        return $query->orderBy('mahasiswa.nim', 'asc');
    }

    public function getMahasiswaAll($search = null, $perPage = 10, $mode = 'simple', $filters = [])
    {
        $paginatedData = $this->getMahasiswaAllQuery($search, $mode, $filters)->paginate($perPage);

        // Apply transformation for detailed mode to include extra info and consistent mapping
        if ($mode === 'detailed') {
            // Get mandatory MKs once
            $mandatoryQuery = DB::table('mata_kuliahs')
                ->whereIn('tipe_mk', ['nasional', 'fakultas', 'prodi']);
                
            $user = Auth::user();
            if ($user && $user->hasRole('kaprodi')) {
                $mandatoryQuery->where('prodi_id', $user->prodi_id);
            } elseif ($user && $user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $mandatoryQuery->where('prodi_id', request('prodi_id'));
            }

            $mandatoryMKsByCategory = $mandatoryQuery->get()
                ->groupBy('tipe_mk');

            $paginatedData->getCollection()->transform(function ($mahasiswa) use ($mandatoryMKsByCategory) {
                // Get latest grades for detail
                $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
                    ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
                    ->whereIn('khs1.id', function($query) use ($mahasiswa) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('khs_krs_mahasiswa as khs2')
                            ->where('khs2.mahasiswa_id', $mahasiswa->mahasiswa_id)
                            ->groupBy('khs2.matakuliah_id');
                    })
                    ->where('khs1.mahasiswa_id', $mahasiswa->mahasiswa_id)
                    ->select('mata_kuliahs.id as matakuliah_id', 'mata_kuliahs.name as nama', 'mata_kuliahs.sks', 'khs1.nilai_akhir_huruf')
                    ->get();

                // 1. Nilai E
                $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
                $mahasiswa->jumlah_nilai_e = $matkulNilaiE->count();
                $mahasiswa->nilai_e_detail = $matkulNilaiE->pluck('nama')->toArray();
                $mahasiswa->nilai_e = ($mahasiswa->jumlah_nilai_e === 0) ? 'yes' : 'no';

                // 2. Nilai D
                $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
                $mahasiswa->jumlah_nilai_d = $matkulNilaiD->count();
                $mahasiswa->nilai_d_detail = $matkulNilaiD->pluck('nama')->toArray();

                // 3. Mandatory MKs
                $categories = ['nasional', 'fakultas', 'prodi'];
                foreach ($categories as $cat) {
                    $field = "mk_$cat";
                    $detailField = "mk_{$cat}_detail";
                    if ($mahasiswa->$field === 'no') {
                        $prodiMandatory = $mandatoryMKsByCategory->get($cat) ?? collect();
                        $missing = [];
                        foreach ($prodiMandatory as $mk) {
                            $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                            if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                                $missing[] = $mk->name;
                            }
                        }
                        $mahasiswa->$detailField = $missing;
                    } else {
                        $mahasiswa->$detailField = [];
                    }
                }
                return $mahasiswa;
            });
        }

        return $paginatedData;
    }

    public function getMahasiswaAllExport($search = null, $mode = 'simple', $filters = [])
    {
        return $this->getMahasiswaAllQuery($search, $mode, $filters)->get();
    }
}
