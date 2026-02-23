<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Services\EwsService;

class RetakeMahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Simulate mahasiswa ID 2552 retaking failed courses.
     */
    public function run()
    {
        $this->command->info('Starting retake simulation for Mahasiswa ID 2552...');

        // Find mahasiswa
        $mahasiswa = Mahasiswa::with('user')->find(2552);
        if (!$mahasiswa) {
            $this->command->error('Mahasiswa with ID 2552 not found!');
            return;
        }

        $this->command->info("Found: {$mahasiswa->user->name} ({$mahasiswa->nim})");

        // Define retakes: kode => new grade
        $retakes = [
            'A11.54406' => 'B', // Metode Penelitian (E -> B)
        ];

        foreach ($retakes as $kode => $newGrade) {
            // Find mata kuliah
            $mataKuliah = MataKuliah::where('kode', $kode)->first();
            if (!$mataKuliah) {
                $this->command->warn("Mata kuliah {$kode} not found! Skipping...");
                continue;
            }

            // Find existing failed record
            $existingKhs = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                ->where('matakuliah_id', $mataKuliah->id)
                ->whereIn('nilai_akhir_huruf', ['D', 'E'])
                ->orderBy('id', 'desc')
                ->first();

            if (!$existingKhs) {
                $this->command->warn("No failed record found for {$mataKuliah->name}. Skipping...");
                continue;
            }

            // Find a kelompok for this mata kuliah
            $kelompok = KelompokMataKuliah::where('mata_kuliah_id', $mataKuliah->id)->first();
            if (!$kelompok) {
                $this->command->warn("No kelompok found for {$mataKuliah->name}. Skipping...");
                continue;
            }

            // Calculate nilai angka based on huruf
            $nilaiAngka = match($newGrade) {
                'A' => rand(85, 100),
                'B' => rand(70, 84),
                'C' => rand(60, 69),
                default => 70
            };

            // Create retake record with status 'U' (Ulang)
            KhsKrsMahasiswa::create([
                'mahasiswa_id' => $mahasiswa->id,
                'matakuliah_id' => $mataKuliah->id,
                'kelompok_id' => $kelompok->id,
                'absen' => rand(75, 100),
                'status' => 'U', // Ulang (retake)
                'nilai_uts' => rand(70, 100),
                'nilai_uas' => rand(70, 100),
                'nilai_akhir_angka' => $nilaiAngka,
                'nilai_akhir_huruf' => $newGrade,
            ]);

            $this->command->info("✓ Created retake record: {$mataKuliah->name} - Nilai: {$newGrade}");
        }

        // Update akademik mahasiswa status via EwsService
        $this->command->info('Updating akademik mahasiswa status...');

        $akademik = $mahasiswa->akademikmahasiswa;
        if ($akademik) {
            $ewsService = new EwsService();
            $ewsService->updateStatus($akademik);
            $this->command->info("✓ Akademik data updated for mahasiswa {$mahasiswa->user->name}");
        } else {
            $this->command->warn("Akademik mahasiswa tidak ditemukan!");
        }

        $this->command->info('✓ Retake simulation completed!');
    }
}
