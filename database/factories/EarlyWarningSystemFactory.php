<?php

namespace Database\Factories;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarlyWarningSystem>
 */
class EarlyWarningSystemFactory extends Factory
{
    protected $model = EarlyWarningSystem::class;

    public function definition(): array
    {
        return [
            'akademik_mahasiswa_id' => AkademikMahasiswa::factory(),
            'status' => fake()->randomElement(['tepat_waktu', 'normal', 'perhatian', 'kritis']),
            'status_kelulusan' => fake()->randomElement(['eligible', 'noneligible']),
            'SPS1' => 'no',
            'SPS2' => 'no',
            'SPS3' => 'no',
        ];
    }
}
