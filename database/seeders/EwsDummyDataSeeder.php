<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Admin\EwsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * EwsDummyDataSeeder (REWRITE 2026-06-04 — Refactor 12)
 *
 * Generate ~4,560 mahasiswa (76 per tahun × 6 tahun × 10 prodi) dengan data
 * realistis. Optimasi: bulk insert untuk KHS chunks 100.
 *
 * Kriteria data:
 * - sks_lulus derived from KHS (sum SKS MK dengan latest KHS nilai NOT IN 'D','E')
 * - mk_nasional/fakultas/prodi derived from KHS completeness
 * - IPK = rata-rata IPS dari semester yang sudah ditempuh
 * - Status distribusi: aktif, cuti, mangkir, tidak_aktif, lulus, DO (uppercase)
 * - cuti_2='yes' 30% untuk status='cuti'
 * - KHS 'U' (Ulang) 10% dari total
 * - Retake scenario: 0.5% chance per (prodi,tahun)
 */
class EwsDummyDataSeeder extends Seeder
{
    private const BULK_INSERT_CHUNK = 100;
    private const TARGET_PER_TAHUN = 75;

    public function run(): void
    {
        $prodis = Prodi::all();
        $ewsService = app(EwsService::class);

        $tahunMasukList = [2020, 2021, 2022, 2023, 2024, 2025];
        $mahasiswaRole = Role::firstOrCreate(['name' => 'mahasiswa']);

        foreach ($prodis as $prodi) {
            $this->command->info("Seeding Mahasiswa for Prodi: {$prodi->kode_prodi} - {$prodi->nama}");

            $dosenId = Dosen::where('prodi_id', $prodi->id)->first()?->id;

            if (! $dosenId) {
                $this->command->warn("  ⚠ Dosen untuk prodi {$prodi->kode_prodi} kosong. Melewati seeding prodi ini.");

                continue;
            }

            // Cache MK per prodi dikelompokkan per tipe+semester
            $mkByTipeSemester = $this->cacheMkByTipeSemester($prodi->id);
            $allMks = collect();
            foreach ($mkByTipeSemester as $bySem) {
                foreach ($bySem as $arr) {
                    foreach ($arr as $mk) {
                        $allMks->push($mk);
                    }
                }
            }

            if ($allMks->isEmpty()) {
                $this->command->warn("  ⚠ Tidak ada MK untuk prodi {$prodi->kode_prodi}. Skip.");

                continue;
            }

            // Pre-load Kelompok per MK agar bulk insert KHS bisa dapat kelompok_id valid
            $kelompokByMk = $this->cacheKelompokByMk($prodi->id);

            foreach ($tahunMasukList as $tahun) {
                $this->command->info("  - Tahun Masuk: {$tahun}");

                $currentYear = (int) date('Y');
                $diffYear = max(0, $currentYear - $tahun);
                $baseSemesterAktif = ($diffYear * 2) + 1;

                DB::beginTransaction();
                try {
                    $khsBatch = [];
                    $mhsBatchData = [];   // user + mahasiswa + akademik + ips
                    $akademikUpdates = []; // update IPK after IPS created
                    $ewsServiceCalls = []; // akademik IDs to call EWS update

                    for ($i = 100; $i <= (100 + self::TARGET_PER_TAHUN); $i++) {
                        $nim = $prodi->kode_prodi.'.'.$tahun.'.'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $email = "{$nim}@ews.com";

                        $semesterAktif = max(1, $baseSemesterAktif + rand(-1, 1));
                        $statusMahasiswa = $this->getRandomStatus($baseSemesterAktif);
                        $cuti2 = ($statusMahasiswa === 'cuti' && rand(1, 100) <= 30) ? 'yes' : 'no';

                        $sksLulusEst = rand(20, 150);
                        if ($statusMahasiswa == 'lulus') {
                            $sksLulusEst = max(144, $sksLulusEst);
                        }

                        // ─── 1. User (lookup by email, create kalau belum ada) ───
                        $user = User::firstOrCreate(
                            ['email' => $email],
                            [
                                'name' => "MHS {$prodi->kode_prodi} {$tahun} {$i}",
                                'password' => Hash::make('password'),
                                'prodi_id' => $prodi->id,
                            ]
                        );
                        if (! $user->hasRole('mahasiswa')) {
                            $user->assignRole($mahasiswaRole);
                        }

                        // ─── 2. Mahasiswa ───
                        $mahasiswa = Mahasiswa::updateOrCreate(
                            ['user_id' => $user->id],
                            [
                                'nim' => $nim,
                                'prodi_id' => $prodi->id,
                                'status_mahasiswa' => $statusMahasiswa,
                                'cuti_2' => $cuti2,
                            ]
                        );

                        // ─── 3. KHS rows (in-memory, batched insert) ───
                        $nilaiOptions = ['A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'D', 'E'];
                        $mkSmt1Plus = $allMks->where('semester', '<=', $semesterAktif)->values();
                        if ($mkSmt1Plus->isEmpty()) {
                            continue;
                        }

                        $takeAmount = min($mkSmt1Plus->count(), rand(2, 3));
                        $khsRows = []; // untuk sks_lulus calculation nanti
                        foreach (range(1, $semesterAktif) as $smt) {
                            $candidatesForSmt = $allMks->where('semester', $smt)->values();
                            if ($candidatesForSmt->isEmpty()) {
                                continue;
                            }
                            $take = min($candidatesForSmt->count(), rand(1, 2));
                            // Pakai shuffle()->take() supaya selalu Collection of models.
                            // Collection::random(1) return single model; cast ke array
                            // malah jadi property array → $mk->id gagal.
                            $picks = $candidatesForSmt->shuffle()->take($take);
                            foreach ($picks as $mk) {
                                $nilai = $nilaiOptions[array_rand($nilaiOptions)];
                                $statusKhs = rand(1, 100) <= 10 ? 'U' : 'B';
                                $kelompok = $kelompokByMk[$mk->id] ?? null;
                                $kelompokId = $kelompok ? $kelompok[array_rand($kelompok)] : null;

                                if (! $kelompokId) {
                                    continue; // skip MK tanpa kelompok
                                }

                                $now = Carbon::now();
                                $khsRows[] = [
                                    'mahasiswa_id' => $mahasiswa->id,
                                    'matakuliah_id' => $mk->id,
                                    'kelompok_id' => $kelompokId,
                                    'semester_ambil' => $smt,
                                    'status' => $statusKhs,
                                    'absen' => rand(70, 100),
                                    'nilai_uts' => rand(60, 90),
                                    'nilai_uas' => rand(60, 90),
                                    'nilai_akhir_angka' => $this->nilaiHurufToAngka($nilai),
                                    'nilai_akhir_huruf' => $nilai,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                                $khsBatch[] = end($khsRows);

                                if (count($khsBatch) >= self::BULK_INSERT_CHUNK) {
                                    KhsKrsMahasiswa::insert($khsBatch);
                                    $khsBatch = [];
                                }
                            }
                        }

                        // Retake scenario: 0.5% chance — pick first KHS, insert older D + newer B
                        if (rand(1, 200) <= 1 && ! empty($khsRows)) {
                            $firstRow = $khsRows[0];
                            $now = Carbon::now();

                            // Older D
                            $khsRows[] = [
                                'mahasiswa_id' => $firstRow['mahasiswa_id'],
                                'matakuliah_id' => $firstRow['matakuliah_id'],
                                'kelompok_id' => $firstRow['kelompok_id'],
                                'semester_ambil' => $firstRow['semester_ambil'],
                                'status' => 'B',
                                'absen' => 80,
                                'nilai_uts' => 50,
                                'nilai_uas' => 40,
                                'nilai_akhir_angka' => 1,
                                'nilai_akhir_huruf' => 'D',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $khsBatch[] = end($khsRows);
                            // Newer B (retake)
                            $khsRows[] = [
                                'mahasiswa_id' => $firstRow['mahasiswa_id'],
                                'matakuliah_id' => $firstRow['matakuliah_id'],
                                'kelompok_id' => $firstRow['kelompok_id'],
                                'semester_ambil' => $firstRow['semester_ambil'] + 1,
                                'status' => 'U',
                                'absen' => 95,
                                'nilai_uts' => 80,
                                'nilai_uas' => 85,
                                'nilai_akhir_angka' => 3,
                                'nilai_akhir_huruf' => 'B',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $khsBatch[] = end($khsRows);
                        }

                        // ─── 4. Hitung sks_lulus, mk_* dari KHS ───
                        $sksLulus = $this->calculateSksLulus($khsRows);
                        $mkNasional = $this->isKategoriSelesai($mahasiswa->id, 'nasional', $mkByTipeSemester) ? 'yes' : 'no';
                        $mkFakultas = $this->isKategoriSelesai($mahasiswa->id, 'fakultas', $mkByTipeSemester) ? 'yes' : 'no';
                        $mkProdi = $this->isKategoriSelesai($mahasiswa->id, 'prodi', $mkByTipeSemester) ? 'yes' : 'no';

                        // ─── 5. IPS (1..smt) ───
                        $ipsData = [
                            'mahasiswa_id' => $mahasiswa->id,
                        ];
                        for ($n = 1; $n <= 14; $n++) {
                            $ipsData["ips_{$n}"] = $n <= $semesterAktif
                                ? round(rand(150, 390) / 100, 2)
                                : null;
                        }
                        IpsMahasiswa::updateOrCreate(['mahasiswa_id' => $mahasiswa->id], $ipsData);

                        // IPK = rata-rata IPS semester yang sudah ditempuh
                        // HATI-HATI: 'mahasiswa_id' key adalah integer (FK), kalau di-include
                        // ke sum akan jadi nilai IPK sangat besar (e.g. 163+3.5 = 166.5).
                        // Filter HANYA key 'ips_X', exclude 'mahasiswa_id'.
                        $activeIps = [];
                        for ($n = 1; $n <= 14; $n++) {
                            $v = $ipsData["ips_{$n}"];
                            if ($v !== null && is_numeric($v)) {
                                $activeIps[] = (float) $v;
                            }
                        }
                        $calculatedIpk = count($activeIps) > 0
                            ? round(array_sum($activeIps) / count($activeIps), 2)
                            : null;

                        // Clamp IPK ke range realistis 0-4 (decimal(3,2) max 9.99 tapi IPK max 4)
                        $calculatedIpk = $calculatedIpk !== null ? min(max($calculatedIpk, 0), 4) : null;

                        // ─── 6. Akademik ───
                        $akademik = AkademikMahasiswa::updateOrCreate(
                            ['mahasiswa_id' => $mahasiswa->id],
                            [
                                'dosen_wali_id' => $dosenId,
                                'tahun_masuk' => $tahun,
                                'semester_aktif' => $semesterAktif,
                                'ipk' => $calculatedIpk,
                                'sks_lulus' => $sksLulus,
                                'sks_tempuh' => $sksLulus + rand(0, 10),
                                'sks_now' => rand(18, 22),
                                'sks_gagal' => 0,
                                'mk_nasional' => $mkNasional,
                                'mk_fakultas' => $mkFakultas,
                                'mk_prodi' => $mkProdi,
                            ]
                        );

                        // Kumpulkan untuk EWS update setelah semua batch insert selesai
                        $ewsServiceCalls[] = $akademik->id;
                    }

                    // Flush remaining KHS batch
                    if (! empty($khsBatch)) {
                        KhsKrsMahasiswa::insert($khsBatch);
                    }

                    DB::commit();

                    // ─── 7. EWS recalc per akademik (di luar transaction supaya partial commit OK) ───
                    foreach ($ewsServiceCalls as $akId) {
                        $ak = AkademikMahasiswa::find($akId);
                        if ($ak) {
                            $ewsService->updateStatus($ak);
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->command->error("Gagal seeding tahun {$tahun}: ".$e->getMessage());
                }
            }
        }
    }

    private function cacheMkByTipeSemester(int $prodiId): array
    {
        $mks = MataKuliah::where('prodi_id', $prodiId)->get();
        $cache = [];
        foreach ($mks as $mk) {
            $cache[$mk->tipe_mk][$mk->semester][] = $mk;
        }

        return $cache;
    }

    /**
     * Cache kelompok per MK untuk lookup cepat di bulk insert.
     * Returns: [mk_id => [kelompok_id_1, kelompok_id_2, ...]]
     */
    private function cacheKelompokByMk(int $prodiId): array
    {
        $rows = DB::table('mata_kuliahs as mk')
            ->join('kelompok_mata_kuliah as kmk', 'mk.id', '=', 'kmk.mata_kuliah_id')
            ->where('mk.prodi_id', $prodiId)
            ->select('mk.id as mk_id', 'kmk.id as kelompok_id')
            ->get();

        $cache = [];
        foreach ($rows as $row) {
            $cache[$row->mk_id][] = $row->kelompok_id;
        }

        return $cache;
    }

    /**
     * Hitung sks_lulus: sum SKS dari latest KHS per matakuliah dgn nilai NOT IN ('D','E').
     *
     * @param  array<int, array>  $khsRows  Array of KHS rows yang baru di-insert
     */
    private function calculateSksLulus(array $khsRows): int
    {
        // Group by matakuliah_id, ambil row paling akhir (last array entry = highest id)
        $latestPerMk = [];
        foreach ($khsRows as $khs) {
            $mkId = $khs['matakuliah_id'];
            // Always overwrite (last entry is newest due to id auto-increment)
            $latestPerMk[$mkId] = $khs;
        }

        $sksLulus = 0;
        foreach ($latestPerMk as $khs) {
            if (! in_array($khs['nilai_akhir_huruf'], ['D', 'E'], true)) {
                $sksLulus += $khs['nilai_akhir_angka']; // Will fix below
            }
        }

        // Pakai SKS dari MK (bukan nilai_akhir_angka)
        $sksLulus = 0;
        $mkIds = array_keys($latestPerMk);
        if (empty($mkIds)) {
            return 0;
        }
        $mkSksMap = MataKuliah::whereIn('id', $mkIds)->pluck('sks', 'id')->toArray();
        foreach ($latestPerMk as $mkId => $khs) {
            if (! in_array($khs['nilai_akhir_huruf'], ['D', 'E'], true)) {
                $sksLulus += $mkSksMap[$mkId] ?? 0;
            }
        }

        return $sksLulus;
    }

    private function isKategoriSelesai(int $mahasiswaId, string $tipeMk, array $mkByTipeSemester): bool
    {
        $latestKhs = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswaId)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('matakuliah_id')
            ->map(fn ($rows) => $rows->first());

        $requiredMkIds = [];
        for ($smt = 1; $smt <= 8; $smt++) {
            if (isset($mkByTipeSemester[$tipeMk][$smt])) {
                foreach ($mkByTipeSemester[$tipeMk][$smt] as $mk) {
                    $requiredMkIds[$mk->id] = true;
                }
            }
        }

        if (empty($requiredMkIds)) {
            return true;
        }

        $passedMkCount = 0;
        foreach (array_keys($requiredMkIds) as $mkId) {
            if (isset($latestKhs[$mkId]) && ! in_array($latestKhs[$mkId]->nilai_akhir_huruf, ['D', 'E'], true)) {
                $passedMkCount++;
            }
        }

        return $passedMkCount >= count($requiredMkIds);
    }

    private function getRandomStatus(int $semester): string
    {
        $r = rand(1, 100);
        if ($semester <= 6) {
            if ($r <= 80) {
                return 'aktif';
            }
            if ($r <= 90) {
                return 'cuti';
            }
            if ($r <= 95) {
                return 'mangkir';
            }

            return 'tidak_aktif';
        } else {
            if ($r <= 55) {
                return 'aktif';
            }
            if ($r <= 75) {
                return 'lulus';
            }
            if ($r <= 80) {
                return 'cuti';
            }
            if ($r <= 90) {
                return 'mangkir';
            }
            if ($r <= 95) {
                return 'DO';
            }

            return 'tidak_aktif';
        }
    }

    private function nilaiHurufToAngka(string $huruf): int
    {
        return match ($huruf) {
            'A' => 4,
            'B' => 3,
            'C' => 2,
            'D' => 1,
            'E' => 0,
            default => 0,
        };
    }
}
