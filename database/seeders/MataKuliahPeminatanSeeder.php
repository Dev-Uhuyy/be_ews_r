<?php

namespace Database\Seeders;

use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MataKuliahPeminatanSeeder extends Seeder
{
    /**
     * Seed peminatan per prodi dengan data realistis.
     * Gunakan firstOrCreate agar aman dijalankan berulang.
     *
     * Coverage 10 prodi (4 utama + 6 ekstra).
     */
    public function run(): void
    {
        $prodis = Prodi::all();

        $peminatanMap = [
            // 4 prodi utama
            'A11' => ['SC', 'RPLD', 'SK3D'], // Teknik Informatika
            'A12' => ['EIS', 'EB', 'DATA'],  // Sistem Informasi: Enterprise Info System, E-Business, Data Science
            'A14' => ['DG', 'MM', 'AN'],     // DKV: Desain Grafis, Multimedia, Animasi
            'A15' => ['PR', 'JR', 'BROAD'],  // Ilkom: Public Relations, Jurnalistik, Broadcasting
            // 6 prodi ekstra (2026-06-04 Refactor 12)
            'A16' => ['PRD', 'VFX', 'AUD'],    // Film & Televisi: Produksi, VFX, Audio
            'A17' => ['2D', '3D', 'GAME'],     // Animasi: 2D, 3D, Game Art
            'A18' => ['PJJ-A', 'PJJ-B', 'PJJ-C'], // PJJ Informatika
            'A22' => ['A22-A', 'A22-B', 'A22-C'], // Teknik Informatika (kampus 2)
            'P31' => ['RISET', 'TERAPAN', 'KONS'], // Magister Teknik Informatika
            'P41' => ['P41-A', 'P41-B', 'P41-C'], // Program Ilmu Komputer
        ];

        foreach ($prodis as $prodi) {
            $kode = $prodi->kode_prodi;

            if (isset($peminatanMap[$kode])) {
                foreach ($peminatanMap[$kode] as $peminatan) {
                    MataKuliahPeminatan::firstOrCreate(
                        ['peminatan' => $peminatan, 'prodi_id' => $prodi->id]
                    );
                }
            }
        }

        $this->command->info('✔ MataKuliahPeminatanSeeder: '.MataKuliahPeminatan::count().' peminatan tersedia (spesifik prodi).');
    }
}
