<?php

namespace App\Services;
use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\KhsKrsMahasiswa;

class MahasiswaService
{
    public function getDashboardMahasiswa($userId)
    {
        // Get mahasiswa by user_id with relationships
        $mahasiswa = Mahasiswa::where('user_id', $userId)
            ->with([
                'user',
                'prodi',
                'akademikmahasiswa.dosen_wali.user',
                'akademikmahasiswa.early_warning_systems' => function($query) {
                    $query->latest()->first();
                },
                'ipsmahasiswa'
            ])->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademik = $mahasiswa->akademikmahasiswa;
        $ipsData = $mahasiswa->ipsmahasiswa;

        // Get latest EWS status
        $ewsStatus = null;
        if ($akademik) {
            $latestEws = $akademik->early_warning_systems()->latest()->first();
            $ewsStatus = $latestEws ? $latestEws->status : null;
        }

        // Calculate IPK change (current semester vs previous semester)
        $ipkChange = null;
        $ipkChangeStatus = null;
        $currentSemester = $akademik ? $akademik->semester_aktif : null;

        if ($ipsData && $currentSemester && $currentSemester > 1) {
            $currentIps = $ipsData->{'ips_' . $currentSemester};
            $previousIps = $ipsData->{'ips_' . ($currentSemester - 1)};

            if ($currentIps !== null && $previousIps !== null) {
                $ipkChange = round($currentIps - $previousIps, 2);

                if ($ipkChange > 0) {
                    $ipkChangeStatus = 'naik';
                } elseif ($ipkChange < 0) {
                    $ipkChangeStatus = 'turun';
                } else {
                    $ipkChangeStatus = 'tetap';
                }
            }
        }

        // Prepare IPS per semester and calculate IPK per semester
        $ipsSemesterList = [];
        $ipkSemesterList = [];

        if ($ipsData) {
            $totalSks = 0;
            $totalNilaiSks = 0;

            for ($i = 1; $i <= 14; $i++) {
                $ipsValue = $ipsData->{'ips_' . $i};
                if ($ipsValue !== null) {
                    $ipsSemesterList[] = [
                        'semester' => $i,
                        'ips' => $ipsValue,
                    ];

                    // Calculate cumulative IPK
                    // Assuming equal SKS per semester for simplicity
                    // For accurate calculation, we'd need actual SKS per semester
                    $totalNilaiSks += $ipsValue;
                    $totalSks += 1;
                    $ipkKumulatif = $totalSks > 0 ? round($totalNilaiSks / $totalSks, 2) : 0;

                    $ipkSemesterList[] = [
                        'semester' => $i,
                        'ipk' => $ipkKumulatif,
                    ];
                }
            }
        }

        // Prepare dashboard data
        $dashboardData = [
            'mahasiswa' => [
                'id' => $mahasiswa->id,
                'nim' => $mahasiswa->nim,
                'nama' => $mahasiswa->user ? $mahasiswa->user->name : null,
                'email' => $mahasiswa->user ? $mahasiswa->user->email : null,
                'prodi' => $mahasiswa->prodi ? $mahasiswa->prodi->nama : null,
                'status_mahasiswa' => $mahasiswa->status_mahasiswa,
            ],
            'akademik' => [
                'ipk' => $akademik ? $akademik->ipk : null,
                'ipk_change' => $ipkChange,
                'ipk_change_status' => $ipkChangeStatus,
                'sks_lulus' => $akademik ? $akademik->sks_lulus : 0,
                'sks_tempuh' => $akademik ? $akademik->sks_tempuh : 0,
                'sks_now' => $akademik ? $akademik->sks_now : 0,
                'semester_aktif' => $akademik ? $akademik->semester_aktif : null,
                'tahun_masuk' => $akademik ? $akademik->tahun_masuk : null,
            ],
            'ews' => [
                'status' => $ewsStatus,
            ],
            'dosen_wali' => null,
            'ips_per_semester' => $ipsSemesterList,
            'ipk_per_semester' => $ipkSemesterList,
        ];

        // Add dosen wali information
        if ($akademik && $akademik->dosen_wali) {
            $dosenWali = $akademik->dosen_wali;
            $dashboardData['dosen_wali'] = $dosenWali->nama_lengkap;
        }

        return $dashboardData;
    }

