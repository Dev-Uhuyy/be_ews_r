<?php

namespace App\Services\Kaprodi;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class KaprodiDashboardService
{
    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    public function getDashboard()
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);

        $statistikGlobal = $this->getStatistikGlobal($prodiId);
        $rataIpkPerTahun = $this->getRataIpkPerTahun($prodiId);
        $statistikKelulusan = $this->getStatistikKelulusan($prodiId);
        $tabelRingkasanProdi = $this->getTabelRingkasanProdi($prodiId);

        return [
            'statistik_global' => $statistikGlobal,
            'rata_ipk_per_tahun' => $rataIpkPerTahun,
            'statistik_kelulusan' => $statistikKelulusan,
            'tabel_ringkasan_prodi' => $tabelRingkasanProdi,
        ];
    }

    private function getStatistikGlobal($prodiId)
    {
        $query = Mahasiswa::where('prodi_id', $prodiId)
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');

        $totalMahasiswa = (clone $query)->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->where('prodi_id', $prodiId)
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('status_mahasiswa')
            ->get()
            ->keyBy('status_mahasiswa');

        $totalAktif = ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0);
        $totalMangkir = ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0);
        $totalCuti = ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0);
        $totalDO = Mahasiswa::where('prodi_id', $prodiId)
            ->whereRaw('LOWER(status_mahasiswa) = "do"')
            ->count();

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_mahasiswa_aktif' => $totalAktif,
            'total_mahasiswa_mangkir' => $totalMangkir,
            'total_mahasiswa_cuti' => $totalCuti,
            'total_mahasiswa_do' => $totalDO,
        ];
    }

    private function getRataIpkPerTahun($prodiId)
    {
        return AkademikMahasiswa::select(
                'tahun_masuk',
                DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'),
                DB::raw('COUNT(*) as jumlah_mahasiswa')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    private function getStatistikKelulusan($prodiId)
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return [
            'eligible' => $eligible,
            'non_eligible' => $noneligible,
        ];
    }

    private function getTabelRingkasanProdi($prodiId)
    {
        $prodi = Prodi::find($prodiId);

        $stats = AkademikMahasiswa::select(
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as jumlah_mahasiswa_cuti'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mahasiswa_mangkir'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as jumlah_perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as jumlah_kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->first();

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
            'jumlah_perhatian' => $stats->jumlah_perhatian ?? 0,
            'jumlah_kritis' => $stats->jumlah_kritis ?? 0,
        ];
    }

    public function getDetailDashboard()
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);

        $tahunData = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN mahasiswa.cuti_2 = "yes" THEN 1 ELSE 0 END) as jumlah_cuti_2x'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();

        return [
            'prodi' => [
                'id' => $prodi->id,
                'kode_prodi' => $prodi->kode_prodi,
                'nama_prodi' => $prodi->nama,
            ],
            'tahun_angkatan' => $tahunData,
        ];
    }

    public function getMahasiswaListByCriteria($tahunMasuk = null, $criteria = null)
    {
        $prodiId = $this->getProdiId();

        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'mahasiswa.status_mahasiswa',
                    'early_warning_system.status as ews_status'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        if ($criteria) {
            switch ($criteria) {
                case 'aktif':
                    $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "aktif"');
                    break;
                case 'cuti_2x':
                    $query->where('mahasiswa.cuti_2', 'yes');
                    break;
                case 'tepat_waktu':
                    $query->where('early_warning_system.status', 'tepat_waktu');
                    break;
                case 'perhatian':
                    $query->where('early_warning_system.status', 'perhatian');
                    break;
                case 'kritis':
                    $query->where('early_warning_system.status', 'kritis');
                    break;
            }
        }

        $mahasiswas = $query->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        $grouped = $mahasiswas->groupBy('tahun_masuk')->map(function ($items, $tahun) {
            return [
                'tahun_masuk' => $tahun,
                'jumlah' => $items->count(),
                'mahasiswa' => $items->map(function ($mhs) {
                    return [
                        'mahasiswa_id' => $mhs->mahasiswa_id,
                        'nim' => $mhs->nim,
                        'nama_mahasiswa' => $mhs->nama_mahasiswa,
                        'sks_total' => $mhs->sks_lulus ?? 0,
                        'ipk' => $mhs->ipk ?? 0,
                        'status_mahasiswa' => $mhs->status_mahasiswa,
                        'ews_status' => $mhs->ews_status,
                    ];
                })->values()
            ];
        })->values();

        return $grouped;
    }
}
