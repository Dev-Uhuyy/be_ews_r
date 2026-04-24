<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NilaiMahasiswaService
{
    /**
     * Get comprehensive nilai D, E, and missing MK data for mahasiswa
     * With filtering support by prodi_id, tahun_masuk, and other criteria
     * When mahasiswa_id is provided, returns single student data without pagination
     *
     * @param array $filters Filter parameters: prodi_id, tahun_masuk, has_nilai_d, has_nilai_e, mahasiswa_id, etc.
     * @param int $perPage Number of items per page
     * @param string $search Search by name or NIM
     * @return array
     */
    public function getNilaiMahasiswaList($filters = [], $perPage = 10, $search = null)
    {
        $isSingleMahasiswa = !empty($filters['mahasiswa_id']);
        $query = AkademikMahasiswa::query()
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
            ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                'prodis.nama as nama_prodi',
                'prodis.id as prodi_id',
                'mahasiswa.id as mahasiswa_id',
                'mahasiswa.nim',
                'users.name as nama_lengkap',
                DB::raw("CONCAT(COALESCE(CONCAT(dosen.gelar_depan, ' '), ''), dosen_users.name, COALESCE(CONCAT(' ', dosen.gelar_belakang), '')) as nama_dosen_wali"),
                'akademik_mahasiswa.semester_aktif',
                'akademik_mahasiswa.tahun_masuk',
                'akademik_mahasiswa.ipk',
                'akademik_mahasiswa.sks_lulus',
                'akademik_mahasiswa.mk_nasional',
                'akademik_mahasiswa.mk_fakultas',
                'akademik_mahasiswa.mk_prodi',
                'akademik_mahasiswa.nilai_d_melebihi_batas',
                'akademik_mahasiswa.nilai_e',
                'early_warning_system.status as status_ews',
                'early_warning_system.status_kelulusan'
            );

        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Apply filters
        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }
        if (!empty($filters['has_nilai_d']) && $filters['has_nilai_d'] === 'true') {
            $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', 'yes');
        }
        if (!empty($filters['has_nilai_e']) && $filters['has_nilai_e'] === 'true') {
            $query->where('akademik_mahasiswa.nilai_e', 'yes');
        }
        if (!empty($filters['status_kelulusan'])) {
            $query->where('early_warning_system.status_kelulusan', $filters['status_kelulusan']);
        }
        if (!empty($filters['mk_nasional_kurang']) && $filters['mk_nasional_kurang'] === 'true') {
            $query->where('akademik_mahasiswa.mk_nasional', 'no');
        }
        if (!empty($filters['mk_fakultason_kurang']) && $filters['mk_fakultason_kurang'] === 'true') {
            $query->where('akademik_mahasiswa.mk_fakultason', 'no');
        }
        if (!empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa.id', $filters['mahasiswa_id']);
        }

        // Get total count
        if ($isSingleMahasiswa) {
            $totalMahasiswa = 1;
        } else {
            $totalMahasiswa = $query->count();
        }

        // Paginate
        if ($isSingleMahasiswa) {
            $mahasiswaList = $query->orderBy('mahasiswa.nim', 'asc')->limit(1)->get();
        } else {
            $mahasiswaList = $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);
        }

        // Get mandatory MKs for enrichment
        $mandatoryQuery = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultason']);

        if (!empty($filters['prodi_id'])) {
            $mandatoryQuery->where('prodi_id', $filters['prodi_id']);
        }

        $mandatoryMKsByCategory = $mandatoryQuery->get()->groupBy('tipe_mk');

        // Enrich each mahasiswa with nilai D, E, and missing MK details
        if ($isSingleMahasiswa) {
            $mahasiswa = $mahasiswaList->first();
            if ($mahasiswa) {
                $mahasiswa = $this->enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory);
            }
            return [
                'data' => $mahasiswa ? [$mahasiswa] : [],
                'total_mahasiswa' => 1,
                'filters_applied' => $filters,
            ];
        } else {
            $mahasiswaList->getCollection()->transform(function ($mahasiswa) use ($mandatoryMKsByCategory) {
                return $this->enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory);
            });

            return [
                'paginated_data' => $mahasiswaList,
                'total_mahasiswa' => $totalMahasiswa,
                'filters_applied' => $filters,
            ];
        }
    }

    /**
     * Enrich mahasiswa object with nilai D, E, and missing MK details
     *
     * @param mixed $mahasiswa
     * @param \Illuminate\Support\Collection $mandatoryMKsByCategory
     * @return mixed
     */
    private function enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory)
    {
        // Get latest grades for this student (latest per mata kuliah)
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function($query) use ($mahasiswa) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->where('khs2.mahasiswa_id', $mahasiswa->mahasiswa_id)
                    ->groupBy('khs2.matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->select(
                'mata_kuliahs.id as matakuliah_id',
                'mata_kuliahs.kode',
                'mata_kuliahs.name as nama',
                'mata_kuliahs.sks',
                'mata_kuliahs.tipe_mk',
                'khs1.nilai_akhir_huruf',
                'khs1.nilai_akhir_angka'
            )
            ->get();

        // 1. Mata Kuliah Nilai D
        $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
        $mahasiswa->mata_kuliah_nilai_d = $matkulNilaiD->map(function ($mk) {
            return [
                'kode' => $mk->kode,
                'nama' => $mk->nama,
                'sks' => $mk->sks,
                'nilai_akhir_huruf' => $mk->nilai_akhir_huruf,
                'nilai_akhir_angka' => $mk->nilai_akhir_angka,
            ];
        })->values()->toArray();
        $mahasiswa->jumlah_nilai_d = $matkulNilaiD->count();
        $mahasiswa->total_sks_nilai_d = $matkulNilaiD->sum('sks');

        // 2. Mata Kuliah Nilai E
        $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
        $mahasiswa->mata_kuliah_nilai_e = $matkulNilaiE->map(function ($mk) {
            return [
                'kode' => $mk->kode,
                'nama' => $mk->nama,
                'sks' => $mk->sks,
                'nilai_akhir_huruf' => $mk->nilai_akhir_huruf,
                'nilai_akhir_angka' => $mk->nilai_akhir_angka,
            ];
        })->values()->toArray();
        $mahasiswa->jumlah_nilai_e = $matkulNilaiE->count();
        $mahasiswa->total_sks_nilai_e = $matkulNilaiE->sum('sks');

        // 3. MK Nasional Kurang (belum lulus)
        if ($mahasiswa->mk_nasional === 'no') {
            $nasionalMandatory = $mandatoryMKsByCategory->get('nasional') ?? collect();
            $missingNasional = [];
            foreach ($nasionalMandatory as $mk) {
                $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                    $missingNasional[] = [
                        'kode' => $mk->kode,
                        'nama' => $mk->name,
                        'sks' => $mk->sks,
                    ];
                }
            }
            $mahasiswa->mk_nasional_kurang = $missingNasional;
            $mahasiswa->jumlah_mk_nasional_kurang = count($missingNasional);
        } else {
            $mahasiswa->mk_nasional_kurang = [];
            $mahasiswa->jumlah_mk_nasional_kurang = 0;
        }

        // 4. MK Fakultason Kurang (belum lulus)
        if ($mahasiswa->mk_fakultason === 'no') {
            $fakultasonMandatory = $mandatoryMKsByCategory->get('fakultason') ?? collect();
            $missingFakultason = [];
            foreach ($fakultasonMandatory as $mk) {
                $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                    $missingFakultason[] = [
                        'kode' => $mk->kode,
                        'nama' => $mk->name,
                        'sks' => $mk->sks,
                    ];
                }
            }
            $mahasiswa->mk_fakultason_kurang = $missingFakultason;
            $mahasiswa->jumlah_mk_fakultason_kurang = count($missingFakultason);
        } else {
            $mahasiswa->mk_fakultason_kurang = [];
            $mahasiswa->jumlah_mk_fakultason_kurang = 0;
        }

        // Additional summary fields
        $mahasiswa->total_sks_tidak_lulus = $mahasiswa->total_sks_nilai_d + $mahasiswa->total_sks_nilai_e;

        return $mahasiswa;
    }

    /**
     * Get summary statistics for nilai D, E, and missing MK across all filtered mahasiswa
     *
     * @param array $filters
     * @return array
     */
    public function getNilaiMahasiswaSummary($filters = [])
    {
        $query = AkademikMahasiswa::query()
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as total_mahasiswa'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_d'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_e'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_nasional = "no" THEN 1 ELSE 0 END) as mk_nasional_belum_lulus'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_fakultason = "no" THEN 1 ELSE 0 END) as mk_fakultason_belum_lulus')
            );

        // Apply filters
        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }

        return $query->first();
    }
}