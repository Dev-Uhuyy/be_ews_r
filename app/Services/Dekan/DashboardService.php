<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Membantu filter data query berdasarkan role prodi
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

    /**
     * Get status mahasiswa untuk batch prodis
     * Returns data keyed by prodi_id
     */
    public function getStatusMahasiswaBatch(array $prodiIds)
    {
        $result = [];

        foreach ($prodiIds as $prodiId) {
            $baseQuery = Mahasiswa::whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
                ->where('prodi_id', $prodiId);

            $totalMahasiswa = (clone $baseQuery)->count();

            $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
                ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
                ->where('prodi_id', $prodiId)
                ->groupBy('status_mahasiswa')
                ->get()
                ->keyBy('status_mahasiswa');

            $result[$prodiId] = [
                'total' => $totalMahasiswa,
                'aktif' => ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0),
                'mangkir' => ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0),
                'cuti' => ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Get rata IPK per angkatan untuk batch prodis
     * Returns data keyed by prodi_id
     */
    public function getRataIpkPerAngkatanBatch(array $prodiIds)
    {
        $result = [];

        foreach ($prodiIds as $prodiId) {
            $result[$prodiId] = AkademikMahasiswa::select(
                    'tahun_masuk',
                    DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'),
                    DB::raw('COUNT(*) as jumlah_mahasiswa')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->whereNotNull('tahun_masuk')
                ->whereNotNull('ipk')
                ->where('ipk', '>', 0)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->groupBy('tahun_masuk')
                ->orderBy('tahun_masuk', 'desc')
                ->get();
        }

        return $result;
    }

    /**
     * Get status kelulusan untuk batch prodis
     * Returns data keyed by prodi_id
     */
    public function getStatusKelulusanBatch(array $prodiIds)
    {
        $result = [];

        foreach ($prodiIds as $prodiId) {
            $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('early_warning_system.status_kelulusan', 'eligible')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->count();

            $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('early_warning_system.status_kelulusan', 'noneligible')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->count();

            $result[$prodiId] = [
                'total' => $eligible + $noneligible,
                'eligible' => $eligible,
                'tidak_eligible' => $noneligible,
            ];
        }

        return $result;
    }

    /**
     * Get combined dashboard data for all prodis (single pass queries)
     * Returns data keyed by prodi_id
     */
    public function getDashboardBatch(array $prodiIds)
    {
        $result = [];

        // Get all prodis data in bulk
        $prodis = \App\Models\Prodi::whereIn('id', $prodiIds)->get()->keyBy('id');

        // Single query for status mahasiswa counts by prodi
        $statusCountsByProdi = DB::table('mahasiswa')
            ->select('prodi_id', 'status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('prodi_id', $prodiIds)
            ->groupBy('prodi_id', 'status_mahasiswa')
            ->get()
            ->groupBy('prodi_id');

        // Single query for rata IPK per angkatan per prodi
        $rataIpkByProdi = AkademikMahasiswa::select(
                'mahasiswa.prodi_id',
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as rata_ipk'),
                DB::raw('COUNT(*) as jumlah_mahasiswa')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereNotNull('akademik_mahasiswa.ipk')
            ->where('akademik_mahasiswa.ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->groupBy('prodi_id');

        // Single query for status kelulusan by prodi
        $kelulusanByProdi = DB::table('early_warning_system')
            ->select(
                'mahasiswa.prodi_id',
                'early_warning_system.status_kelulusan',
                DB::raw('COUNT(*) as jumlah')
            )
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->groupBy('mahasiswa.prodi_id', 'early_warning_system.status_kelulusan')
            ->get()
            ->groupBy('prodi_id');

        foreach ($prodiIds as $prodiId) {
            $prodi = $prodis->get($prodiId);
            if (!$prodi) continue;

            // Process status mahasiswa
            $statusProdi = $statusCountsByProdi->get($prodiId, collect());
            $totalMahasiswa = $statusProdi->sum('jumlah');

            $result[$prodiId] = [
                'prodi' => [
                    'id' => $prodi->id,
                    'kode' => $prodi->kode_prodi,
                    'nama' => $prodi->nama,
                ],
                'status_mahasiswa' => [
                    'total' => $totalMahasiswa,
                    'aktif' => $statusProdi->where('status_mahasiswa', 'aktif')->sum('jumlah') +
                               $statusProdi->where('status_mahasiswa', 'Aktif')->sum('jumlah'),
                    'mangkir' => $statusProdi->where('status_mahasiswa', 'mangkir')->sum('jumlah') +
                                 $statusProdi->where('status_mahasiswa', 'Mangkir')->sum('jumlah'),
                    'cuti' => $statusProdi->where('status_mahasiswa', 'cuti')->sum('jumlah') +
                              $statusProdi->where('status_mahasiswa', 'Cuti')->sum('jumlah'),
                ],
                'rata_ipk_per_angkatan' => $rataIpkByProdi->get($prodiId, collect())->map(function ($item) {
                    return [
                        'tahun_masuk' => $item->tahun_masuk,
                        'rata_ipk' => $item->rata_ipk,
                        'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                    ];
                })->values(),
                'status_kelulusan' => [
                    'total' => $kelulusanByProdi->get($prodiId, collect())->sum('jumlah'),
                    'eligible' => $kelulusanByProdi->get($prodiId, collect())->where('status_kelulusan', 'eligible')->sum('jumlah'),
                    'tidak_eligible' => $kelulusanByProdi->get($prodiId, collect())->where('status_kelulusan', 'noneligible')->sum('jumlah'),
                ],
            ];
        }

        return $result;
    }
    public function getStatusMahasiswa()
    {
        $baseQuery = Mahasiswa::whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');
        $baseQuery = $this->applyProdiScope($baseQuery);
        
        $totalMahasiswa = $baseQuery->count();

        $statusQuery = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');
        $statusQuery = $this->applyProdiScope($statusQuery);

        $statusBreakdown = $statusQuery->groupBy('status_mahasiswa')
            ->get()
            ->keyBy('status_mahasiswa');

        return [
            'total' => $totalMahasiswa,
            'aktif' => ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0),
            'mangkir' => ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0),
            'cuti' => ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0),
            // 'do' => ($statusBreakdown->get('do')->jumlah ?? 0) + ($statusBreakdown->get('DO')->jumlah ?? 0),
            // 'lulus' => ($statusBreakdown->get('lulus')->jumlah ?? 0) + ($statusBreakdown->get('Lulus')->jumlah ?? 0),
        ];
    }

    public function getRataIpkPerAngkatan()
    {
        $query = AkademikMahasiswa::select('tahun_masuk', DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'), DB::raw('COUNT(*) as jumlah_mahasiswa'))
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        return $this->applyProdiScope($query)
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
        $eligibleQuery = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        $eligible = $this->applyProdiScope($eligibleQuery)->count();

        $noneligibleQuery = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        $noneligible = $this->applyProdiScope($noneligibleQuery)->count();

        return [
            'total' => $eligible + $noneligible,
            'eligible' => $eligible,
            'tidak_eligible' => $noneligible,
        ];
    }

    private function getTableRingkasanMahasiswaQuery()
    {
        $query = AkademikMahasiswa::select(
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
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
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        return $this->applyProdiScope($query)
                ->groupBy('prodis.nama', 'prodis.kode_prodi', 'akademik_mahasiswa.tahun_masuk')
                ->orderBy('prodis.nama', 'asc')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');
    }

    public function getTableRingkasanMahasiswa($perPage = 10)
    {
        return $this->getTableRingkasanMahasiswaQuery()->paginate($perPage);
    }

    public function getTableRingkasanMahasiswaExport()
    {
        return $this->getTableRingkasanMahasiswaQuery()->get();
    }
}
