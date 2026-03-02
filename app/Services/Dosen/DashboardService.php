<?php

namespace App\Services\Dosen;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Dosen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    protected $dosenId;

    public function __construct()
    {
        // Get dosen_id from authenticated user
        $user = Auth::user();
        if ($user) {
            $dosen = Dosen::where('user_id', $user->id)->first();
            $this->dosenId = $dosen ? $dosen->id : null;
        }
    }

    public function getStatusMahasiswa()
    {
        // Exclude mahasiswa yang sudah lulus dan DO dari total
        // Hanya mahasiswa yang di-wali-kan oleh dosen ini
        $totalMahasiswa = Mahasiswa::join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->join('akademik_mahasiswa', 'mahasiswa.id', '=', 'akademik_mahasiswa.mahasiswa_id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->groupBy('status_mahasiswa')
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
        return AkademikMahasiswa::select('tahun_masuk', DB::raw('AVG(ipk) as rata_ipk'), DB::raw('COUNT(*) as jumlah_mahasiswa'))
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    public function getStatusKelulusan()
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return [
            'total' => $eligible + $noneligible,
            'eligible' => $eligible,
            'tidak_eligible' => $noneligible,
        ];
    }

    public function getTableRingkasanMahasiswa($perPage = 10)
    {
        $tahunMasukQuery = AkademikMahasiswa::select(
                'tahun_masuk',
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as total_mahasiswa')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc');

        // Get paginated tahun_masuk
        $paginatedTahunMasuk = $tahunMasukQuery->paginate($perPage);

        // Get detailed stats for each tahun_masuk in current page
        $tahunMasukList = $paginatedTahunMasuk->pluck('tahun_masuk');

        $result = [];

        foreach ($tahunMasukList as $tahunMasuk) {
            $totalMahasiswa = AkademikMahasiswa::where('tahun_masuk', $tahunMasuk)
                ->where('dosen_wali_id', $this->dosenId)
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->count();

            $statusEwsData = EarlyWarningSystem::select('status', DB::raw('COUNT(*) as jumlah'))
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->where('akademik_mahasiswa.dosen_wali_id', $this->dosenId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $result[] = [
                'tahun_masuk' => $tahunMasuk,
                'total_mahasiswa' => $totalMahasiswa,
                'tepat_waktu' => $statusEwsData->get('tepat_waktu')->jumlah ?? 0,
                'normal' => $statusEwsData->get('normal')->jumlah ?? 0,
                'perhatian' => $statusEwsData->get('perhatian')->jumlah ?? 0,
                'kritis' => $statusEwsData->get('kritis')->jumlah ?? 0,
            ];
        }

        // Replace items with detailed result
        $paginatedTahunMasuk->setCollection(collect($result));

        return $paginatedTahunMasuk;
    }
}
