<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\IpsMahasiswa;
use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\User;
use App\Services\Admin\EwsService;
use Illuminate\Database\Seeder;

/**
 * BaselineUsersSeeder
 *
 * Melengkapi 8 user baseline dari sti-api (mahasiswa existing di DB)
 * dengan KHS, IPS, dan EWS rows. Sebelumnya seeder hanya mengisi
 * akademik_mahasiswa placeholder tanpa data nilai — sehingga 8 user ini
 * invisible di dashboard (semua 'normal/noneligible' uniform).
 *
 * Setelah seeder ini, 8 user baseline punya:
 * - IPS per semester (1-14, sebagian null sesuai semester_aktif)
 * - 4-8 KHS rows (matakuliah smt 1-2 dari prodi-nya)
 * - akademik_mahasiswa yang realistis (ipk dari IPS, sks_lulus dari KHS, mk_* from KHS completeness)
 * - early_warning_system row dari EwsService
 *
 * Strategi: lookup user by ROLE 'mahasiswa' yang sudah ada di DB, BUKAN
 * hardcode user_id. Aman untuk sti_api.sql dump yang berbeda.
 */
class BaselineUsersSeeder extends Seeder
{
    public function run(): void
    {
        $ewsService = app(EwsService::class);

        $mahasiswas = Mahasiswa::with('user')
            ->whereHas('user', function ($q) {
                $q->role('mahasiswa');
            })
            ->get();

        if ($mahasiswas->isEmpty()) {
            $this->command->warn('⚠ BaselineUsersSeeder: tidak ada mahasiswa existing di DB, skip.');

            return;
        }

        $this->command->info('BaselineUsersSeeder: melengkapi '.$mahasiswas->count().' mahasiswa existing dari sti-api.');

        $dosenCache = [];
        $mkCache = [];
        $completed = 0;

        foreach ($mahasiswas as $mhs) {
            // Skip jika EWS row sudah ada
            if ($mhs->akademikMahasiswa && $mhs->akademikMahasiswa->earlyWarningSystem) {
                continue;
            }

            // Skip jika tidak ada prodi (akibat sti_api.sql tanpa prodi mapping)
            if (! $mhs->prodi_id) {
                continue;
            }

            $dosen = $dosenCache[$mhs->prodi_id] ??= Dosen::where('prodi_id', $mhs->prodi_id)->first();
            if (! $dosen) {
                $this->command->warn("  ⚠ Tidak ada dosen untuk prodi_id={$mhs->prodi_id}, skip mhs id={$mhs->id}");

                continue;
            }

            // Ambil MK semester 1-2 untuk prodi ini (Nasional + Fakultas + Prodi)
            $mkList = $mkCache[$mhs->prodi_id] ??= MataKuliah::where('prodi_id', $mhs->prodi_id)
                ->whereIn('semester', [1, 2])
                ->orderBy('semester')
                ->orderBy('kode')
                ->get();

            if ($mkList->isEmpty()) {
                $this->command->warn("  ⚠ Tidak ada MK untuk prodi_id={$mhs->prodi_id}, skip mhs id={$mhs->id}");

                continue;
            }

            // Semester_aktif = 2 (2 semester sudah ditempuh)
            $semesterAktif = 2;

            // IPS per semester (ips_1, ips_2 only; 3-14 null karena baru smt 2)
            $ips1 = $this->randomIps();
            $ips2 = $this->randomIps();
            $ipsData = [
                'ips_1' => $ips1,
                'ips_2' => $ips2,
            ];
            for ($i = 3; $i <= 14; $i++) {
                $ipsData["ips_{$i}"] = null;
            }

            // IPK = rata-rata 2 IPS
            $ipk = round(($ips1 + $ips2) / 2, 2);

            // Create IPS row
            IpsMahasiswa::updateOrCreate(
                ['mahasiswa_id' => $mhs->id],
                $ipsData
            );

            // Create KHS untuk 4-6 MK pertama (smt 1-2)
            $khsMks = $mkList->take(rand(4, min(6, $mkList->count())));
            foreach ($khsMks as $idx => $mk) {
                $nilai = $this->randomNilai();
                $semesterAmbil = $idx < 3 ? 1 : 2;

                $kelompok = KelompokMataKuliah::where('mata_kuliah_id', $mk->id)->first();
                if (! $kelompok) {
                    // Auto-create kelompok kalau belum ada
                    $kelompok = KelompokMataKuliah::firstOrCreate(
                        ['mata_kuliah_id' => $mk->id, 'kode' => 'A'],
                        ['dosen_pengampu_id' => $dosen->id]
                    );
                }

                KhsKrsMahasiswa::create([
                    'mahasiswa_id' => $mhs->id,
                    'matakuliah_id' => $mk->id,
                    'kelompok_id' => $kelompok->id,
                    'semester_ambil' => $semesterAmbil,
                    'status' => 'B',
                    'absen' => rand(80, 100),
                    'nilai_uts' => rand(60, 90),
                    'nilai_uas' => rand(60, 90),
                    'nilai_akhir_angka' => $this->nilaiHurufToAngka($nilai),
                    'nilai_akhir_huruf' => $nilai,
                ]);
            }

            // Hitung SKS lulus dari KHS (MK dengan nilai NOT IN ('D','E'))
            $sksLulus = (int) KhsKrsMahasiswa::where('mahasiswa_id', $mhs->id)
                ->whereNotIn('nilai_akhir_huruf', ['D', 'E'])
                ->join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
                ->sum('mata_kuliahs.sks');

            // Tentukan mk_* completeness dari KHS
            $mkNasionalSelesai = $this->isKategoriSelesai($mhs->id, 'nasional');
            $mkFakultasSelesai = $this->isKategoriSelesai($mhs->id, 'fakultas');
            $mkProdiSelesai = $this->isKategoriSelesai($mhs->id, 'prodi');

            // Update or create akademik_mahasiswa dengan data real
            $akademik = AkademikMahasiswa::updateOrCreate(
                ['mahasiswa_id' => $mhs->id],
                [
                    'dosen_wali_id' => $dosen->id,
                    'semester_aktif' => $semesterAktif,
                    'tahun_masuk' => now()->year - 1, // 1 tahun lalu
                    'ipk' => $ipk,
                    'sks_lulus' => $sksLulus,
                    'sks_tempuh' => $sksLulus + rand(0, 3),
                    'sks_now' => rand(18, 22),
                    'sks_gagal' => 0,
                    'mk_nasional' => $mkNasionalSelesai ? 'yes' : 'no',
                    'mk_fakultas' => $mkFakultasSelesai ? 'yes' : 'no',
                    'mk_prodi' => $mkProdiSelesai ? 'yes' : 'no',
                    // nilai_d_melebihi_batas & nilai_e akan dihitung oleh EwsService
                ]
            );

            // Trigger EWS recalc — service akan update nilai_e dan nilai_d_melebihi_batas
            try {
                $ewsService->updateStatus($akademik);
                $completed++;
            } catch (\Throwable $e) {
                $this->command->warn('  ⚠ EWS recalc gagal untuk mhs id='.$mhs->id.': '.$e->getMessage());
            }
        }

        $this->command->info("✔ BaselineUsersSeeder: {$completed} baseline user dilengkapkan dengan KHS+IPS+EWS.");
    }

