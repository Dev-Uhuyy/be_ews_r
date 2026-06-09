<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelompokMataKuliah;
use App\Models\MataKuliah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelompokMataKuliahSeeder extends Seeder
{
    /**
     * REWRITE 2026-06-04 (Refactor 12).
     *
     * Untuk SETIAP mata kuliah, create multiple kelas paralel (A/B/C/D) dengan
     * distinct dosen_pengampu per kelas. Pola:
     *
     * - MK smt 1-4 (wajib): 4 kelas paralel (A, B, C, D)
     * - MK smt 5-6 (lanjutan): 2 kelas paralel (A, B)
     * - MK smt 7-8 (KKN/KP/TA): 1 kelas (A)
     * - MK peminatan: 3 kelas paralel (A, B, C)
     *
     * Plus update koordinator_mk di MK = dosen random pertama prodi tsb.
     *
     * Total estimasi:
     * - 10 prodi × ~33 MK = ~330 MK
     * - Avg ~3 kelas/MK = ~990 kelompok
     */
    public function run(): void
    {
        $prodies = \App\Models\Prodi::all();

        if ($prodies->isEmpty()) {
            $this->command->error('✖ Prodi kosong. Jalankan ProdiSeeder terlebih dahulu.');

            return;
        }

        $totalKelompok = 0;
        $mkDiupdate = 0;

        foreach ($prodies as $prodi) {
            $dosens = Dosen::where('prodi_id', $prodi->id)->get();

            if ($dosens->isEmpty()) {
                $this->command->warn("⚠ Prodi {$prodi->kode_prodi} tidak punya dosen. Lewati.");

                continue;
            }

            $mks = MataKuliah::where('prodi_id', $prodi->id)->get();

            if ($mks->isEmpty()) {
                $this->command->warn("⚠ Prodi {$prodi->kode_prodi} tidak punya MK. Lewati.");

                continue;
            }

            // Pilih koordinator MK: dosen random pertama (round-robin per MK via offset)
            $koordIndex = 0;

            foreach ($mks as $mk) {
                // Tentukan jumlah kelas paralel
                $kelasCount = match (true) {
                    $mk->tipe_mk === 'peminatan' => 3,
                    $mk->semester <= 4 => 4,
                    $mk->semester <= 6 => 2,
                    default => 1,
                };

                // Set koordinator_mk di MK
                $koordDosen = $dosens[$koordIndex % $dosens->count()];
                $mk->koordinator_mk = $koordDosen->id;
                $mk->save();
                $mkDiupdate++;
                $koordIndex++;

                // Create kelas paralel A/B/C/D
                for ($k = 0; $k < $kelasCount; $k++) {
                    $kodeKelas = chr(65 + $k); // A, B, C, D
                    // Pick dosen pengampu (distinct dari koordinator kalau ada cukup dosen)
                    $dosenIdx = ($koordIndex + $k) % $dosens->count();
                    $dosenPengampu = $dosens[$dosenIdx];

                    KelompokMataKuliah::firstOrCreate(
                        ['mata_kuliah_id' => $mk->id, 'kode' => $kodeKelas],
                        ['dosen_pengampu_id' => $dosenPengampu->id]
                    );
                    $totalKelompok++;
                }
            }

            $this->command->info("✔ KelompokMataKuliahSeeder: {$prodi->kode_prodi} - ".count($mks).' MK, '.KelompokMataKuliah::whereHas('mataKuliah', fn ($q) => $q->where('prodi_id', $prodi->id))->count().' kelompok total.');
        }

        $this->command->info("✔ KelompokMataKuliahSeeder: Selesai. Total {$totalKelompok} kelompok, {$mkDiupdate} MK dengan koordinator_mk.");
    }
}
