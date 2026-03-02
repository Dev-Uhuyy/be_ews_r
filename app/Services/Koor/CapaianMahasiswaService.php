<?php

namespace App\Services\Koor;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CapaianMahasiswaService
{
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

    public function getMahasiswaMKGagal($search = null, $perPage = 10)
    {
        // Get top 10 mata kuliah gagal terlebih dahulu
        $topMKGagal = $this->getTopTenMKGagalAll();

        // Extract matakuliah_id dari top 10
        $topMKIds = DB::table('khs_krs_mahasiswa')
            ->join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('akademik_mahasiswa', 'khs_krs_mahasiswa.mahasiswa_id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('khs_krs_mahasiswa.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select('khs_krs_mahasiswa.matakuliah_id', DB::raw('COUNT(*) as jumlah_gagal'))
            ->groupBy('khs_krs_mahasiswa.matakuliah_id')
            ->orderByDesc('jumlah_gagal')
            ->limit(10)
            ->pluck('matakuliah_id');

        if ($topMKIds->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        // Get mahasiswa yang nilai TERAKHIR per mata kuliah adalah E di top 10 MK
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
            ->whereIn('khs1.matakuliah_id', $topMKIds) // HANYA mata kuliah di top 10
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        // Search by nama
        if ($search) {
            $query->where('users.name', 'LIKE', '%' . $search . '%');
        }

        $mahasiswaGagal = $query->select(
                'users.name as nama',
                'mahasiswa.nim',
                'mata_kuliahs.name as nama_matkul',
                'mata_kuliahs.kode as kode_matkul',
                'kelompok_mata_kuliah.kode as kode_kelompok',
                'khs1.absen as presensi'
            )
            ->orderBy('mata_kuliahs.kode')
            ->orderBy('users.name')
            ->paginate($perPage);

        return $mahasiswaGagal;
    }
}
