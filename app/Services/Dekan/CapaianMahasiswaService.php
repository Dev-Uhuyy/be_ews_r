<?php

namespace App\Services\Dekan;

use Illuminate\Support\Facades\DB;

class CapaianMahasiswaService
{
    /**
     * Get Top 10 Mata Kuliah Gagal (E) per Prodi
     *
     * Mengembalikan data mata kuliah dengan nilai E (gagal) terbanyak
     * beserta jumlah mahasiswa yang gagal per matakuliah tersebut
     *
     * Filter options:
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     * - tahun_masuk (optional): Filter berdasarkan tahun angkatan
     *
     * @param array $filters
     * @return array
     */
    public function getTop10MatakuliahGagal($filters = [])
    {
        // Main query: aggregate nilai E per matakuliah per prodi
        $query = DB::table('khs_krs_mahasiswa as khs')
            ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
            ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->join('akademik_mahasiswa as am', 'khs.mahasiswa_id', '=', 'am.mahasiswa_id')
            ->where('khs.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                'prodis.id as prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'mk.id as matakuliah_id',
                'mk.kode as kode_matakuliah',
                'mk.name as nama_matakuliah',
                'mk.sks',
                DB::raw('COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal'),
                DB::raw('MAX(khs.id) as latest_khs_id')
            )
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'mk.id', 'mk.kode', 'mk.name', 'mk.sks');

