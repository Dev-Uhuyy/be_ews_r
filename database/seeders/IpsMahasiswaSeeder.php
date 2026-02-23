<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IpsMahasiswa;

class IpsMahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User requested to fill this manually
        // Example:

        IpsMahasiswa::create([
            'mahasiswa_id' => 1776,
            'ips_1' => 3.50,
            'ips_2' => 3.60,
            'ips_3' => 3.70,
            'ips_4' => 3.80,
            // ...
        ]);

    }
}
