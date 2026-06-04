<?php

namespace Database\Factories;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AkademikMahasiswa>
 */
class AkademikMahasiswaFactory extends Factory
{
    protected $model = AkademikMahasiswa::class;

    public function definition(): array
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'dosen_wali_id' => Dosen::factory(),
            'semester_aktif' => fake()->numberBetween(1, 14),
            'tahun_masuk' => fake()->numberBetween(2019, 2025),
            'ipk' => round(fake()->randomFloat(2, 1.5, 4.0), 2),
            'mk_nasional' => fake()->randomElement(['yes', 'no']),
            'mk_fakultas' => fake()->randomElement(['yes', 'no']),
            'mk_prodi' => fake()->randomElement(['yes', 'no']),
            'sks_tempuh' => fake()->numberBetween(20, 150),
            'sks_now' => fake()->numberBetween(18, 24),
            'sks_lulus' => fake()->numberBetween(20, 150),
            'sks_gagal' => 0,
            'nilai_d_melebihi_batas' => 'no',
            'nilai_e' => 'no',
        ];
    }
}
