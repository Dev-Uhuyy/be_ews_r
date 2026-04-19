<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class CapaianMahasiswaService
{
    /**
     * Membantu filter data query (Query Builder atau Eloquent) berdasarkan role prodi
     */
    private function applyProdiScope($query)
    {
        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }
        return $query;
    }
    // angkatan, tren ips(naik/turun), mk_gagal ada berapa, mk_ulang ada berapa
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

        $query = $this->applyProdiScope($query);

        $result = $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();

        // Hitung tren IPS untuk setiap angkatan
        $resultWithTren = [];

        foreach ($result as $index => $item) {
            // Ambil semester aktif mayoritas untuk angkatan ini
            $semesterAktifQuery = DB::table('akademik_mahasiswa')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->select('akademik_mahasiswa.semester_aktif');

            $semesterAktif = $this->applyProdiScope($semesterAktifQuery)
                ->groupBy('akademik_mahasiswa.semester_aktif')
                ->orderByRaw('COUNT(*) DESC')
                ->value('semester_aktif');

            $tren = 'stabil';

            if ($semesterAktif && $semesterAktif >= 3) {
                // Hitung rata-rata IPS semester (semester_aktif - 2)
                $semesterPrev2 = $semesterAktif - 2;
                $semesterPrev1 = $semesterAktif - 1;

                $avgIpsPrev2Query = DB::table('ips_mahasiswa')
                    ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                    ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                    ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev2)
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

                $avgIpsPrev2 = $this->applyProdiScope($avgIpsPrev2Query)->avg('ips_mahasiswa.ips_' . $semesterPrev2);

                $avgIpsPrev1Query = DB::table('ips_mahasiswa')
                    ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                    ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                    ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                    ->whereNotNull('ips_mahasiswa.ips_' . $semesterPrev1)
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

                $avgIpsPrev1 = $this->applyProdiScope($avgIpsPrev1Query)->avg('ips_mahasiswa.ips_' . $semesterPrev1);

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
            $mkGagalQuery = DB::table('khs_krs_mahasiswa')
                ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

            $mkGagal = $this->applyProdiScope($mkGagalQuery)->count();

            // Hitung jumlah mata kuliah ulang (retake - matakuliah_id yang muncul > 1x)
            $mkUlangQuery = DB::table('khs_krs_mahasiswa')
                ->select('khs_krs_mahasiswa.mahasiswa_id', 'khs_krs_mahasiswa.matakuliah_id', DB::raw('COUNT(*) as jumlah'))
                ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $item->tahun_masuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

            $mkUlang = $this->applyProdiScope($mkUlangQuery)
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

    /**
     * Get tren IPS all for batch prodis - NO N+1 QUERIES
     * Returns data keyed by prodi_id
     */
    public function getTrenIPSAllBatch(array $prodiIds, $tahunMasuk = null)
    {
        $result = [];

        // Bulk query for semua prodis - angkatan data
        $query = AkademikMahasiswa::select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('AVG(akademik_mahasiswa.ipk) as rata_ipk'),
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as jumlah_mahasiswa')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereNotNull('akademik_mahasiswa.ipk')
            ->where('akademik_mahasiswa.ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds);

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $dataByProdiAngkatan = $query->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->groupBy('prodi_id');

        // Bulk query untuk semester aktif mayoritas per angkatan per prodi
        $semesterByProdiAngkatan = DB::table('akademik_mahasiswa')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk', 'akademik_mahasiswa.semester_aktif')
            ->select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                'akademik_mahasiswa.semester_aktif',
                DB::raw('COUNT(*) as jumlah')
            )
            ->get()
            ->groupBy('prodi_id')
            ->map(function ($items) {
                return $items->sortByDesc('jumlah')->groupBy('tahun_masuk')
                    ->map(function ($angktn) {
                        return $angktn->first()->semester_aktif;
                    });
            });

        // Bulk query untuk IPS rata-rata per semester per prodi per angkatan
        $ipsByProdiAngkatanSemester = DB::table('ips_mahasiswa')
            ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('AVG(ips_mahasiswa.ips_1) as avg_ips_1'),
                DB::raw('AVG(ips_mahasiswa.ips_2) as avg_ips_2'),
                DB::raw('AVG(ips_mahasiswa.ips_3) as avg_ips_3'),
                DB::raw('AVG(ips_mahasiswa.ips_4) as avg_ips_4'),
                DB::raw('AVG(ips_mahasiswa.ips_5) as avg_ips_5'),
                DB::raw('AVG(ips_mahasiswa.ips_6) as avg_ips_6'),
                DB::raw('AVG(ips_mahasiswa.ips_7) as avg_ips_7'),
                DB::raw('AVG(ips_mahasiswa.ips_8) as avg_ips_8')
            )
            ->get()
            ->groupBy('prodi_id');

        // Bulk query for mk_gagal count per prodi per angkatan
        $mkGagalByProdiAngkatan = DB::table('khs_krs_mahasiswa')
            ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('COUNT(*) as jumlah_gagal')
            )
            ->get()
            ->groupBy('prodi_id');

        // Bulk query for mk_ulang count per prodi per angkatan
        $mkUlangByProdiAngkatan = DB::table('khs_krs_mahasiswa')
            ->select('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk', 'khs_krs_mahasiswa.mahasiswa_id', 'khs_krs_mahasiswa.matakuliah_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->groupBy('prodi_id')
            ->map(function ($items) {
                return $items->groupBy('tahun_masuk')->map(function ($angktn) {
                    return $angktn->count();
                });
            });

        foreach ($prodiIds as $prodiId) {
            $dataAngkatan = $dataByProdiAngkatan->get($prodiId, collect());
            $semesterData = $semesterByProdiAngkatan->get($prodiId, collect());
            $ipsData = $ipsByProdiAngkatanSemester->get($prodiId, collect());
            $mkGagalData = $mkGagalByProdiAngkatan->get($prodiId, collect())->keyBy('tahun_masuk');
            $mkUlangData = $mkUlangByProdiAngkatan->get($prodiId, collect());

            $trenData = [];

            foreach ($dataAngkatan as $item) {
                $tahun = $item->tahun_masuk;
                $semesterAktif = $semesterData->get($tahun);
                $ipsRow = $ipsData->first();

                $tren = 'stabil';

                if ($semesterAktif && $semesterAktif >= 3) {
                    $semesterPrev2 = $semesterAktif - 2;
                    $semesterPrev1 = $semesterAktif - 1;

                    $avgIpsPrev2 = $ipsRow ? ($ipsRow->{'avg_ips_' . $semesterPrev2} ?? null) : null;
                    $avgIpsPrev1 = $ipsRow ? ($ipsRow->{'avg_ips_' . $semesterPrev1} ?? null) : null;

                    if ($avgIpsPrev2 !== null && $avgIpsPrev1 !== null) {
                        if ($avgIpsPrev1 > $avgIpsPrev2) {
                            $tren = 'naik';
                        } elseif ($avgIpsPrev1 < $avgIpsPrev2) {
                            $tren = 'turun';
                        }
                    }
                }

                $mkGagal = $mkGagalData->get($tahun)?->jumlah_gagal ?? 0;
                $mkUlang = $mkUlangData->get($tahun, 0);

                $trenData[] = [
                    'tahun_masuk' => $tahun,
                    'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                    'tren_ips' => $tren,
                    'mk_gagal' => $mkGagal,
                    'mk_ulang' => $mkUlang,
                ];
            }

            $result[$prodiId] = $trenData;
        }

        return $result;
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

        $query = $this->applyProdiScope($query);

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
            $mahasiswaIpsQuery = DB::table('ips_mahasiswa')
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
                );

            $mahasiswaIps = $this->applyProdiScope($mahasiswaIpsQuery)->get();

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

    /**
     * Get card capaian mahasiswa for batch prodis - NO N+1 QUERIES
     * Returns data keyed by prodi_id
     */
    public function getCardCapaianMahasiswaBatch(array $prodiIds, $tahunMasuk = null)
    {
        $result = [];

        // Bulk query: angkatan per prodi
        $angkatanData = AkademikMahasiswa::select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk'
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->groupBy('prodi_id');

        // Bulk query: semester aktif mayoritas per prodi per angkatan
        $semesterData = DB::table('akademik_mahasiswa')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk', 'akademik_mahasiswa.semester_aktif')
            ->select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                'akademik_mahasiswa.semester_aktif',
                DB::raw('COUNT(*) as jumlah')
            )
            ->get()
            ->groupBy('prodi_id')
            ->map(function ($items) {
                return $items->sortByDesc('jumlah')->groupBy('tahun_masuk')
                    ->map(function ($angktn) {
                        return $angktn->first()->semester_aktif;
                    });
            });

        // Bulk query: IPS per mahasiswa per prodi per angkatan (for comparing semester)
        $ipsMahasiswaData = DB::table('ips_mahasiswa')
            ->join('akademik_mahasiswa', 'ips_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk', 'ips_mahasiswa.mahasiswa_id')
            ->select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                'ips_mahasiswa.mahasiswa_id',
                DB::raw('AVG(ips_mahasiswa.ips_1) as avg_ips_1'),
                DB::raw('AVG(ips_mahasiswa.ips_2) as avg_ips_2'),
                DB::raw('AVG(ips_mahasiswa.ips_3) as avg_ips_3'),
                DB::raw('AVG(ips_mahasiswa.ips_4) as avg_ips_4'),
                DB::raw('AVG(ips_mahasiswa.ips_5) as avg_ips_5'),
                DB::raw('AVG(ips_mahasiswa.ips_6) as avg_ips_6'),
                DB::raw('AVG(ips_mahasiswa.ips_7) as avg_ips_7'),
                DB::raw('AVG(ips_mahasiswa.ips_8) as avg_ips_8')
            )
            ->get()
            ->groupBy('prodi_id');

        foreach ($prodiIds as $prodiId) {
            $angkatans = $angkatanData->get($prodiId, collect());
            $semestersByAngkatan = $semesterData->get($prodiId, collect());
            $ipsByAngkatan = $ipsMahasiswaData->get($prodiId, collect())->groupBy('tahun_masuk');

            $totalMahasiswa = 0;
            $totalTurun = 0;
            $totalNaik = 0;
            $trenPerAngkatan = [];

            foreach ($angkatans as $angkat) {
                $tahun = $angkat->tahun_masuk;
                $semesterAktif = $semestersByAngkatan->get($tahun);

                if (!$semesterAktif || $semesterAktif < 3) {
                    continue;
                }

                $semesterPrev2 = $semesterAktif - 2;
                $semesterPrev1 = $semesterAktif - 1;

                $mahasiswaIpsList = $ipsByAngkatan->get($tahun, collect());

                $jumlahMahasiswa = $mahasiswaIpsList->count();
                $naik = 0;
                $turun = 0;
                $stabil = 0;
                $totalIpsPrev1 = 0;

                foreach ($mahasiswaIpsList as $mhs) {
                    $ipsPrev2 = $mhs->{'avg_ips_' . $semesterPrev2} ?? null;
                    $ipsPrev1 = $mhs->{'avg_ips_' . $semesterPrev1} ?? null;

                    if ($ipsPrev2 !== null && $ipsPrev1 !== null) {
                        $totalIpsPrev1 += $ipsPrev1;
                        if ($ipsPrev1 > $ipsPrev2) {
                            $naik++;
                        } elseif ($ipsPrev1 < $ipsPrev2) {
                            $turun++;
                        } else {
                            $stabil++;
                        }
                    }
                }

                $rataIps = $jumlahMahasiswa > 0 ? round($totalIpsPrev1 / $jumlahMahasiswa, 2) : 0;

                $totalMahasiswa += $jumlahMahasiswa;
                $totalNaik += $naik;
                $totalTurun += $turun;

                $trenPerAngkatan[] = [
                    'tahun_masuk' => $tahun,
                    'semester_aktif' => $semesterAktif,
                    'rata_ips' => $rataIps,
                    'jumlah_mahasiswa' => $jumlahMahasiswa,
                    'mahasiswa_naik_ip' => $naik,
                    'mahasiswa_turun_ip' => $turun,
                    'mahasiswa_stabil_ip' => $stabil,
                ];
            }

            $result[$prodiId] = [
                'total_mahasiswa' => $totalMahasiswa,
                'total_turun_ip' => $totalTurun,
                'total_naik_ip' => $totalNaik,
                'tren_per_angkatan' => $trenPerAngkatan,
            ];
        }

        return $result;
    }

    public function getTopTenMKGagalAll()
    {
        // Get top 10 mata kuliah dengan jumlah mahasiswa gagal (nilai E) terbanyak
        $topMKGagalQuery = DB::table('khs_krs_mahasiswa')
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
            );

        $topMKGagal = $this->applyProdiScope($topMKGagalQuery)
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

    private function getMahasiswaMKGagalQuery($search = null)
    {
        // Get top 10 mata kuliah gagal terlebih dahulu
        $topMKIdsQuery = DB::table('khs_krs_mahasiswa')
            ->join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select('khs_krs_mahasiswa.matakuliah_id', DB::raw('COUNT(*) as jumlah_gagal'));

        $topMKIds = $this->applyProdiScope($topMKIdsQuery)
            ->groupBy('khs_krs_mahasiswa.matakuliah_id')
            ->orderByDesc('jumlah_gagal')
            ->limit(10)
            ->pluck('matakuliah_id');

        if ($topMKIds->isEmpty()) {
            return null;
        }

        $query = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('kelompok_mata_kuliah', 'khs1.kelompok_id', '=', 'kelompok_mata_kuliah.id')
            ->join('akademik_mahasiswa', 'khs1.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('dosen', 'kelompok_mata_kuliah.dosen_pengampu_id', '=', 'dosen.id')
            ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
            ->whereIn('khs1.id', function($subquery) {
                $subquery->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->whereColumn('khs2.mahasiswa_id', 'khs1.mahasiswa_id')
                    ->whereColumn('khs2.matakuliah_id', 'khs1.matakuliah_id')
                    ->groupBy('khs2.mahasiswa_id', 'khs2.matakuliah_id');
            })
            ->where('khs1.nilai_akhir_huruf', 'E')
            ->whereIn('khs1.matakuliah_id', $topMKIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        $query = $this->applyProdiScope($query);

        return $query->select(
                'users.name as nama',
                'mahasiswa.nim',
                'mata_kuliahs.name as nama_matkul',
                'mata_kuliahs.kode as kode_matkul',
                'kelompok_mata_kuliah.kode as kode_kelompok',
                'khs1.absen as presensi',
                DB::raw("COALESCE(
                    CONCAT(
                        CASE WHEN dosen.gelar_depan IS NOT NULL THEN CONCAT(TRIM(dosen.gelar_depan), ' ') ELSE '' END,
                        dosen_users.name,
                        CASE WHEN dosen.gelar_belakang IS NOT NULL THEN CONCAT(' ', TRIM(dosen.gelar_belakang)) ELSE '' END
                    ),
                    '-'
                ) as dosen_pengampu")
            )
            ->orderBy('mata_kuliahs.kode')
            ->orderBy('users.name');
    }

    public function getMahasiswaMKGagal($search = null, $perPage = 10)
    {
        $query = $this->getMahasiswaMKGagalQuery($search);

        if (!$query) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $query->paginate($perPage);
    }

    public function getMahasiswaMKGagalExport($search = null)
    {
        $query = $this->getMahasiswaMKGagalQuery($search);

        if (!$query) {
            return collect([]);
        }

        return $query->get();
    }
}
