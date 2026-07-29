<?php

namespace Database\Factories;

use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prodi>
 */
class ProdiFactory extends Factory
{
    protected $model = Prodi::class;

    public function definition(): array
    {
        $kode = 'TEST-'.strtoupper($this->faker->unique()->bothify('??####'));

        return [
            'nama' => 'Prodi Test '.$kode,
            'kode_prodi' => $kode,
        ];
    }
}
