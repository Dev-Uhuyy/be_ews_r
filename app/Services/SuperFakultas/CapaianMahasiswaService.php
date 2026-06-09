<?php

declare(strict_types=1);

namespace App\Services\SuperFakultas;

use Illuminate\Support\Facades\DB;

class CapaianMahasiswaService
{
    public function getTop10MatakuliahGagal(array $filters = []): array
    {
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
                DB::raw('COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal')
            )
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'mk.id', 'mk.kode', 'mk.name', 'mk.sks');

        if (! empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (! empty($filters['tahun_masuk'])) {
            $query->where('am.tahun_masuk', $filters['tahun_masuk']);
        }

        $results = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('jumlah_mahasiswa_gagal', 'desc')
            ->get();

        $groupedByProdi = $results->groupBy('prodi_id');

        $totalGagalAll = 0;
        $formattedData = [];
        foreach ($groupedByProdi as $prodiId => $matakuliahList) {
            $top10 = $matakuliahList->take(10)->values();
            $prodiInfo = $top10->first();

            $totalPerProdi = $matakuliahList->sum('jumlah_mahasiswa_gagal');
            $totalGagalAll += $totalPerProdi;

            $formattedData[] = [
                'prodi' => [
                    'id' => $prodiId,
                    'kode_prodi' => $prodiInfo->kode_prodi,
                    'nama_prodi' => $prodiInfo->nama_prodi,
                ],
                'total_mahasiswa_gagal' => $totalPerProdi,
                'top_matakuliah_gagal' => $top10->map(fn ($mk) => [
                    'matakuliah_id' => $mk->matakuliah_id,
                    'kode_matakuliah' => $mk->kode_matakuliah,
                    'nama_matakuliah' => $mk->nama_matakuliah,
                    'sks' => $mk->sks,
                    'jumlah_mahasiswa_gagal' => $mk->jumlah_mahasiswa_gagal,
                ])->toArray(),
            ];
        }

        usort($formattedData, fn ($a, $b) => strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']));

        return [
            'total_prodi' => count($formattedData),
            'total_gagal' => $totalGagalAll,
            'data_per_prodi' => $formattedData,
        ];
    }

    public function getRataRataIpsPerTahunProdi(array $filters = []): array
    {
        $query = DB::table('ips_mahasiswa as im')
            ->join('akademik_mahasiswa as akademik', 'im.mahasiswa_id', '=', 'akademik.mahasiswa_id')
            ->join('mahasiswa as m', 'im.mahasiswa_id', '=', 'm.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'prodis.id as prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'akademik.tahun_masuk as tahun_masuk',
                DB::raw('COUNT(DISTINCT im.mahasiswa_id) as jumlah_mahasiswa'),
                DB::raw('ROUND(AVG(im.ips_1), 2) as avg_ips_1'),
                DB::raw('ROUND(AVG(im.ips_2), 2) as avg_ips_2'),
                DB::raw('ROUND(AVG(im.ips_3), 2) as avg_ips_3'),
                DB::raw('ROUND(AVG(im.ips_4), 2) as avg_ips_4'),
                DB::raw('ROUND(AVG(im.ips_5), 2) as avg_ips_5'),
                DB::raw('ROUND(AVG(im.ips_6), 2) as avg_ips_6'),
                DB::raw('ROUND(AVG(im.ips_7), 2) as avg_ips_7'),
                DB::raw('ROUND(AVG(im.ips_8), 2) as avg_ips_8'),
                DB::raw('ROUND(AVG(im.ips_9), 2) as avg_ips_9'),
                DB::raw('ROUND(AVG(im.ips_10), 2) as avg_ips_10'),
                DB::raw('ROUND(AVG(im.ips_11), 2) as avg_ips_11'),
                DB::raw('ROUND(AVG(im.ips_12), 2) as avg_ips_12'),
                DB::raw('ROUND(AVG(im.ips_13), 2) as avg_ips_13'),
                DB::raw('ROUND(AVG(im.ips_14), 2) as avg_ips_14')
            )
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'akademik.tahun_masuk');

        if (! empty($filters['prodi_id'])) {
            $query->where('m.prodi_id', $filters['prodi_id']);
        }

        $results = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('akademik.tahun_masuk', 'asc')
            ->get();

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
                'prodi' => [
                    'id' => (int) $row->prodi_id,
                    'kode_prodi' => $row->kode_prodi,
                    'nama_prodi' => $row->nama_prodi,
                ],
                'data_per_angkatan' => [[
                    'tahun_masuk' => (int) $row->tahun_masuk,
                    'jumlah_mahasiswa' => (int) $row->jumlah_mahasiswa,
                    'ips_per_semester' => $ipsPerSemester,
                ]],
            ];
        }

        $groupedByProdi = collect($formattedData)->groupBy('prodi.id');
        $finalData = [];
        foreach ($groupedByProdi as $prodiId => $items) {
            $firstItem = $items->first();
            $dataPerAngkatan = array_map(fn ($item) => $item['data_per_angkatan'][0], $items->values()->toArray());

            $finalData[] = [
                'prodi' => $firstItem['prodi'],
                'data_per_angkatan' => $dataPerAngkatan,
            ];
        }

        usort($finalData, fn ($a, $b) => strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']));

        return [
            'total_prodi' => count($finalData),
            'data_per_prodi' => $finalData,
        ];
    }

    public function getTabelCapaianMahasiswa(array $filters = []): array
    {
        $gagalQuery = DB::table('khs_krs_mahasiswa as khs')
            ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
            ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->where('khs.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'prodis.id as prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                DB::raw('COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal')
            )
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama');

        if (! empty($filters['prodi_id'])) {
            $gagalQuery->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }

        $gagalByProdi = $gagalQuery->get()->keyBy('prodi_id');

        $ipsQuery = DB::table('ips_mahasiswa as im')
            ->join('akademik_mahasiswa as akademik', 'im.mahasiswa_id', '=', 'akademik.mahasiswa_id')
            ->join('mahasiswa as m', 'im.mahasiswa_id', '=', 'm.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'm.prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'im.ips_1', 'im.ips_2', 'im.ips_3', 'im.ips_4', 'im.ips_5', 'im.ips_6', 'im.ips_7', 'im.ips_8',
                'im.ips_9', 'im.ips_10', 'im.ips_11', 'im.ips_12', 'im.ips_13', 'im.ips_14'
            );

        if (! empty($filters['prodi_id'])) {
            $ipsQuery->where('m.prodi_id', $filters['prodi_id']);
        }

        $ipsResults = $ipsQuery->get();

        $ipsDataByProdi = [];
        foreach ($ipsResults as $row) {
            $prodiId = $row->prodi_id;
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
                if (! isset($ipsDataByProdi[$prodiId])) {
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

        $sksQuery = DB::table('akademik_mahasiswa as akademik')
            ->join('mahasiswa as m', 'akademik.mahasiswa_id', '=', 'm.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'm.prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                DB::raw('ROUND(AVG(akademik.sks_lulus), 2) as rata_rata_sks_lulus')
            )
            ->groupBy('m.prodi_id', 'prodis.kode_prodi', 'prodis.nama');

        if (! empty($filters['prodi_id'])) {
            $sksQuery->where('m.prodi_id', $filters['prodi_id']);
        }

        $sksResults = $sksQuery->get()->keyBy('prodi_id');

        $formattedData = [];
        foreach ($ipsDataByProdi as $prodiId => $data) {
            $trenIPS = 'Stabil';
            $count = $data['count'];
            if ($count > 0) {
                $avgTerakhir = $data['total_ips_terakhir'] / $count;
                $avgSebelum = $data['total_ips_sebelum'] / $count;
                $trenIPS = $avgTerakhir > $avgSebelum ? 'Naik' : ($avgTerakhir < $avgSebelum ? 'Turun' : 'Stabil');
            }

            $gagalData = $gagalByProdi->get($prodiId);
            $sksData = $sksResults->get($prodiId);

            $formattedData[] = [
                'prodi' => [
                    'id' => (int) $prodiId,
                    'kode_prodi' => $data['kode_prodi'],
                    'nama_prodi' => $data['nama_prodi'],
                ],
                'tren_ips' => $trenIPS,
                'jumlah_matakuliah_gagal' => $gagalData ? (int) $gagalData->jumlah_matakuliah_gagal : 0,
                'rata_rata_sks_lulus' => $sksData ? (float) $sksData->rata_rata_sks_lulus : 0,
            ];
        }

        return [
            'total_prodi' => count($formattedData),
            'data_per_prodi' => $formattedData,
        ];
    }

    public function getDetailTabelCapaianMahasiswa(array $filters = []): array
    {
        $gagalQuery = DB::table('khs_krs_mahasiswa as khs')
            ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
            ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->join('akademik_mahasiswa as akademik', 'khs.mahasiswa_id', '=', 'akademik.mahasiswa_id')
            ->where('khs.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'prodis.id as prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'akademik.tahun_masuk as tahun_angkatan',
                DB::raw('COUNT(DISTINCT mk.id) as jumlah_matakuliah_gagal')
            )
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'akademik.tahun_masuk');

        if (! empty($filters['prodi_id'])) {
            $gagalQuery->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }

        $gagalResults = $gagalQuery->get();
        $gagalByKey = $gagalResults->keyBy(fn ($item) => $item->prodi_id.'_'.$item->tahun_angkatan);

        $ipsQuery = DB::table('ips_mahasiswa as im')
            ->join('akademik_mahasiswa as akademik', 'im.mahasiswa_id', '=', 'akademik.mahasiswa_id')
            ->join('mahasiswa as m', 'im.mahasiswa_id', '=', 'm.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'm.prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'akademik.tahun_masuk as tahun_angkatan',
                'im.ips_1', 'im.ips_2', 'im.ips_3', 'im.ips_4', 'im.ips_5', 'im.ips_6', 'im.ips_7', 'im.ips_8',
                'im.ips_9', 'im.ips_10', 'im.ips_11', 'im.ips_12', 'im.ips_13', 'im.ips_14'
            );

        if (! empty($filters['prodi_id'])) {
            $ipsQuery->where('m.prodi_id', $filters['prodi_id']);
        }

        $ipsResults = $ipsQuery->get();

        $ipsDataByKey = [];
        foreach ($ipsResults as $row) {
            $key = $row->prodi_id.'_'.$row->tahun_angkatan;
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
                if (! isset($ipsDataByKey[$key])) {
                    $ipsDataByKey[$key] = [
                        'prodi_id' => $row->prodi_id,
                        'kode_prodi' => $row->kode_prodi,
                        'nama_prodi' => $row->nama_prodi,
                        'tahun_angkatan' => $row->tahun_angkatan,
                        'total_ips_terakhir' => 0,
                        'total_ips_sebelum' => 0,
                        'count' => 0,
                        'total_sks_lulus' => 0,
                        'count_sks' => 0,
                    ];
                }
                $ipsDataByKey[$key]['total_ips_terakhir'] += $ipsTerakhir;
                $ipsDataByKey[$key]['total_ips_sebelum'] += $ipsSebelum;
                $ipsDataByKey[$key]['count']++;
            }
        }

        $sksQuery = DB::table('akademik_mahasiswa as akademik')
            ->join('mahasiswa as m', 'akademik.mahasiswa_id', '=', 'm.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'm.prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'akademik.tahun_masuk as tahun_angkatan',
                DB::raw('SUM(akademik.sks_lulus) as total_sks_lulus'),
                DB::raw('COUNT(*) as jumlah_mahasiswa')
            )
            ->groupBy('m.prodi_id', 'prodis.kode_prodi', 'prodis.nama', 'akademik.tahun_masuk');

        if (! empty($filters['prodi_id'])) {
            $sksQuery->where('m.prodi_id', $filters['prodi_id']);
        }

        $sksResults = $sksQuery->get()->keyBy(fn ($item) => $item->prodi_id.'_'.$item->tahun_angkatan);

        foreach ($ipsDataByKey as $key => &$data) {
            $sksData = $sksResults->get($key);
            if ($sksData && $sksData->jumlah_mahasiswa > 0) {
                $data['rata_rata_sks_lulus'] = round($sksData->total_sks_lulus / $sksData->jumlah_mahasiswa, 2);
            } else {
                $data['rata_rata_sks_lulus'] = 0;
            }
        }

        $formattedData = [];
        foreach ($ipsDataByKey as $key => $data) {
            $trenIPS = 'Stabil';
            $count = $data['count'];
            if ($count > 0) {
                $avgTerakhir = $data['total_ips_terakhir'] / $count;
                $avgSebelum = $data['total_ips_sebelum'] / $count;
                $trenIPS = $avgTerakhir > $avgSebelum ? 'Naik' : ($avgTerakhir < $avgSebelum ? 'Turun' : 'Stabil');
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
                'rata_rata_sks_lulus' => $data['rata_rata_sks_lulus'] ?? 0,
            ];
        }

        usort($formattedData, fn ($a, $b) => $a['tahun_angkatan'] - $b['tahun_angkatan']);

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    public function getListMataKuliahPerProdi(array $filters = []): array
    {
        $groupByAngkatan = ! empty($filters['tahun_masuk']);

        if ($groupByAngkatan) {
            $query = DB::table('khs_krs_mahasiswa as khs')
                ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
                ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->join('akademik_mahasiswa as akademik', 'khs.mahasiswa_id', '=', 'akademik.mahasiswa_id')
                ->where('khs.nilai_akhir_huruf', 'E')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
                ->where('akademik.tahun_masuk', $filters['tahun_masuk'])
                ->select(
                    'prodis.id as prodi_id',
                    'prodis.kode_prodi',
                    'prodis.nama as nama_prodi',
                    'akademik.tahun_masuk as tahun_angkatan',
                    'mk.id as matakuliah_id',
                    'mk.kode as kode_matakuliah',
                    'mk.name as nama_matakuliah',
                    'mk.sks',
                    DB::raw('COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal')
                )
                ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'akademik.tahun_masuk', 'mk.id', 'mk.kode', 'mk.name', 'mk.sks')
                ->orderBy('prodis.nama', 'asc')
                ->orderBy('jumlah_mahasiswa_gagal', 'desc');

            if (! empty($filters['prodi_id'])) {
                $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
            }

            $results = $query->get();
            $groupedByKey = $results->groupBy(fn ($item) => $item->prodi_id.'_'.$item->tahun_angkatan);

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
                    'matakuliah' => $matakuliahList->map(fn ($mk) => [
                        'matakuliah_id' => (int) $mk->matakuliah_id,
                        'kode_matakuliah' => $mk->kode_matakuliah,
                        'nama_matakuliah' => $mk->nama_matakuliah,
                        'sks' => (int) $mk->sks,
                        'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                    ])->values()->toArray(),
                ];
            }
        } else {
            $query = DB::table('khs_krs_mahasiswa as khs')
                ->join('mata_kuliahs as mk', 'khs.matakuliah_id', '=', 'mk.id')
                ->join('mahasiswa', 'khs.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->where('khs.nilai_akhir_huruf', 'E')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
                ->select(
                    'prodis.id as prodi_id',
                    'prodis.kode_prodi',
                    'prodis.nama as nama_prodi',
                    'mk.id as matakuliah_id',
                    'mk.kode as kode_matakuliah',
                    'mk.name as nama_matakuliah',
                    'mk.sks',
                    DB::raw('COUNT(DISTINCT khs.mahasiswa_id) as jumlah_mahasiswa_gagal')
                )
                ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama', 'mk.id', 'mk.kode', 'mk.name', 'mk.sks')
                ->orderBy('prodis.nama', 'asc')
                ->orderBy('jumlah_mahasiswa_gagal', 'desc');

            if (! empty($filters['prodi_id'])) {
                $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
            }

            $results = $query->get();
            $groupedByProdi = $results->groupBy('prodi_id');

            $formattedData = [];
            foreach ($groupedByProdi as $prodiId => $matakuliahList) {
                $firstItem = $matakuliahList->first();
                $formattedData[] = [
                    'prodi' => [
                        'id' => (int) $firstItem->prodi_id,
                        'kode_prodi' => $firstItem->kode_prodi,
                        'nama_prodi' => $firstItem->nama_prodi,
                    ],
                    'matakuliah' => $matakuliahList->map(fn ($mk) => [
                        'matakuliah_id' => (int) $mk->matakuliah_id,
                        'kode_matakuliah' => $mk->kode_matakuliah,
                        'nama_matakuliah' => $mk->nama_matakuliah,
                        'sks' => (int) $mk->sks,
                        'jumlah_mahasiswa_gagal' => (int) $mk->jumlah_mahasiswa_gagal,
                    ])->values()->toArray(),
                ];
            }
        }

        usort($formattedData, fn ($a, $b) => strcmp($a['prodi']['nama_prodi'], $b['prodi']['nama_prodi']));

        return [
            'total_prodi' => count($formattedData),
            'data_per_prodi' => $formattedData,
        ];
    }

    public function getListMahasiswaGagalPerMataKuliah(array $filters = []): array
    {
        if (empty($filters['matakuliah_id'])) {
            throw new \InvalidArgumentException('matakuliah_id is required');
        }

        $query = DB::table('khs_krs_mahasiswa as khs')
            ->join('mahasiswa as m', 'khs.mahasiswa_id', '=', 'm.id')
            ->join('users as u', 'm.user_id', '=', 'u.id')
            ->join('prodis', 'm.prodi_id', '=', 'prodis.id')
            ->join('akademik_mahasiswa as akademik', 'khs.mahasiswa_id', '=', 'akademik.mahasiswa_id')
            ->join('kelompok_mata_kuliah as kel', 'khs.kelompok_id', '=', 'kel.id')
            ->join('dosen as dos', 'kel.dosen_pengampu_id', '=', 'dos.id')
            ->join('users as dos_user', 'dos.user_id', '=', 'dos_user.id')
            ->where('khs.matakuliah_id', $filters['matakuliah_id'])
            ->where('khs.nilai_akhir_huruf', 'E')
            ->whereRaw('LOWER(m.status_mahasiswa) NOT IN (\'lulus\', \'do\')')
            ->select(
                'm.id as mahasiswa_id',
                'm.nim',
                'u.name as nama_mahasiswa',
                'prodis.id as prodi_id',
                'prodis.kode_prodi',
                'prodis.nama as nama_prodi',
                'akademik.tahun_masuk as tahun_angkatan',
                'kel.id as kelompok_id',
                'kel.kode as kode_kelompok',
                'dos_user.name as nama_dosen_pengampu',
                'khs.absen'
            )
            ->orderBy('prodis.nama', 'asc')
            ->orderBy('akademik.tahun_masuk', 'asc')
            ->orderBy('kel.kode', 'asc')
            ->orderBy('u.name', 'asc');

        if (! empty($filters['prodi_id'])) {
            $query->where('m.prodi_id', $filters['prodi_id']);
        }
        if (! empty($filters['tahun_masuk'])) {
            $query->where('akademik.tahun_masuk', $filters['tahun_masuk']);
        }

        $results = $query->get();
        $groupedByKey = $results->groupBy(fn ($item) => $item->prodi_id.'_'.$item->tahun_angkatan.'_'.$item->kelompok_id);

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
                'dosen_pengampu' => $firstItem->nama_dosen_pengampu,
                'kode_kelompok' => $firstItem->kode_kelompok,
                'jumlah_mahasiswa' => $mahasiswaList->count(),
                'mahasiswa' => $mahasiswaList->map(fn ($m) => [
                    'mahasiswa_id' => (int) $m->mahasiswa_id,
                    'nim' => $m->nim,
                    'nama_mahasiswa' => $m->nama_mahasiswa,
                    'presensi' => $m->absen ?? 0,
                ])->values()->toArray(),
            ];
        }

        usort($formattedData, fn ($a, $b) => $a['tahun_angkatan'] - $b['tahun_angkatan']);

        return [
            'total_data' => count($formattedData),
            'data' => $formattedData,
        ];
    }

    // ── Export Wrappers ──────────────────────────────────────────────────────

    public function exportTopMatakuliahGagal($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianTopMatakuliahExportService::class)
            ->exportXlsx($filters);
    }

    public function exportRataRataIps($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianRataRataIpsExportService::class)
            ->exportXlsx($filters);
    }

    public function exportTabelCapaian($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianTabelCapaianExportService::class)
            ->exportXlsx($filters);
    }

    public function exportTabelCapaianDetail($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianTabelCapaianDetailExportService::class)
            ->exportXlsx($filters);
    }

    public function exportListMatakuliah($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianListMatakuliahExportService::class)
            ->exportXlsx($filters);
    }

    public function exportMahasiswaGagal($filters = [])
    {
        return app(\App\Services\SuperFakultas\Export\CapaianMahasiswaGagalExportService::class)
            ->exportXlsx($filters);
    }
}