    /**
     * Random IPS 1.50-4.00
     */
    private function randomIps(): float
    {
        return round(rand(150, 400) / 100, 2);
    }

    /**
     * Random nilai huruf dengan bobot realistic (lebih banyak B, beberapa A, sedikit D, sangat sedikit E)
     */
    private function randomNilai(): string
    {
        $pool = ['A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D'];

        return $pool[array_rand($pool)];
    }

    /**
     * Konversi nilai huruf ke angka (skala 4)
     */
    private function nilaiHurufToAngka(string $huruf): int
    {
        return match ($huruf) {
            'A' => 4,
            'B+' => 3, // tidak di-seed tapi handle defensif
            'B' => 3,
            'C+' => 2,
            'C' => 2,
            'D' => 1,
            'E' => 0,
            default => 0,
        };
    }

    /**
     * Cek apakah semua MK tipe tertentu (sampai semester_aktif=2) sudah lulus
     * di KHS mahasiswa. Return true jika semua MK dgn tipe_mk=X di semester<=2
     * punya KHS row dengan nilai NOT IN ('D','E').
     */
    private function isKategoriSelesai(int $mahasiswaId, string $tipeMk): bool
    {
        // Ambil semua MK dengan tipe_mk=X dan semester <= 2
        $mkIds = MataKuliah::where('tipe_mk', $tipeMk)
            ->whereIn('semester', [1, 2])
            ->pluck('id')
            ->toArray();

        if (empty($mkIds)) {
            // Tidak ada MK tipe ini di smt 1-2 → anggap selesai
            return true;
        }

        // Cek apakah ada KHS lulus untuk semua MK tipe ini
        $khsLulusCount = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswaId)
            ->whereIn('matakuliah_id', $mkIds)
            ->whereNotIn('nilai_akhir_huruf', ['D', 'E'])
            ->distinct('matakuliah_id')
            ->count('matakuliah_id');

        return $khsLulusCount >= count($mkIds);
    }
}