        // Apply filters
        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('am.tahun_masuk', $filters['tahun_masuk']);
        }

        // Get all results first
        $results = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('jumlah_mahasiswa_gagal', 'desc')
            ->get();

        // Group by prodi and limit to top 10 per prodi
        $groupedByProdi = $results->groupBy('prodi_id');

        $formattedData = [];
        foreach ($groupedByProdi as $prodiId => $matakuliahList) {
            $top10 = $matakuliahList->take(10)->values();

            $prodiInfo = $top10->first();
            $formattedData[] = [
                'prodi' => [
                    'id' => $prodiId,
                    'kode_prodi' => $prodiInfo->kode_prodi,
                    'nama_prodi' => $prodiInfo->nama_prodi,
                ],
                'top_matakuliah_gagal' => $top10->map(function ($mk) {
                    return [
                        'matakuliah_id' => $mk->matakuliah_id,
                        'kode_matakuliah' => $mk->kode_matakuliah,
                        'nama_matakuliah' => $mk->nama_matakuliah,
                        'sks' => $mk->sks,
                        'jumlah_mahasiswa_gagal' => $mk->jumlah_mahasiswa_gagal,
                    ];
                })->toArray(),
            ];
        }

        // Sort by prodi name
        usort($formattedData, function($a, $b) {
            return strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']);
        });

        return [
            'total_prodi' => count($formattedData),
            'data_per_prodi' => $formattedData,
        ];
    }

    /**
     * Get Rata-rata IPS per Semester per Angkatan per Prodi
     *
     * Mengembalikan data rata-rata IPS mahasiswa per semester,
     * dikelompokkan per prodi dan tahun angkatan.
     * Hanya menampilkan semester yang действительно punya data (IPS > 0).
     *
     * Filter options:
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     *
     * @param array $filters
     * @return array
     */
    public function getRataRataIpsPerTahunProdi($filters = [])
    {
        // Get aggregated IPS data per prodi per angkatan
        $sql = "
            SELECT
                prodis.id as prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
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
            JOIN prodis ON m.prodi_id = prodis.id
            WHERE LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sql .= " AND m.prodi_id = " . intval($filters['prodi_id']);
        }

        $sql .= " GROUP BY prodis.id, prodis.kode_prodi, prodis.nama, akademik.tahun_masuk ORDER BY prodis.nama ASC, akademik.tahun_masuk ASC";

        $results = DB::select($sql);

        $formattedData = [];
        foreach ($results as $row) {
            $ipsPerSemester = [];

            // Check each semester (ips_1 to ips_14) - only include if avg > 0
            for ($i = 1; $i <= 14; $i++) {
                $colName = "avg_ips_$i";
                // Only include if value exists and > 0
                if ($row->$colName !== null && $row->$colName > 0) {
                    $ipsPerSemester["ips_$i"] = (float) $row->$colName;
                }
            }

            $formattedData[] = [
                'prodi' => [
                    'id' => (int) $row->prodi_id,
                    'kode_prodi' => $row->kode_prodi,
                    'nama_prodi' => $row->nama_prodi,
                ],
                'data_per_angkatan' => [
                    [
                        'tahun_masuk' => (int) $row->tahun_masuk,
                        'jumlah_mahasiswa' => (int) $row->jumlah_mahasiswa,
                        'ips_per_semester' => $ipsPerSemester,
                    ]
                ],
            ];
        }

        // Group by prodi since we want single prodi object with multiple angkatan
        $groupedByProdi = collect($formattedData)->groupBy('prodi.id');

        $finalData = [];
        foreach ($groupedByProdi as $prodiId => $items) {
            $firstItem = $items->first();
            $dataPerAngkatan = array_map(function ($item) {
                return [
                    'tahun_masuk' => $item['data_per_angkatan'][0]['tahun_masuk'],
                    'jumlah_mahasiswa' => $item['data_per_angkatan'][0]['jumlah_mahasiswa'],
                    'ips_per_semester' => $item['data_per_angkatan'][0]['ips_per_semester'],
                ];
            }, $items->values()->toArray());

            $finalData[] = [
                'prodi' => $firstItem['prodi'],
                'data_per_angkatan' => $dataPerAngkatan,
            ];
        }

        // Sort by prodi name
        usort($finalData, function($a, $b) {
            return strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']);
        });

        return [
            'total_prodi' => count($finalData),
            'data_per_prodi' => $finalData,
        ];
    }

    /**
     * Get Tabel Capaian Mahasiswa
     *
     * Mengembalikan ringkasan capaian mahasiswa per prodi yang meliputi:
     * - Nama Prodi
     * - Tren IPS (Naik / Turun / Stabil) berdasarkan perbandingan
     *   rata-rata IPS terakhir vs rata-rata IPS sebelumnya dari seluruh mahasiswa
     *   (IPS terakhir = kolom ips_X tertinggi yang ada nilainya per mahasiswa)
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per prodi
     *
     * Filter options:
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     *
     * @param array $filters
     * @return array
     */
    public function getTabelCapaianMahasiswa($filters = [])
    {
        // Get jumlah matakuliah gagal per prodi
        // Count DISTINCT mk.id per prodi (abaikan angkatan)
        // Jadi 1 MK yang gagal di angkatan manapun tetap hanya dihitung 1x per prodi
        $sqlGagal = "
            SELECT
                prodis.id as prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
                COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal
            FROM khs_krs_mahasiswa khs
            JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
            JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
            JOIN prodis ON mahasiswa.prodi_id = prodis.id
            WHERE khs.nilai_akhir_huruf = 'E'
            AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sqlGagal .= " AND mahasiswa.prodi_id = " . intval($filters['prodi_id']);
        }

        $sqlGagal .= " GROUP BY prodis.id, prodis.kode_prodi, prodis.nama ORDER BY prodis.nama ASC";

        $gagalResults = DB::select($sqlGagal);
        $gagalByProdi = collect($gagalResults)->keyBy('prodi_id');

        // Get all IPS data for trend calculation
        $sqlIps = "
            SELECT
                im.mahasiswa_id,
                m.prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
                im.ips_1, im.ips_2, im.ips_3, im.ips_4, im.ips_5, im.ips_6, im.ips_7, im.ips_8,
                im.ips_9, im.ips_10, im.ips_11, im.ips_12, im.ips_13, im.ips_14
            FROM ips_mahasiswa im
            JOIN akademik_mahasiswa akademik ON im.mahasiswa_id = akademik.mahasiswa_id
            JOIN mahasiswa m ON im.mahasiswa_id = m.id
            JOIN prodis ON m.prodi_id = prodis.id
            WHERE LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sqlIps .= " AND m.prodi_id = " . intval($filters['prodi_id']);
        }

        $ipsResults = DB::select($sqlIps);

        // Process IPS data to calculate ips_terakhir and ips_sebelum per mahasiswa
        $ipsDataByProdi = [];
        foreach ($ipsResults as $row) {
            $prodiId = $row->prodi_id;

            // Find the last IPS (highest ips_X > 0)
            $ipsTerakhir = null;
            $ipsSebelum = null;
            for ($i = 14; $i >= 1; $i--) {
                $colName = "ips_$i";
                if ($row->$colName !== null && $row->$colName > 0) {
                    if ($ipsTerakhir === null) {
                        $ipsTerakhir = $row->$colName;
                        // Find previous IPS
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
                if (!isset($ipsDataByProdi[$prodiId])) {
                    $ipsDataByProdi[$prodiId] = [
                        'kode_prodi' => $row->kode_prodi,
                        'nama_prodi' => $row->nama_prodi,
                        'total_ips_terakhir' => 0,
                        'total_ips_sebelum' => 0,
                        'count' => 0,
                    ];
                }
                $ipsDataByProdi[$prodiId]['total_ips_terakhir'] += $ipsTerakhir;
                $ipsDataByProdi[$prodiId]['total_ips_sebelum'] += $ipsSebelum;
                $ipsDataByProdi[$prodiId]['count']++;
            }
        }

        // Build result
        $formattedData = [];
        foreach ($ipsDataByProdi as $prodiId => $data) {
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

            $gagalData = $gagalByProdi->get($prodiId);

            $formattedData[] = [
                'prodi' => [
                    'id' => (int) $prodiId,
                    'kode_prodi' => $data['kode_prodi'],
                    'nama_prodi' => $data['nama_prodi'],
                ],
                'tren_ips' => $trenIPS,
                'jumlah_matakuliah_gagal' => $gagalData ? (int) $gagalData->jumlah_matakuliah_gagal : 0,
            ];
        }

        return [
            'total_prodi' => count($formattedData),
            'data_per_prodi' => $formattedData,
        ];
    }

    /**
     * Get Detail Tabel Capaian Mahasiswa (per Tahun Angkatan)
     *
     * Mengembalikan ringkasan capaian mahasiswa per prodi per tahun angkatan yang meliputi:
     * - Prodi info
     * - Tahun Angkatan
     * - Tren IPS (Naik / Turun / Stabil) berdasarkan perbandingan
     *   IPS terakhir vs IPS sebelumnya untuk setiap mahasiswa
     * - Jumlah mata kuliah yang memiliki nilai E (gagal) per prodi per angkatan
     *
     * Filter options:
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     *
     * @param array $filters
     * @return array
     */
    public function getDetailTabelCapaianMahasiswa($filters = [])
    {
        // Get jumlah matakuliah gagal per prodi per angkatan
        // Count DISTINCT mk.id per angkatan (MK sama di angkatan berbeda dihitung terpisah)
        $sqlGagal = "
            SELECT
                prodis.id as prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
                akademik.tahun_masuk as tahun_angkatan,
                COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal
            FROM khs_krs_mahasiswa khs
            JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
            JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
            JOIN prodis ON mahasiswa.prodi_id = prodis.id
            JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
            WHERE khs.nilai_akhir_huruf = 'E'
            AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sqlGagal .= " AND mahasiswa.prodi_id = " . intval($filters['prodi_id']);
        }

        $sqlGagal .= " GROUP BY prodis.id, prodis.kode_prodi, prodis.nama, akademik.tahun_masuk ORDER BY prodis.nama ASC, akademik.tahun_masuk ASC";

        $gagalResults = DB::select($sqlGagal);
        $gagalByKey = collect($gagalResults)->keyBy(function ($item) {
            return $item->prodi_id . '_' . $item->tahun_angkatan;
        });

        // Get all IPS data for trend calculation
        $sqlIps = "
            SELECT
                im.mahasiswa_id,
                m.prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
                akademik.tahun_masuk as tahun_angkatan,
                im.ips_1, im.ips_2, im.ips_3, im.ips_4, im.ips_5, im.ips_6, im.ips_7, im.ips_8,
                im.ips_9, im.ips_10, im.ips_11, im.ips_12, im.ips_13, im.ips_14
            FROM ips_mahasiswa im
            JOIN akademik_mahasiswa akademik ON im.mahasiswa_id = akademik.mahasiswa_id
            JOIN mahasiswa m ON im.mahasiswa_id = m.id
            JOIN prodis ON m.prodi_id = prodis.id
            WHERE LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sqlIps .= " AND m.prodi_id = " . intval($filters['prodi_id']);
        }

        $ipsResults = DB::select($sqlIps);

        // Process IPS data to calculate ips_terakhir and ips_sebelum per mahasiswa
        $ipsDataByKey = [];
        foreach ($ipsResults as $row) {
            $key = $row->prodi_id . '_' . $row->tahun_angkatan;

            // Find the last IPS (highest ips_X > 0)
            $ipsTerakhir = null;
            $ipsSebelum = null;
            for ($i = 14; $i >= 1; $i--) {
                $colName = "ips_$i";
                if ($row->$colName !== null && $row->$colName > 0) {
                    if ($ipsTerakhir === null) {
                        $ipsTerakhir = $row->$colName;
                        // Find previous IPS
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
                if (!isset($ipsDataByKey[$key])) {
                    $ipsDataByKey[$key] = [
                        'prodi_id' => $row->prodi_id,
                        'kode_prodi' => $row->kode_prodi,
                        'nama_prodi' => $row->nama_prodi,
                        'tahun_angkatan' => $row->tahun_angkatan,
                        'total_ips_terakhir' => 0,
                        'total_ips_sebelum' => 0,
                        'count' => 0,
                    ];
                }
                $ipsDataByKey[$key]['total_ips_terakhir'] += $ipsTerakhir;
                $ipsDataByKey[$key]['total_ips_sebelum'] += $ipsSebelum;
                $ipsDataByKey[$key]['count']++;
            }
        }

        // Build result
        $formattedData = [];
        foreach ($ipsDataByKey as $key => $data) {
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

            $gagalData = $gagalByKey->get($key);

            $formattedData[] = [
                'prodi' => [
                    'id' => (int) $data['prodi_id'],
                    'kode_prodi' => $data['kode_prodi'],
                    'nama_prodi' => $data['nama_prodi'],
                ],
                'tahun_angkatan' => (int) $data['tahun_angkatan'],
                'tren_ips' => $trenIPS,
                'jumlah_matakuliah_gagal' => $gagalData ? (int) $gagalData->jumlah_matakuliah_gagal : 0,
            ];
        }

        // Sort by prodi name then tahun angkatan
        usort($formattedData, function($a, $b) {
            $prodiCompare = strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']);
            if ($prodiCompare !== 0) return $prodiCompare;
            return $a['tahun_angkatan'] - $b['tahun_angkatan'];
        });

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    /**
     * Get List Mata Kuliah Gagal per Prodi
     *
     * Mengembalikan daftar mata kuliah yang memiliki nilai E (gagal)
     * per prodi, beserta detail jumlah mahasiswa yang gagal per matakuliah.
     * Jika tahun_masuk diisi, grouping per prodi + angkatan.
     * Jika tahun_masuk kosong, grouping per prodi saja (semua angkatan digabung).
     *
     * Filter options:
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     * - tahun_masuk (optional): Filter berdasarkan tahun angkatan
     *
     * @param array $filters
     * @return array
     */
    public function getListMataKuliahPerProdi($filters = [])
    {
        $groupByAngkatan = !empty($filters['tahun_masuk']);

        if ($groupByAngkatan) {
            // Group by prodi + tahun angkatan
            $sql = "
                SELECT
                    prodis.id as prodi_id,
                    prodis.kode_prodi,
                    prodis.nama as nama_prodi,
                    akademik.tahun_masuk as tahun_angkatan,
                    mk.id as matakuliah_id,
                    mk.kode as kode_matakuliah,
                    mk.name as nama_matakuliah,
                    mk.sks,
                    COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal
                FROM khs_krs_mahasiswa khs
                JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
                JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
                JOIN prodis ON mahasiswa.prodi_id = prodis.id
                JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
                WHERE khs.nilai_akhir_huruf = 'E'
                AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
            ";

            if (!empty($filters['prodi_id'])) {
                $sql .= " AND mahasiswa.prodi_id = " . intval($filters['prodi_id']);
            }

            $sql .= " AND akademik.tahun_masuk = " . intval($filters['tahun_masuk']);

            $sql .= " GROUP BY prodis.id, prodis.kode_prodi, prodis.nama, akademik.tahun_masuk, mk.id, mk.kode, mk.name, mk.sks ORDER BY prodis.nama ASC, jumlah_mahasiswa_gagal DESC";

            $results = DB::select($sql);

            $groupedByKey = collect($results)->groupBy(function ($item) {
                return $item->prodi_id . '_' . $item->tahun_angkatan;
            });

            $formattedData = [];
            foreach ($groupedByKey as $key => $matakuliahList) {
                $firstItem = $matakuliahList->first();

                $formattedData[] = [
                    'prodi' => [
                        'id' => (int) $firstItem->prodi_id,
                        'kode_prodi' => $firstItem->kode_prodi,
                        'nama_prodi' => $firstItem->nama_prodi,
                    ],
                    'tahun_angkatan' => (int) $firstItem->tahun_angkatan,
                    'matakuliah' => $matakuliahList->map(function ($mk) {
                        return [
                            'matakuliah_id' => (int) $mk->matakuliah_id,
                            'kode_matakuliah' => $mk->kode_matakuliah,
                            'nama_matakuliah' => $mk->nama_matakuliah,
                            'sks' => (int) $mk->sks,
                            'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                        ];
                    })->values()->toArray(),
                ];
            }
        } else {
            // Group by prodi only (semua angkatan digabung)
            $sql = "
                SELECT
                    prodis.id as prodi_id,
                    prodis.kode_prodi,
                    prodis.nama as nama_prodi,
                    mk.id as matakuliah_id,
                    mk.kode as kode_matakuliah,
                    mk.name as nama_matakuliah,
                    mk.sks,
                    COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal
                FROM khs_krs_mahasiswa khs
                JOIN mata_kuliahs mk ON khs.matakuliah_id = mk.id
                JOIN mahasiswa ON khs.mahasiswa_id = mahasiswa.id
                JOIN prodis ON mahasiswa.prodi_id = prodis.id
                WHERE khs.nilai_akhir_huruf = 'E'
                AND LOWER(mahasiswa.status_mahasiswa) NOT IN ('lulus', 'do')
            ";

            if (!empty($filters['prodi_id'])) {
                $sql .= " AND mahasiswa.prodi_id = " . intval($filters['prodi_id']);
            }

            $sql .= " GROUP BY prodis.id, prodis.kode_prodi, prodis.nama, mk.id, mk.kode, mk.name, mk.sks ORDER BY prodis.nama ASC, jumlah_mahasiswa_gagal DESC";

            $results = DB::select($sql);

            $groupedByProdi = collect($results)->groupBy('prodi_id');

            $formattedData = [];
            foreach ($groupedByProdi as $prodiId => $matakuliahList) {
                $firstItem = $matakuliahList->first();

                $formattedData[] = [
                    'prodi' => [
                        'id' => (int) $firstItem->prodi_id,
                        'kode_prodi' => $firstItem->kode_prodi,
                        'nama_prodi' => $firstItem->nama_prodi,
                    ],
                    'matakuliah' => $matakuliahList->map(function ($mk) {
                        return [
                            'matakuliah_id' => (int) $mk->matakuliah_id,
                            'kode_matakuliah' => $mk->kode_matakuliah,
                            'nama_matakuliah' => $mk->nama_matakuliah,
                            'sks' => (int) $mk->sks,
                            'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                        ];
                    })->values()->toArray(),
                ];
            }
        }

        // Sort by prodi name
        usort($formattedData, function($a, $b) {
            return strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']);
        });

        return [
            'total_prodi' => count($formattedData),
            'data_per_prodi' => $formattedData,
        ];
    }

    /**
     * Get List Mahasiswa Gagal per Mata Kuliah
     *
     * Mengembalikan daftar mahasiswa yang mendapat nilai E (gagal)
     * pada mata kuliah tertentu, beserta info prodi dan angkatan.
     *
     * Filter options:
     * - matakuliah_id (required): ID Mata Kuliah yang akan dicek
     * - prodi_id (optional): Filter berdasarkan ID Prodi
     * - tahun_masuk (optional): Filter berdasarkan tahun angkatan
     *
     * @param array $filters
     * @return array
     */
    public function getListMahasiswaGagalPerMataKuliah($filters = [])
    {
        if (empty($filters['matakuliah_id'])) {
            throw new \InvalidArgumentException('matakuliah_id is required');
        }

        $sql = "
            SELECT
                m.id as mahasiswa_id,
                m.nim,
                u.name as nama_mahasiswa,
                prodis.id as prodi_id,
                prodis.kode_prodi,
                prodis.nama as nama_prodi,
                akademik.tahun_masuk as tahun_angkatan,
                khs.nilai_akhir_huruf as nilai
            FROM khs_krs_mahasiswa khs
            JOIN mahasiswa m ON khs.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN prodis ON m.prodi_id = prodis.id
            JOIN akademik_mahasiswa akademik ON khs.mahasiswa_id = akademik.mahasiswa_id
            WHERE khs.matakuliah_id = " . intval($filters['matakuliah_id']) . "
            AND khs.nilai_akhir_huruf = 'E'
            AND LOWER(m.status_mahasiswa) NOT IN ('lulus', 'do')
        ";

        if (!empty($filters['prodi_id'])) {
            $sql .= " AND m.prodi_id = " . intval($filters['prodi_id']);
        }

        if (!empty($filters['tahun_masuk'])) {
            $sql .= " AND akademik.tahun_masuk = " . intval($filters['tahun_masuk']);
        }

        $sql .= " ORDER BY prodis.nama ASC, akademik.tahun_masuk ASC, u.name ASC";

        $results = DB::select($sql);

        // Group by prodi + angkatan
        $groupedByKey = collect($results)->groupBy(function ($item) {
            return $item->prodi_id . '_' . $item->tahun_angkatan;
        });

        $formattedData = [];
        foreach ($groupedByKey as $key => $mahasiswaList) {
            $firstItem = $mahasiswaList->first();

            $formattedData[] = [
                'prodi' => [
                    'id' => (int) $firstItem->prodi_id,
                    'kode_prodi' => $firstItem->kode_prodi,
                    'nama_prodi' => $firstItem->nama_prodi,
                ],
                'tahun_angkatan' => (int) $firstItem->tahun_angkatan,
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

        // Sort by prodi name then tahun angkatan
        usort($formattedData, function($a, $b) {
            $prodiCompare = strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']);
            if ($prodiCompare !== 0) return $prodiCompare;
            return $a['tahun_angkatan'] - $b['tahun_angkatan'];
        });

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }
}