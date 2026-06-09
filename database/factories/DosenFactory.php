<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    protected $model = Dosen::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prodi_id' => Prodi::factory(),
            'npp' => 'NPP.'.fake()->unique()->numerify('###.##'),
            'gelar_depan' => 'Dr.',
            'gelar_belakang' => 'M.Kom.',
            'bidang_kajian' => 'SC',
            'status_dosen' => 'Aktif',
        ];
    }
}
