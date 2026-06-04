<?php

namespace Database\Factories;

use App\Models\IpsMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IpsMahasiswa>
 */
class IpsMahasiswaFactory extends Factory
{
    protected $model = IpsMahasiswa::class;

    public function definition(): array
    {
        $data = ['mahasiswa_id' => Mahasiswa::factory()];
        for ($i = 1; $i <= 14; $i++) {
            $data["ips_{$i}"] = fake()->optional(0.5)->randomFloat(2, 1.5, 4.0);
        }

        return $data;
    }
}
