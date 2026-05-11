<?php

declare(strict_types=1);

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DekanDashboardService
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
        $query = Mahasiswa::whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');

        $totalMahasiswa = (clone $query)->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('status_mahasiswa')
            ->get()
            ->keyBy('status_mahasiswa');

        $totalAktif = ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0);
        $totalMangkir = ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0);
        $totalCuti = ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0);
        $totalDO = (clone $query)->whereRaw('LOWER(status_mahasiswa) = "do"')->count();

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_mahasiswa_aktif' => $totalAktif,
            'total_mahasiswa_mangkir' => $totalMangkir,
            'total_mahasiswa_cuti' => $totalCuti,
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
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
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
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return [
            'eligible' => $eligible,
            'non_eligible' => $noneligible,
        ];
    }

    private function getTabelRingkasanProdi($prodis): array
    {
        $result = [];
        foreach ($prodis as $prodi) {
            $result[] = $this->getRingkasanPerProdi($prodi);
        }

        return $result;
    }

    private function getRingkasanPerProdi(Prodi $prodi): array
    {
        $query = AkademikMahasiswa::select(
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as jumlah_mahasiswa_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mahasiswa_mangkir'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as jumlah_normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as jumlah_perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as jumlah_kritis')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodi->id)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        $stats = $query->first();

        return [
            'prodi' => [
                'id' => $prodi->id,
                'kode_prodi' => $prodi->kode_prodi,
                'nama_prodi' => $prodi->nama,
            ],
            'jumlah_mahasiswa' => $stats->jumlah_mahasiswa ?? 0,
            'jumlah_mahasiswa_aktif' => $stats->jumlah_mahasiswa_aktif ?? 0,
            'jumlah_mahasiswa_cuti' => $stats->jumlah_mahasiswa_cuti ?? 0,
            'jumlah_mahasiswa_mangkir' => $stats->jumlah_mahasiswa_mangkir ?? 0,
            'ipk_rata_rata' => $stats->ipk_rata_rata ?? 0,
            'jumlah_tepat_waktu' => $stats->jumlah_tepat_waktu ?? 0,
            'jumlah_normal' => $stats->jumlah_normal ?? 0,
            'jumlah_perhatian' => $stats->jumlah_perhatian ?? 0,
            'jumlah_kritis' => $stats->jumlah_kritis ?? 0,
        ];
    }
}
