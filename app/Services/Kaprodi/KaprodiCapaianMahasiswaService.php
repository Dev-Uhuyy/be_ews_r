<?php

namespace App\Services\Kaprodi;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KaprodiCapaianMahasiswaService
{
    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Get Top 10 Mata Kuliah Gagal (E) untuk Prodi Kaprodi
     */
    public function getTop10MatakuliahGagal($filters = [])
    {
        $prodiId = $this->getProdiId();

        $query = DB::table('khs_krs_mahasiswa as khs')
            ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
            ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('akademik_mahasiswa as am', 'khs.mahasiswa_id', '=', 'am.mahasiswa_id')
            ->where('khs.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->select(
                'mk.id as matakuliah_id',
                'mk.kode as kode_matakuliah',
                'mk.name as nama_matakuliah',
                'mk.sks',
                DB::raw('COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal'),
                DB::raw('MAX(khs.id) as latest_khs_id')
            )
            ->groupBy('mk.id', 'mk.kode', 'mk.name', 'mk.sks');

        if (!empty($filters['tahun_masuk'])) {
            $query->where('am.tahun_masuk', $filters['tahun_masuk']);
        }

        $results = $query->orderBy('jumlah_mahasiswa_gagal', 'desc')->get();

        $formattedData = $results->map(function ($mk) {
            return [
                'matakuliah_id' => $mk->matakuliah_id,
                'kode_matakuliah' => $mk->kode_matakuliah,
                'nama_matakuliah' => $mk->nama_matakuliah,
                'sks' => $mk->sks,
                'jumlah_mahasiswa_gagal' => $mk->jumlah_mahasiswa_gagal,
            ];
        })->values()->toArray();

        return [
            'total_matakuliah' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    /**
     * Get Rata-rata IPS per Tahun Angkatan untuk Prodi Kaprodi
     */
    public function getRataRataIpsPerTahunProdi()
    {
        $prodiId = $this->getProdiId();

        $sql = "
            SELECT
                akademik.tahun_masuk as tahun_masuk,
                COUNT(DISTINCT im.mahasiswa_id) as jumlah_mahasiswa,
                ROUND(AVG(im.ips_1), 2) as avg_ips_1,
                ROUND(AVG(im.ips_2), 2) as avg_ips_2,
                ROUND(AVG(im.ips_3), 2) as avg_ips_3,
                ROUND(AVG(im.ips_4), 2) as avg_ips_4,
                ROUND(AVG(im.ips_5), 2) as avg_ips_5,
                ROUND(AVG(im.ips_6), 2) as avg_ips_6,
                ROUND(AVG(im.ips_7), 2) as avg_ips_7,
                ROUND(AVG(im.ips_8), 2) as avg_ips_8,
                ROUND(AVG(im.ips_9), 2) as avg_ips_9,
                ROUND(AVG(im.ips_10), 2) as avg_ips_10,
                ROUND(AVG(im.ips_11), 2) as avg_ips_11,
                ROUND(AVG(im.ips_12), 2) as avg_ips_12,
                ROUND(AVG(im.ips_13), 2) as avg_ips_13,
                ROUND(AVG(im.ips_14), 2) as avg_ips_14
            FROM ips_mahasiswa im
            JOIN akademik_mahasiswa akademik ON im.mahasiswa_id = akademik.mahasiswa_id
            JOIN mahasiswa m ON im.mahasiswa_id = m.id
            WHERE m.prodi_id = ?
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
            GROUP BY akademik.tahun_masuk
            ORDER BY akademik.tahun_masuk ASC
        ";

        $results = DB::select($sql, [$prodiId]);

        $formattedData = [];
        foreach ($results as $row) {
            $ipsPerSemester = [];

            for ($i = 1; $i <= 14; $i++) {
                $colName = "avg_ips_$i";
                if ($row->$colName !== null && $row->$colName > 0) {
                    $ipsPerSemester["ips_$i"] = (float) $row->$colName;
                }
            }

            $formattedData[] = [
                'tahun_masuk' => (int) $row->tahun_masuk,
                'jumlah_mahasiswa' => (int) $row->jumlah_mahasiswa,
                'ips_per_semester' => $ipsPerSemester,
            ];
        }

        return [
            'total_angkatan' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    /**
     * Get Tabel Capaian Mahasiswa untuk Prodi Kaprodi
     */
    public function getTabelCapaianMahasiswa()
    {
        $prodiId = $this->getProdiId();

        // Get jumlah matakuliah gagal
        $sqlGagal = "
            SELECT COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal
            FROM khs_krs_mahasiswa khs
            JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
            JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
            WHERE khs.nilai_akhir_huruf = 'E'
            AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
            AND mahasiswa.prodi_id = ?
        ";

        $gagalResult = DB::select($sqlGagal, [$prodiId]);
        $jumlahMatakuliahGagal = $gagalResult[0]->jumlah_matakuliah_gagal ?? 0;

        // Get IPS data for trend
        $sqlIps = "
            SELECT
                im.mahasiswa_id,
                im.ips_1, im.ips_2, im.ips_3, im.ips_4, im.ips_5, im.ips_6, im.ips_7, im.ips_8,
                im.ips_9, im.ips_10, im.ips_11, im.ips_12, im.ips_13, im.ips_14
            FROM ips_mahasiswa im
            JOIN akademik_mahasiswa akademik ON im.mahasiswa_id = akademik.mahasiswa_id
            JOIN mahasiswa m ON im.mahasiswa_id = m.id
            WHERE m.prodi_id = ?
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        $ipsResults = DB::select($sqlIps, [$prodiId]);

        $totalIpsTerakhir = 0;
        $totalIpsSebelum = 0;
        $count = 0;

        foreach ($ipsResults as $row) {
            $ipsTerakhir = null;
            $ipsSebelum = null;

            for ($i = 14; $i >= 1; $i--) {
                $colName = "ips_$i";
                if ($row->$colName !== null && $row->$colName > 0) {
                    if ($ipsTerakhir === null) {
                        $ipsTerakhir = $row->$colName;
                        for ($j = $i - 1; $j >= 1; $j--) {
                            $prevColName = "ips_$j";
                            if ($row->$prevColName !== null && $row->$prevColName > 0) {
                                $ipsSebelum = $row->$prevColName;
                                break;
                            }
                        }
                        break;
                    }
                }
            }

            if ($ipsTerakhir !== null && $ipsSebelum !== null) {
                $totalIpsTerakhir += $ipsTerakhir;
                $totalIpsSebelum += $ipsSebelum;
                $count++;
            }
        }

        $trenIPS = 'Stabil';
        if ($count > 0) {
            $avgTerakhir = $totalIpsTerakhir / $count;
            $avgSebelum = $totalIpsSebelum / $count;
            if ($avgTerakhir > $avgSebelum) {
                $trenIPS = 'Naik';
            } elseif ($avgTerakhir < $avgSebelum) {
                $trenIPS = 'Turun';
            }
        }

        return [
            'tren_ips' => $trenIPS,
            'jumlah_matakuliah_gagal' => (int) $jumlahMatakuliahGagal,
        ];
    }

    /**
     * Get Detail Tabel Capaian Mahasiswa (per Tahun Angkatan) untuk Prodi Kaprodi
     */
    public function getDetailTabelCapaianMahasiswa()
    {
        $prodiId = $this->getProdiId();

        // Get jumlah matakuliah gagal per angkatan
        $sqlGagal = "
            SELECT
                akademik.tahun_masuk as tahun_angkatan,
                COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal
            FROM khs_krs_mahasiswa khs
            JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
            JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
            JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
            WHERE khs.nilai_akhir_huruf = 'E'
            AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
            AND mahasiswa.prodi_id = ?
            GROUP BY akademik.tahun_masuk
            ORDER BY akademik.tahun_masuk ASC
        ";

        $gagalResults = DB::select($sqlGagal, [$prodiId]);
        $gagalByTahun = collect($gagalResults)->keyBy('tahun_angkatan');

        // Get IPS data per angkatan
        $sqlIps = "
            SELECT
                im.mahasiswa_id,
                akademik.tahun_masuk as tahun_angkatan,
                im.ips_1, im.ips_2, im.ips_3, im.ips_4, im.ips_5, im.ips_6, im.ips_7, im.ips_8,
                im.ips_9, im.ips_10, im.ips_11, im.ips_12, im.ips_13, im.ips_14
            FROM ips_mahasiswa im
            JOIN akademik_mahasiswa akademik ON im.mahasiswa_id = akademik.mahasiswa_id
            JOIN mahasiswa m ON im.mahasiswa_id = m.id
            WHERE m.prodi_id = ?
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        $ipsResults = DB::select($sqlIps, [$prodiId]);

        $ipsDataByTahun = [];
        foreach ($ipsResults as $row) {
            $tahun = $row->tahun_angkatan;

            $ipsTerakhir = null;
            $ipsSebelum = null;

            for ($i = 14; $i >= 1; $i--) {
                $colName = "ips_$i";
                if ($row->$colName !== null && $row->$colName > 0) {
                    if ($ipsTerakhir === null) {
                        $ipsTerakhir = $row->$colName;
                        for ($j = $i - 1; $j >= 1; $j--) {
                            $prevColName = "ips_$j";
                            if ($row->$prevColName !== null && $row->$prevColName > 0) {
                                $ipsSebelum = $row->$prevColName;
                                break;
                            }
                        }
                        break;
                    }
                }
            }

            if ($ipsTerakhir !== null && $ipsSebelum !== null) {
                if (!isset($ipsDataByTahun[$tahun])) {
                    $ipsDataByTahun[$tahun] = [
                        'total_ips_terakhir' => 0,
                        'total_ips_sebelum' => 0,
                        'count' => 0,
                    ];
                }
                $ipsDataByTahun[$tahun]['total_ips_terakhir'] += $ipsTerakhir;
                $ipsDataByTahun[$tahun]['total_ips_sebelum'] += $ipsSebelum;
                $ipsDataByTahun[$tahun]['count']++;
            }
        }

        $formattedData = [];
        foreach ($ipsDataByTahun as $tahun => $data) {
            $trenIPS = 'Stabil';
            $count = $data['count'];
            if ($count > 0) {
                $avgTerakhir = $data['total_ips_terakhir'] / $count;
                $avgSebelum = $data['total_ips_sebelum'] / $count;
                if ($avgTerakhir > $avgSebelum) {
                    $trenIPS = 'Naik';
                } elseif ($avgTerakhir < $avgSebelum) {
                    $trenIPS = 'Turun';
                }
            }

            $gagalData = $gagalByTahun->get($tahun);

            $formattedData[] = [
                'tahun_angkatan' => (int) $tahun,
                'tren_ips' => $trenIPS,
                'jumlah_matakuliah_gagal' => $gagalData ? (int) $gagalData->jumlah_matakuliah_gagal : 0,
            ];
        }

        usort($formattedData, function($a, $b) {
            return $a['tahun_angkatan'] - $b['tahun_angkatan'];
        });

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    /**
     * Get List Mata Kuliah Gagal untuk Prodi Kaprodi
     */
    public function getListMataKuliahPerProdi($filters = [])
    {
        $prodiId = $this->getProdiId();
        $groupByAngkatan = !empty($filters['tahun_masuk']);

        if ($groupByAngkatan) {
            $sql = "
                SELECT
                    akademik.tahun_masuk as tahun_angkatan,
                    mk.id as matakuliah_id,
                    mk.kode as kode_matakuliah,
                    mk.name as nama_matakuliah,
                    mk.sks,
                    COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal
                FROM khs_krs_mahasiswa khs
                JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
                JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
                JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
                WHERE khs.nilai_akhir_huruf = 'E'
                AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
                AND mahasiswa.prodi_id = ?
                AND akademik.tahun_masuk = ?
                GROUP BY akademik.tahun_masuk, mk.id, mk.kode, mk.name, mk.sks
                ORDER BY jumlah_mahasiswa_gagal DESC
            ";

            $results = DB::select($sql, [$prodiId, $filters['tahun_masuk']]);

            $formattedData = [];
            foreach ($results as $mk) {
                $formattedData[] = [
                    'tahun_angkatan' => (int) $mk->tahun_angkatan,
                    'matakuliah' => [
                        'matakuliah_id' => (int) $mk->matakuliah_id,
                        'kode_matakuliah' => $mk->kode_matakuliah,
                        'nama_matakuliah' => $mk->nama_matakuliah,
                        'sks' => (int) $mk->sks,
                        'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                    ],
                ];
            }

            return [
                'total_data' => count($formattedData),
                'data' => $formattedData,
            ];
        } else {
            $sql = "
                SELECT
                    mk.id as matakuliah_id,
                    mk.kode as kode_matakuliah,
                    mk.name as nama_matakuliah,
                    mk.sks,
                    COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal
                FROM khs_krs_mahasiswa khs
                JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
                JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
                WHERE khs.nilai_akhir_huruf = 'E'
                AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
                AND mahasiswa.prodi_id = ?
                GROUP BY mk.id, mk.kode, mk.name, mk.sks
                ORDER BY jumlah_mahasiswa_gagal DESC
            ";

            $results = DB::select($sql, [$prodiId]);

            $formattedData = [];
            foreach ($results as $mk) {
                $formattedData[] = [
                    'matakuliah_id' => (int) $mk->matakuliah_id,
                    'kode_matakuliah' => $mk->kode_matakuliah,
                    'nama_matakuliah' => $mk->nama_matakuliah,
                    'sks' => (int) $mk->sks,
                    'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                ];
            }

            return [
                'total_matakuliah' => count($formattedData),
                'data' => $formattedData,
            ];
        }
    }

    /**
     * Get List Mahasiswa Gagal per Mata Kuliah untuk Prodi Kaprodi
     */
    public function getListMahasiswaGagalPerMataKuliah($filters = [])
    {
        $prodiId = $this->getProdiId();

        if (empty($filters['matakuliah_id'])) {
            throw new \InvalidArgumentException('matakuliah_id is required');
        }

        $sql = "
            SELECT
                m.id as mahasiswa_id,
                m.nim,
                u.name as nama_mahasiswa,
                akademik.tahun_masuk as tahun_angkatan,
                khs.nilai_akhir_huruf as nilai
            FROM khs_krs_mahasiswa khs
            JOIN mahasiswa m ON khs.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
            WHERE khs.matakuliah_id = ?
            AND khs.nilai_akhir_huruf = 'E'
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
            AND m.prodi_id = ?
        ";

        if (!empty($filters['tahun_masuk'])) {
            $sql .= " AND akademik.tahun_masuk = " . intval($filters['tahun_masuk']);
        }

        $sql .= " ORDER BY akademik.tahun_masuk ASC, u.name ASC";

        $results = DB::select($sql, [$filters['matakuliah_id'], $prodiId]);

        // Group by tahun angkatan
        $groupedByTahun = collect($results)->groupBy('tahun_angkatan');

        $formattedData = [];
        foreach ($groupedByTahun as $tahun => $mahasiswaList) {
            $formattedData[] = [
                'tahun_angkatan' => (int) $tahun,
                'jumlah_mahasiswa' => $mahasiswaList->count(),
                'mahasiswa' => $mahasiswaList->map(function ($m) {
                    return [
                        'mahasiswa_id' => (int) $m->mahasiswa_id,
                        'nim' => $m->nim,
                        'nama_mahasiswa' => $m->nama_mahasiswa,
                    ];
                })->values()->toArray(),
            ];
        }

        usort($formattedData, function($a, $b) {
            return $a['tahun_angkatan'] - $b['tahun_angkatan'];
        });

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    /**
     * Get List Mahasiswa Gagal per Tahun Angkatan untuk Prodi Kaprodi
     */
    public function getListMahasiswaGagalByAngkatan($filters = [])
    {
        $prodiId = $this->getProdiId();

        if (empty($filters['tahun_masuk'])) {
            throw new \InvalidArgumentException('tahun_masuk is required');
        }

        $sql = "
            SELECT
                m.id as mahasiswa_id,
                m.nim,
                u.name as nama_mahasiswa,
                mk.id as matakuliah_id,
                mk.kode as kode_matakuliah,
                mk.name as nama_matakuliah,
                khs.nilai_akhir_huruf as nilai
            FROM khs_krs_mahasiswa khs
            JOIN mahasiswa m ON khs.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
            JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
            WHERE khs.nilai_akhir_huruf = 'E'
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
            AND m.prodi_id = ?
            AND akademik.tahun_masuk = ?
            ORDER BY u.name ASC, mk.name ASC
        ";

        $results = DB::select($sql, [$prodiId, $filters['tahun_masuk']]);

        // Group by mahasiswa
        $groupedByMahasiswa = collect($results)->groupBy('mahasiswa_id');

        $mahasiswaList = [];
        foreach ($groupedByMahasiswa as $mahasiswaId => $nilaiList) {
            $firstItem = $nilaiList->first();

            $matakuliahList = $nilaiList->map(function ($mk) {
                return [
                    'matakuliah_id' => (int) $mk->matakuliah_id,
                    'kode_matakuliah' => $mk->kode_matakuliah,
                    'nama_matakuliah' => $mk->nama_matakuliah,
                    'nilai' => $mk->nilai,
                ];
            })->values()->toArray();

            $mahasiswaList[] = [
                'mahasiswa_id' => (int) $mahasiswaId,
                'nim' => $firstItem->nim,
                'nama_mahasiswa' => $firstItem->nama_mahasiswa,
                'matakuliah' => $matakuliahList,
                'jumlah_matakuliah_gagal' => count($matakuliahList),
            ];
        }

        // Sort by nama_mahasiswa
        usort($mahasiswaList, function($a, $b) {
            return strcmp($a['nama_mahasiswa'], $b['nama_mahasiswa']);
        });

        return [
            'tahun_angkatan' => (int) $filters['tahun_masuk'],
            'total_mahasiswa' => count($mahasiswaList),
            'data' => $mahasiswaList,
        ];
    }
}
