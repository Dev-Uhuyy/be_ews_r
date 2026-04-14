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
     */
    public function run(): void
    {
        $prodis = Prodi::all();

        $peminatanMap = [
            'A11' => ['SC', 'RPLD', 'SK3D'], // Teknik Informatika
            'A12' => ['EIS', 'EB', 'DATA'],  // Sistem Informasi: Enterprise Info System, E-Business, Data Science
            'A14' => ['DG', 'MM', 'AN'],     // DKV: Desain Grafis, Multimedia, Animasi
            'A15' => ['PR', 'JR', 'BROAD'],  // Ilkom: Public Relations, Jurnalistik, Broadcasting
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

        $this->command->info('✔ MataKuliahPeminatanSeeder: ' . MataKuliahPeminatan::count() . ' peminatan tersedia (spesifik prodi).');
    }
}
