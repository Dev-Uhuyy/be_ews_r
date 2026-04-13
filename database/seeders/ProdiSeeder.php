<?php

namespace Database\Seeders;

use App\Models\Prodi;
use Illuminate\Database\Seeder;

class ProdiSeeder extends Seeder
{
    /**
     * Seed prodis — data sudah ada di DB (dari sti_api.sql).
     * Seeder ini hanya memastikan 4 prodi ada, tidak duplikat.
     */
    public function run(): void
    {
        $prodis = [
            ['kode_prodi' => 'A11', 'nama' => 'Teknik Informatika'],
            ['kode_prodi' => 'A12', 'nama' => 'Sistem Informasi'],
            ['kode_prodi' => 'A14', 'nama' => 'Desain Komunikasi Visual'],
            ['kode_prodi' => 'A15', 'nama' => 'Ilmu Komunikasi'],
        ];

        foreach ($prodis as $prodi) {
            Prodi::firstOrCreate(
                ['kode_prodi' => $prodi['kode_prodi']],
                ['nama'       => $prodi['nama']]
            );
        }

        $this->command->info('✔ ProdiSeeder: ' . count($prodis) . ' prodi tersedia.');
    }
}