    public function getCardStatusAkademik($userId)
    {
        // Get mahasiswa by user_id with relationships
        $mahasiswa = Mahasiswa::where('user_id', $userId)
            ->with([
                'user',
                'prodi',
                'akademikmahasiswa.dosen_wali.user',
                'akademikmahasiswa.early_warning_systems',
                'khskrsmahasiswa.mata_kuliah',
                'khskrsmahasiswa.kelompok_mata_kuliah'
            ])->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademik = $mahasiswa->akademikmahasiswa;

        // Get latest EWS status
        $ewsStatus = null;
        if ($akademik) {
            $latestEws = $akademik->early_warning_systems()->latest()->first();
            $ewsStatus = $latestEws ? $latestEws->status : null;
        }

        // Calculate SKS per semester
        $sksPerSemester = [];
        if ($mahasiswa->khskrsmahasiswa) {
            $semesterData = [];

            foreach ($mahasiswa->khskrsmahasiswa as $khs) {
                // Only count status 'B' (Baru), not 'U' (Ulang)
                if ($khs->mata_kuliah && $khs->status === 'B') {
                    // Use semester_ambil if available, fallback to mata_kuliah->semester
                    $semester = isset($khs->semester_ambil) ? $khs->semester_ambil : $khs->mata_kuliah->semester;
                    $sks = $khs->mata_kuliah->sks;

                    if (!isset($semesterData[$semester])) {
                        $semesterData[$semester] = 0;
                    }
                    $semesterData[$semester] += $sks;
                }
            }

            // Convert to array format
            foreach ($semesterData as $semester => $totalSks) {
                $sksPerSemester[] = [
                    'semester' => $semester,
                    'sks' => $totalSks
                ];
            }

            // Sort by semester
            usort($sksPerSemester, function($a, $b) {
                return $a['semester'] - $b['semester'];
            });
        }

        // Get mata kuliah with nilai D or E
        $mataKuliahDE = [];
        if ($mahasiswa->khskrsmahasiswa) {
            foreach ($mahasiswa->khskrsmahasiswa as $khs) {
                if ($khs->mata_kuliah && in_array($khs->nilai_akhir_huruf, ['D', 'E'])) {
                    // Use semester_ambil if available, fallback to mata_kuliah->semester
                    $semester = isset($khs->semester_ambil) ? $khs->semester_ambil : $khs->mata_kuliah->semester;

                    $mataKuliahDE[] = [
                        'kode' => $khs->mata_kuliah->kode,
                        'nama' => $khs->mata_kuliah->name,
                        'sks' => $khs->mata_kuliah->sks,
                        'semester' => $semester,
                        'nilai' => $khs->nilai_akhir_huruf,
                        'nilai_angka' => $khs->nilai_akhir_angka,
                    ];
                }
            }
        }

        // Prepare status akademik data
        $statusAkademikData = [
            'mahasiswa' => [
                'nim' => $mahasiswa->nim,
                'nama' => $mahasiswa->user ? $mahasiswa->user->name : null,
                'dosen_wali' => $akademik && $akademik->dosen_wali ? $akademik->dosen_wali->nama_lengkap : null,
                'status_ews' => $ewsStatus,
            ],
            'nilai_d_melebihi_batas' => $akademik ? $akademik->nilai_d_melebihi_batas : 0,
            'sks_per_semester' => $sksPerSemester,
            'mata_kuliah_de' => $mataKuliahDE,
        ];

        return $statusAkademikData;
    }

