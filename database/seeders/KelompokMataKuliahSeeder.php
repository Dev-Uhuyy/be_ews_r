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

        if ($mataKuliahs->isEmpty()) {
            $this->command->error('✖ Mata kuliah kosong. Jalankan MataKuliahSeeder terlebih dahulu.');
            return;
        }

        $dosenCache = [];

        foreach ($mataKuliahs as $mk) {
            // Ambil dosen SETEPAT prodi MK
            if (!array_key_exists($mk->prodi_id, $dosenCache)) {
                $dosenProdi = Dosen::where('prodi_id', $mk->prodi_id)->first();
                $dosenCache[$mk->prodi_id] = $dosenProdi;
            }
            
            $dosen = $dosenCache[$mk->prodi_id];

            if (!$dosen) {
                // Jangan gunakan dosen cabang lain, mending diskip.
                // Atau, create fallback dummy dosen for this specific prodi?
                continue; 
            }

            KelompokMataKuliah::updateOrCreate(
                ['mata_kuliah_id' => $mk->id, 'kode' => 'A'],
                ['dosen_pengampu_id' => $dosen->id]
            );
        }

        $this->command->info('✔ KelompokMataKuliahSeeder: Selesai menyinkronkan kelompok dosen (strict prodi mode).');
    }
}
