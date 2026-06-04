<?php

namespace Database\Factories;

use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataKuliahPeminatan>
 */
class MataKuliahPeminatanFactory extends Factory
{
    protected $model = MataKuliahPeminatan::class;

    public function definition(): array
    {
        return [
            'peminatan' => fake()->randomElement(['SC', 'RPLD', 'SK3D', 'EIS', 'EB', 'DATA']),
            'prodi_id' => Prodi::factory(),
        ];
    }
}
