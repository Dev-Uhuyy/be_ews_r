<?php

namespace App\Services\Kaprodi;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KaprodiNilaiMahasiswaService
{
    /**
     * Get prodi_id milik Kaprodi yang sedang login.
     */
    private function getProdiId(): int
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Get comprehensive nilai D, E, and missing MK data for mahasiswa
     * Scoped to Kaprodi's own prodi — no prodi_id filter needed from request.
     *
     * Query params supported:
     * - tahun_masuk       : Filter berdasarkan tahun angkatan (optional)
     * - has_nilai_d       : 'true'/'false' — mahasiswa yang memiliki nilai D melebihi batas (optional)
     * - has_nilai_e       : 'true'/'false' — mahasiswa yang memiliki nilai E (optional)
     * - mk_nasional_kurang: 'true'/'false' — belum lulus MK nasional (optional)
     * - mk_fakultas_kurang: 'true'/'false' — belum lulus MK fakultas (optional)
     * - mk_prodi_kurang   : 'true'/'false' — belum lulus MK prodi (optional)
     * - status_kelulusan  : 'eligible' atau 'noneligible' (optional)
     * - mahasiswa_id      : ID mahasiswa spesifik — menonaktifkan pagination (optional)
     * - search            : Cari berdasarkan nama atau NIM (optional)
     * - per_page          : Items per halaman (default 10)
     *
     * @param array $filters
     * @param int   $perPage
     * @param string|null $search
     * @return array
     */
    public function getNilaiMahasiswaList(array $filters = [], int $perPage = 10, ?string $search = null): array
    {
        $prodiId           = $this->getProdiId();
        $isSingleMahasiswa = !empty($filters['mahasiswa_id']);

        $query = AkademikMahasiswa::query()
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
            ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)                          // ← scope Kaprodi
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

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        // Filters
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
        if (!empty($filters['mk_fakultas_kurang']) && $filters['mk_fakultas_kurang'] === 'true') {
            $query->where('akademik_mahasiswa.mk_fakultas', 'no');
        }
        if (!empty($filters['mk_prodi_kurang']) && $filters['mk_prodi_kurang'] === 'true') {
            $query->where('akademik_mahasiswa.mk_prodi', 'no');
        }
        if (!empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa.id', $filters['mahasiswa_id']);
        }

        // Mandatory MKs for enrichment (scoped to this prodi)
        $mandatoryMKsByCategory = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultas', 'prodi'])
            ->where('prodi_id', $prodiId)
            ->get()
            ->groupBy('tipe_mk');

        if ($isSingleMahasiswa) {
            $mahasiswa = $query->orderBy('mahasiswa.nim', 'asc')->limit(1)->first();
            if ($mahasiswa) {
                $mahasiswa = $this->enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory);
            }
            return [
                'data'             => $mahasiswa ? [$mahasiswa] : [],
                'total_mahasiswa'  => $mahasiswa ? 1 : 0,
                'filters_applied'  => $filters,
            ];
        }

        $totalMahasiswa = $query->count();
        $mahasiswaList  = $query->orderBy('mahasiswa.nim', 'asc')->paginate($perPage);

        $mahasiswaList->getCollection()->transform(function ($mahasiswa) use ($mandatoryMKsByCategory) {
            return $this->enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory);
        });

        return [
            'paginated_data'  => $mahasiswaList,
            'total_mahasiswa' => $totalMahasiswa,
            'filters_applied' => $filters,
        ];
    }

    /**
     * Enrich mahasiswa object with nilai D, E, and missing MK details.
     */
    private function enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory)
    {
        // Latest grade per mata kuliah
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function ($query) use ($mahasiswa) {
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

        // 1. Nilai D
        $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
        $mahasiswa->mata_kuliah_nilai_d = $matkulNilaiD->map(fn($mk) => [
            'kode'              => $mk->kode,
            'nama'              => $mk->nama,
            'sks'               => $mk->sks,
            'nilai_akhir_huruf' => $mk->nilai_akhir_huruf,
            'nilai_akhir_angka' => $mk->nilai_akhir_angka,
        ])->values()->toArray();
        $mahasiswa->jumlah_nilai_d    = $matkulNilaiD->count();
        $mahasiswa->total_sks_nilai_d = $matkulNilaiD->sum('sks');

        // 2. Nilai E
        $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
        $mahasiswa->mata_kuliah_nilai_e = $matkulNilaiE->map(fn($mk) => [
            'kode'              => $mk->kode,
            'nama'              => $mk->nama,
            'sks'               => $mk->sks,
            'nilai_akhir_huruf' => $mk->nilai_akhir_huruf,
            'nilai_akhir_angka' => $mk->nilai_akhir_angka,
        ])->values()->toArray();
        $mahasiswa->jumlah_nilai_e    = $matkulNilaiE->count();
        $mahasiswa->total_sks_nilai_e = $matkulNilaiE->sum('sks');

        // 3. MK Nasional kurang
        if ($mahasiswa->mk_nasional === 'no') {
            $nasionalMandatory = $mandatoryMKsByCategory->get('nasional') ?? collect();
            $missingNasional   = [];
            foreach ($nasionalMandatory as $mk) {
                $grade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$grade || $grade->nilai_akhir_huruf === 'E') {
                    $missingNasional[] = ['kode' => $mk->kode, 'nama' => $mk->name, 'sks' => $mk->sks];
                }
            }
            $mahasiswa->mk_nasional_kurang        = $missingNasional;
            $mahasiswa->jumlah_mk_nasional_kurang = count($missingNasional);
        } else {
            $mahasiswa->mk_nasional_kurang        = [];
            $mahasiswa->jumlah_mk_nasional_kurang = 0;
        }

        // 4. MK Fakultas kurang
        if ($mahasiswa->mk_fakultas === 'no') {
            $fakultasMandatory = $mandatoryMKsByCategory->get('fakultas') ?? collect();
            $missingFakultas   = [];
            foreach ($fakultasMandatory as $mk) {
                $grade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$grade || $grade->nilai_akhir_huruf === 'E') {
                    $missingFakultas[] = ['kode' => $mk->kode, 'nama' => $mk->name, 'sks' => $mk->sks];
                }
            }
            $mahasiswa->mk_fakultas_kurang        = $missingFakultas;
            $mahasiswa->jumlah_mk_fakultas_kurang = count($missingFakultas);
        } else {
            $mahasiswa->mk_fakultas_kurang        = [];
            $mahasiswa->jumlah_mk_fakultas_kurang = 0;
        }

        // 5. MK Prodi kurang
        if ($mahasiswa->mk_prodi === 'no') {
            $prodiMandatory = $mandatoryMKsByCategory->get('prodi') ?? collect();
            $missingProdi   = [];
            foreach ($prodiMandatory as $mk) {
                $grade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$grade || $grade->nilai_akhir_huruf === 'E') {
                    $missingProdi[] = ['kode' => $mk->kode, 'nama' => $mk->name, 'sks' => $mk->sks];
                }
            }
            $mahasiswa->mk_prodi_kurang        = $missingProdi;
            $mahasiswa->jumlah_mk_prodi_kurang = count($missingProdi);
        } else {
            $mahasiswa->mk_prodi_kurang        = [];
            $mahasiswa->jumlah_mk_prodi_kurang = 0;
        }

        $mahasiswa->total_sks_tidak_lulus = $mahasiswa->total_sks_nilai_d + $mahasiswa->total_sks_nilai_e;

        return $mahasiswa;
    }
}
