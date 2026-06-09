<?php

namespace Database\Seeders;

use App\Models\Prodi;
use Illuminate\Database\Seeder;

class ProdiSeeder extends Seeder
{
    /**
     * Seed prodis — data sudah ada di DB (dari sti_api.sql).
     * Seeder ini hanya memastikan 10 prodi ada, tidak duplikat.
     * Plus 6 prodi ekstra (2026-06-04 Refactor 12).
     */
    public function run(): void
    {
        $prodis = [
            ['kode_prodi' => 'A11', 'nama' => 'Teknik Informatika'],
            ['kode_prodi' => 'A12', 'nama' => 'Sistem Informasi'],
            ['kode_prodi' => 'A14', 'nama' => 'Desain Komunikasi Visual'],
            ['kode_prodi' => 'A15', 'nama' => 'Ilmu Komunikasi'],
            // 6 prodi ekstra (Refactor 12, 2026-06-04)
            ['kode_prodi' => 'A16', 'nama' => 'Film & Televisi'],
            ['kode_prodi' => 'A17', 'nama' => 'Animasi'],
            ['kode_prodi' => 'A18', 'nama' => 'PJJ Informatika'],
            ['kode_prodi' => 'A22', 'nama' => 'Teknik Informatika (Kampus 2)'],
            ['kode_prodi' => 'P31', 'nama' => 'Magister Teknik Informatika'],
            ['kode_prodi' => 'P41', 'nama' => 'Program Ilmu Komputer'],
        ];

        foreach ($prodis as $prodi) {
            Prodi::firstOrCreate(
                ['kode_prodi' => $prodi['kode_prodi']],
                ['nama' => $prodi['nama']]
            );
        }

        $this->command->info('✔ ProdiSeeder: '.count($prodis).' prodi tersedia.');
    }
}
