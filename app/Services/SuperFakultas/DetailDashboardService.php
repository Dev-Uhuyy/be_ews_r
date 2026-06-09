<?php

namespace App\Services\SuperFakultas;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;

class DetailDashboardService
{
    /**
     * Get detail dashboard - data per prodi dan tahun angkatan
     */
    public function getDetailDashboard($prodiId = null)
    {
        $prodisQuery = $prodiId
            ? Prodi::where('id', $prodiId)
            : Prodi::query();

        $prodis = $prodisQuery->get();
        $prodiIds = $prodis->pluck('id')->toArray();

        $tahunData = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as jumlah_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "tidak_aktif" THEN 1 ELSE 0 END) as jumlah_tidak_aktif'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->groupBy('prodi_id');

        $doPerProdi = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(*) as jumlah_do')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "do"')
            ->groupBy('mahasiswa.prodi_id', 'akademik_mahasiswa.tahun_masuk')
            ->get()
            ->groupBy('prodi_id');

        $result = [];
        foreach ($prodis as $prodi) {
            $prodiTahunData = $tahunData->get($prodi->id, collect());
            $prodiDoData = $doPerProdi->get($prodi->id, collect())->keyBy('tahun_masuk');

            $result[] = [
                'prodi' => [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ],
                'tahun_angkatan' => $prodiTahunData->map(function ($item) use ($prodiDoData) {
                    return [
                        'tahun_masuk' => $item->tahun_masuk,
                        'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                        'mahasiswa_aktif' => $item->mahasiswa_aktif,
                        'jumlah_cuti' => $item->jumlah_cuti,
                        'jumlah_mangkir' => $item->jumlah_mangkir,
                        'jumlah_do' => $prodiDoData->get($item->tahun_masuk)->jumlah_do ?? 0,
                        'jumlah_tidak_aktif' => $item->jumlah_tidak_aktif,
                        'ipk_rata_rata' => $item->ipk_rata_rata,
                        'tepat_waktu' => $item->tepat_waktu,
                        'normal' => $item->normal,
                        'perhatian' => $item->perhatian,
                        'kritis' => $item->kritis,
                        'eligible' => $item->eligible ?? 0,
                        'tidak_eligible' => $item->tidak_eligible ?? 0,
                    ];
                })->values()->toArray(),
            ];
        }

        return $result;
    }

    /**
     * Get list mahasiswa dengan kriteria spesifik per prodi dan tahun
     *
     * Filter options:
     * - prodi_id (required)
     * - tahun_masuk (optional)
     * - criteria: 'aktif', 'cuti_2x', 'tepat_waktu', 'perhatian', 'kritis'
     */
    public function getMahasiswaListByCriteria($prodiId, $tahunMasuk = null, $criteria = null)
    {
        $query = AkademikMahasiswa::select(
            'mahasiswa.id as mahasiswa_id',
            'mahasiswa.nim',
            'users.name as nama_mahasiswa',
            'akademik_mahasiswa.tahun_masuk',
            'akademik_mahasiswa.sks_lulus',
            'akademik_mahasiswa.ipk',
            'mahasiswa.status_mahasiswa',
            'early_warning_system.status as ews_status',
            'early_warning_system.status_kelulusan'
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId);

        if ($criteria === 'do') {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "do"');
        } else {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        }

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        // Apply criteria filter
        if ($criteria) {
            switch ($criteria) {
                case 'aktif':
                    $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "aktif"');
                    break;
                case 'cuti':
                    $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "cuti"');
                    break;
                case 'mangkir':
                    $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "mangkir"');
                    break;
                case 'do':
                    break;
                case 'cuti_2x':
                    $query->where('mahasiswa.cuti_2', 'yes');
                    break;
                case 'tepat_waktu':
                    $query->where('early_warning_system.status', 'tepat_waktu');
                    break;
                case 'normal':
                    $query->where('early_warning_system.status', 'normal');
                    break;
                case 'perhatian':
                    $query->where('early_warning_system.status', 'perhatian');
                    break;
                case 'kritis':
                    $query->where('early_warning_system.status', 'kritis');
                    break;
                case 'eligible':
                    $query->where('early_warning_system.status_kelulusan', 'eligible');
                    break;
                case 'noneligible':
                    $query->where('early_warning_system.status_kelulusan', 'noneligible');
                    break;
            }
        }

        $mahasiswas = $query->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        // Group by tahun_masuk
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
                        'status_kelulusan' => $mhs->status_kelulusan,
                    ];
                })->values(),
            ];
        })->values();

        return $grouped;
    }
}
