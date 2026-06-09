<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\KelompokMataKuliah;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelompokMataKuliah>
 */
class KelompokMataKuliahFactory extends Factory
{
    protected $model = KelompokMataKuliah::class;

    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'kode' => 'A',
            'dosen_pengampu_id' => Dosen::factory(),
        ];
    }
}
