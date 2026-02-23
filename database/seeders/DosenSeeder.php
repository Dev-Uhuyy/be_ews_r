<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

class DosenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed specific Dosen for User ID 1983
        Dosen::updateOrCreate(
            ['user_id' => 1983],
            [
                'prodi_id' => 1,
                'npp' => '111.11.111',
                'gelar_depan' => 'Ir.',
                'gelar_belakang' => 'M.Kom',
                'bidang_kajian' => 'SC', 
                'telepon' => '081234567890',
                'scholar_link' => 'https://scholar.google.com/citations?user=1234567890',
                'ttd' => 'ttd.png'
            ]
        );
    }
}
