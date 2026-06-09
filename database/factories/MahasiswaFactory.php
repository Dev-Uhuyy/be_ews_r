<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    public function definition(): array
    {
        static $nimCounter = 9000;

        return [
            'user_id' => User::factory(),
            'prodi_id' => Prodi::factory(),
            'nim' => 'TST.'.date('Y').'.'.str_pad((string) (++$nimCounter), 5, '0', STR_PAD_LEFT),
            'transkrip' => null,
            'telepon' => '08'.fake()->numerify('##########'),
            'minat' => null,
            'cuti_2' => 'no',
            'status_mahasiswa' => 'aktif',
            'tanggal_yusidium' => null,
        ];
    }

    public function cuti(): static
    {
        return $this->state(fn () => ['status_mahasiswa' => 'cuti']);
    }

    public function cuti2x(): static
    {
        return $this->state(fn () => ['status_mahasiswa' => 'cuti', 'cuti_2' => 'yes']);
    }

    public function mangkir(): static
    {
        return $this->state(fn () => ['status_mahasiswa' => 'mangkir']);
    }

    public function tidakAktif(): static
    {
        return $this->state(fn () => ['status_mahasiswa' => 'tidak_aktif']);
    }

    public function lulus(): static
    {
        return $this->state(fn () => [
            'status_mahasiswa' => 'lulus',
            'tanggal_yusidium' => now()->subMonths(rand(1, 24))->format('Y-m-d'),
        ]);
    }

    public function do(): static
    {
        return $this->state(fn () => ['status_mahasiswa' => 'DO']);
    }
}
