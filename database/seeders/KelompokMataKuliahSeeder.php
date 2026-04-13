<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelompokMataKuliah;
use App\Models\MataKuliah;
use Illuminate\Database\Seeder;

class KelompokMataKuliahSeeder extends Seeder
{
    /**
     * Buat 1 kelompok (kelas A) untuk setiap mata kuliah.
     * Gunakan updateOrCreate agar aman dijalankan ulang.
     */
    public function run(): void
    {
        $mataKuliahs = MataKuliah::all();
        $dosen = Dosen::first();

        if ($mataKuliahs->isEmpty()) {
            $this->command->error('✖ Mata kuliah kosong. Jalankan MataKuliahSeeder terlebih dahulu.');
            return;
        }

        if (!$dosen) {
            $this->command->error('✖ Dosen kosong. Jalankan DosenSeeder terlebih dahulu.');
            return;
        }

        foreach ($mataKuliahs as $mk) {
            KelompokMataKuliah::updateOrCreate(
                ['mata_kuliah_id' => $mk->id, 'kode' => 'A'],
                ['dosen_pengampu_id' => $dosen->id]
            );
        }

        $this->command->info('✔ KelompokMataKuliahSeeder: ' . $mataKuliahs->count() . ' kelompok dibuat.');
    }
}
