<?php

namespace Database\Factories;

use App\Models\MataKuliah;
use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataKuliah>
 */
class MataKuliahFactory extends Factory
{
    protected $model = MataKuliah::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        $kode = 'MK.'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT);

        return [
            'prodi_id' => Prodi::factory(),
            'kode' => $kode,
            'name' => 'Mata Kuliah '.$counter,
            'sks' => fake()->randomElement([2, 3, 4]),
            'semester' => fake()->numberBetween(1, 8),
            'tipe_mk' => 'prodi',
            'koordinator_mk' => null,
            'peminatan_id' => null,
        ];
    }

    public function nasional(): static
    {
        return $this->state(fn () => ['tipe_mk' => 'nasional']);
    }

    public function fakultas(): static
    {
        return $this->state(fn () => ['tipe_mk' => 'fakultas']);
    }

    public function prodi(): static
    {
        return $this->state(fn () => ['tipe_mk' => 'prodi']);
    }

    public function peminatan(?MataKuliahPeminatan $p = null): static
    {
        return $this->state(fn () => [
            'tipe_mk' => 'peminatan',
            'peminatan_id' => $p?->id ?? MataKuliahPeminatan::factory(),
        ]);
    }

    public function withSemester(int $smt): static
    {
        return $this->state(fn () => ['semester' => $smt]);
    }
}
