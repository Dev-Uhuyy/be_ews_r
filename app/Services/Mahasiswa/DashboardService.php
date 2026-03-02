<?php

namespace App\Services\Mahasiswa;

use App\Models\Mahasiswa;

class DashboardService
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
}
