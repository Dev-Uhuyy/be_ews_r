<?php

namespace Database\Seeders;

use App\Models\MataKuliahPeminatan;
use Illuminate\Database\Seeder;

class MataKuliahPeminatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $peminatans = ['SC', 'RPLD', 'SKKKD'];

        foreach ($peminatans as $peminatan) {
            MataKuliahPeminatan::create([
                'prodi_id' => 1,
                'peminatan' => $peminatan,
            ]);
        }
    }
}
