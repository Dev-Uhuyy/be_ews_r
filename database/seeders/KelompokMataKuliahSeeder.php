<?php

namespace Database\Seeders;

use App\Models\KelompokMataKuliah;
use App\Models\MataKuliah;
use App\Models\Dosen;
use Illuminate\Database\Seeder;

class KelompokMataKuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mataKuliahs = MataKuliah::all();

        if ($mataKuliahs->isEmpty()) {
            $this->command->error('Mata Kuliah tidak ditemukan. Jalankan MataKuliahSeeder terlebih dahulu.');
            return;
        }

        $dosens = Dosen::all();

        if ($dosens->isEmpty()) {
            $this->command->error('Dosen tidak ditemukan. Jalankan DosenSeeder terlebih dahulu.');
            return;
        }

        $kelompokNames = ['A', 'B', 'C'];

        foreach ($mataKuliahs as $mataKuliah) {
            // Buat 2-3 kelompok untuk setiap mata kuliah
            $jumlahKelompok = rand(2, 3);

            for ($i = 0; $i < $jumlahKelompok; $i++) {
                $kelompokName = $kelompokNames[$i];
                $dosen = $dosens->random();

                KelompokMataKuliah::updateOrCreate(
                    [
                        'mata_kuliah_id' => $mataKuliah->id,
                        'kode' => $kelompokName,
                    ],
                    [
                        'dosen_pengampu_id' => $dosen->id,
                    ]
                );
            }
        }

        $this->command->info('Berhasil membuat kelompok mata kuliah untuk ' . $mataKuliahs->count() . ' mata kuliah!');
    }
}
