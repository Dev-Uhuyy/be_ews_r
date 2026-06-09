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
        static $counter = 0;
        $counter++;
        $kode = 'T'.str_pad((string) $counter, 2, '0', STR_PAD_LEFT);

        return [
            'nama' => 'Prodi Test '.$kode,
            'kode_prodi' => $kode,
        ];
    }
}
