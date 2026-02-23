<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkademikMahasiswa;

class AkademikMahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User requested to fill this manually
        // Example:
        AkademikMahasiswa::create([
            'mahasiswa_id' => 1776,
            'dosen_wali_id' => 90,
            'semester_aktif' => 5,
            'tahun_masuk' => 2023,
            'ipk' => 3.50,
            'sks_tempuh' => 100,
            'sks_now' => 20,
            'sks_lulus' => 100,
            'sks_gagal' => 0,
        ]);

    }
}
