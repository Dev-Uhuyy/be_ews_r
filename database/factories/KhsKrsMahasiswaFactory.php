<?php

namespace Database\Factories;

use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KhsKrsMahasiswa>
 */
class KhsKrsMahasiswaFactory extends Factory
{
    protected $model = KhsKrsMahasiswa::class;

    public function definition(): array
    {
        $nilai = fake()->randomElement(['A', 'B', 'C', 'D', 'E']);

        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'matakuliah_id' => MataKuliah::factory(),
            'kelompok_id' => KelompokMataKuliah::factory(),
            'semester_ambil' => fake()->numberBetween(1, 8),
            'status' => 'B',
            'absen' => fake()->numberBetween(70, 100),
            'nilai_uts' => fake()->numberBetween(50, 95),
            'nilai_uas' => fake()->numberBetween(50, 95),
            'nilai_akhir_angka' => $this->hurufToAngka($nilai),
            'nilai_akhir_huruf' => $nilai,
        ];
    }

    public function withNilai(string $huruf): static
    {
        return $this->state(fn () => [
            'nilai_akhir_huruf' => $huruf,
            'nilai_akhir_angka' => $this->hurufToAngka($huruf),
        ]);
    }

    public function ulang(): static
    {
        return $this->state(fn () => ['status' => 'U']);
    }

    private function hurufToAngka(string $h): int
    {
        return match ($h) {
            'A' => 4,
            'B' => 3,
            'C' => 2,
            'D' => 1,
            'E' => 0,
            default => 0,
        };
    }
}
