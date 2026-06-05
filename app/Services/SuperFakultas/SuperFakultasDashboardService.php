<?php

declare(strict_types=1);

namespace App\Services\SuperFakultas;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SuperFakultasDashboardService
{
    public function getDashboard(): array
    {
        $prodis = Prodi::all();

        return [
            'statistik_global' => $this->getStatistikGlobal(),
            'rata_ipk_per_tahun' => $this->getRataIpkPerTahun(),
            'statistik_kelulusan' => $this->getStatistikKelulusan(),
            'tabel_ringkasan_prodi' => $this->getTabelRingkasanProdi($prodis),
        ];
    }

    private function getStatistikGlobal(): array
    {
        $stats = Mahasiswa::select(
            DB::raw('COUNT(*) as total_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as total_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as total_mangkir'),
            DB::raw('SUM(CASE WHEN LOWER(status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as total_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(status_mahasiswa) = "do" THEN 1 ELSE 0 END) as total_do')
        )
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->first();

        $totalTidakAktif = Mahasiswa::whereRaw('LOWER(status_mahasiswa) = "tidak_aktif"')->count();
        $totalDO = Mahasiswa::whereRaw('LOWER(status_mahasiswa) = "do"')->count();

        return [
            'total_mahasiswa' => $stats->total_mahasiswa ?? 0,
            'total_mahasiswa_aktif' => ($stats->total_aktif ?? 0),
            'total_mahasiswa_mangkir' => ($stats->total_mangkir ?? 0),
            'total_mahasiswa_cuti' => ($stats->total_cuti ?? 0),
            'total_mahasiswa_tidak_aktif' => $totalTidakAktif,
            'total_mahasiswa_do' => $totalDO,
        ];
    }

    private function getRataIpkPerTahun(): Collection
    {
        return AkademikMahasiswa::select(
            'tahun_masuk',
            DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'),
            DB::raw('COUNT(*) as jumlah_mahasiswa')
        )
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    private function getStatistikKelulusan(): array
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->count();

        return [
            'eligible' => $eligible,
            'non_eligible' => $noneligible,
        ];
    }

    private function getTabelRingkasanProdi($prodis): array
    {
        $prodiIds = $prodis->pluck('id')->toArray();

        $nonDoStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as jumlah_mahasiswa_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mahasiswa_mangkir'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as jumlah_normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as jumlah_perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as jumlah_kritis'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $doStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(*) as jumlah_do')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "do"')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $tidakAktifStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(*) as jumlah_mahasiswa_tidak_aktif')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "tidak_aktif"')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $result = [];
        foreach ($prodis as $prodi) {
            $statsNonDo = $nonDoStats->get($prodi->id);
            $statsDo = $doStats->get($prodi->id);
            $statsTidakAktif = $tidakAktifStats->get($prodi->id);

            $result[] = [
                'prodi' => [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ],
                'jumlah_mahasiswa' => $statsNonDo->jumlah_mahasiswa ?? 0,
                'jumlah_mahasiswa_aktif' => $statsNonDo->jumlah_mahasiswa_aktif ?? 0,
                'jumlah_mahasiswa_cuti' => $statsNonDo->jumlah_mahasiswa_cuti ?? 0,
                'jumlah_mahasiswa_mangkir' => $statsNonDo->jumlah_mahasiswa_mangkir ?? 0,
                'jumlah_do' => $statsDo->jumlah_do ?? 0,
                'jumlah_mahasiswa_tidak_aktif' => $statsTidakAktif->jumlah_mahasiswa_tidak_aktif ?? 0,
                'ipk_rata_rata' => $statsNonDo->ipk_rata_rata ?? 0,
                'jumlah_tepat_waktu' => $statsNonDo->jumlah_tepat_waktu ?? 0,
                'jumlah_normal' => $statsNonDo->jumlah_normal ?? 0,
                'jumlah_perhatian' => $statsNonDo->jumlah_perhatian ?? 0,
                'jumlah_kritis' => $statsNonDo->jumlah_kritis ?? 0,
                'eligible' => $statsNonDo->eligible ?? 0,
                'tidak_eligible' => $statsNonDo->tidak_eligible ?? 0,
            ];
        }

        return $result;
    }
}
