<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateKoordinatorMataKuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coordinators = [
            1 => 'Dr. Budi Santoso, M.T. SC1',
            2 => 'Siti Aminah, S.Kom., M.Kom. SC2',
            3 => 'Eko Prasetyo, Ph.D. SC3',
            4 => 'Rina Suryani, S.T., M.T. RPLD1',
            5 => 'Dedi Kurniawan, M.Sc. RPLD2',
            6 => 'Dr. Maya Sari, S.Si., M.Si. RPLD3',
            7 => 'Agus Setiawan, S.Kom., M.Cs. SKKKD',
            8 => 'Dewi Lestari, M.Eng. SKKKD2',
            9 => 'Ir. Hendra Gunawan, M.T. SKKKD3',
        ];

        foreach ($coordinators as $id => $name) {
            DB::table('mata_kuliahs')
                ->where('id', $id)
                ->update(['koordinator_mk' => $name]);
        }
    }
}
