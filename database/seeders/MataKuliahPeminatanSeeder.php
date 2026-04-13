<?php

namespace Database\Seeders;

use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MataKuliahPeminatanSeeder extends Seeder
{
    /**
     * Seed peminatan per prodi.
     * Gunakan firstOrCreate agar aman dijalankan berulang.
     */
    public function run(): void
    {
        // Peminatan untuk prodi Teknik Informatika (A11) — sesuai bidang_kajian dosen
        $peminatans = [
            ['kode_prodi' => 'A11', 'peminatan' => 'SC'],    // Software Computing
            ['kode_prodi' => 'A11', 'peminatan' => 'RPLD'],  // Rekayasa PL & Data
            ['kode_prodi' => 'A11', 'peminatan' => 'SK3D'],  // Sistem Komputer 3D
        ];

        foreach ($peminatans as $data) {
            $prodi = Prodi::where('kode_prodi', $data['kode_prodi'])->first();

            if (!$prodi) continue;

            MataKuliahPeminatan::firstOrCreate(
                ['peminatan' => $data['peminatan'], 'prodi_id' => $prodi->id]
            );
        }

        $this->command->info('✔ MataKuliahPeminatanSeeder: ' . MataKuliahPeminatan::count() . ' peminatan tersedia.');
    }
}