    public function getKhsKrsMahasiswa($userId, $perPage = 15, $page = 1)
    {
        // Get mahasiswa by user_id
        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

        if (!$mahasiswa) {
            return null;
        }

        // Get all KHS KRS with relationships
        $allKhs = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->with(['mata_kuliah', 'kelompok_mata_kuliah'])
            ->orderBy('id', 'asc')
            ->get();

        // Group by mata kuliah and keep only:
        // - If only taken once (status 'B'), keep that one
        // - If taken multiple times (has 'U'), keep the latest 'U' (last attempt)
        $khsGrouped = [];
        $khsStatus = []; // Track if MK has been taken multiple times

        foreach ($allKhs as $khs) {
            if ($khs->mata_kuliah) {
                $mkId = $khs->matakuliah_id;

                // Track how many times this MK was taken
                if (!isset($khsStatus[$mkId])) {
                    $khsStatus[$mkId] = ['count' => 0, 'has_ulang' => false];
                }
                $khsStatus[$mkId]['count']++;
                if ($khs->status === 'U') {
                    $khsStatus[$mkId]['has_ulang'] = true;
                }

                // Always replace with the latest one (since we ordered by id asc)
                // This will be either the only 'B' or the last 'U'
                $khsGrouped[$mkId] = $khs;
            }
        }

        // Convert to array (simplified for list)
        $khsKrsList = [];
        foreach ($khsGrouped as $mkId => $khs) {
            $khsKrsList[] = [
                'id' => $khs->id,
                'kode_matkul' => $khs->mata_kuliah->kode,
                'nama_matkul' => $khs->mata_kuliah->name,
                'sks' => $khs->mata_kuliah->sks,
                'nilai_huruf' => $khs->nilai_akhir_huruf,
            ];
        }

        // Sort by id
        usort($khsKrsList, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        // Pagination
        $total = count($khsKrsList);
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($khsKrsList, $offset, $perPage);

        return [
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    public function getDetailKhsKrs($userId, $khsKrsId)
    {
        // Get mahasiswa by user_id
        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

        if (!$mahasiswa) {
            return ['error' => 'Mahasiswa tidak ditemukan'];
        }

        // Get KHS KRS by ID with ownership validation
        $khsKrs = KhsKrsMahasiswa::where('id', $khsKrsId)
            ->where('mahasiswa_id', $mahasiswa->id) // Validasi ownership
            ->with(['mata_kuliah', 'kelompok_mata_kuliah.dosen_pengampu.user'])
            ->first();

        if (!$khsKrs) {
            return ['error' => 'Data KHS tidak ditemukan atau bukan milik Anda'];
        }

        // Build detailed response
        $semester = isset($khsKrs->semester_ambil) ? $khsKrs->semester_ambil : ($khsKrs->mata_kuliah ? $khsKrs->mata_kuliah->semester : null);

        $detailData = [
            'id' => $khsKrs->id,
            'mata_kuliah' => [
                'id' => $khsKrs->mata_kuliah->id,
                'kode' => $khsKrs->mata_kuliah->kode,
                'nama' => $khsKrs->mata_kuliah->name,
                'sks' => $khsKrs->mata_kuliah->sks,
                'semester' => $khsKrs->mata_kuliah->semester,
                'tipe_mk' => $khsKrs->mata_kuliah->tipe_mk,
            ],
            'kelompok' => $khsKrs->kelompok_mata_kuliah ? [
                'id' => $khsKrs->kelompok_mata_kuliah->id,
                'kode' => $khsKrs->kelompok_mata_kuliah->kode,
                'dosen_pengampu' => $khsKrs->kelompok_mata_kuliah->dosen_pengampu && $khsKrs->kelompok_mata_kuliah->dosen_pengampu->user
                    ? $khsKrs->kelompok_mata_kuliah->dosen_pengampu->user->name
                    : null,
            ] : null,
            'semester_ambil' => $semester,
            'status' => $khsKrs->status,
            'status_display' => $khsKrs->status === 'B' ? 'Baru' : 'Ulang',
            'absen' => $khsKrs->absen,
            'nilai' => [
                'uts' => $khsKrs->nilai_uts,
                'uas' => $khsKrs->nilai_uas,
                'akhir_angka' => $khsKrs->nilai_akhir_angka,
                'akhir_huruf' => $khsKrs->nilai_akhir_huruf,
            ],
        ];

        return $detailData;
    }

    public function getPeringatan($userId)
    {
        // Get mahasiswa by user_id with relationships
        $mahasiswa = Mahasiswa::where('user_id', $userId)
            ->with([
                'akademikmahasiswa.early_warning_systems'
            ])->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademik = $mahasiswa->akademikmahasiswa;

        // Get latest EWS
        $ews = null;
        if ($akademik) {
            $ews = $akademik->early_warning_systems()->latest()->first();
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

        return [
            'status_ews' => $ews ? $ews->status : null,
            'riwayat_sps' => $riwayatSps,
        ];
    }
}
