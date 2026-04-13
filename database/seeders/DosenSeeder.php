<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

class DosenSeeder extends Seeder
{
    /**
     * Seed dosen untuk setiap user berkategori 'dosen' di DB.
     *
     * Catatan: Di sti_api.sql, user dosen ada (misal user_id 10, 21, 32, 43).
     * Seeder ini membuat record dosen untuk setiap prodi jika belum ada.
     */
    public function run(): void
    {
        // Data dosen per prodi — berdasarkan user dosen di sti_api.sql
        $dosens = [
            // Prodi A11 - user_id 10 (Dosen A11)
            ['user_id' => 10, 'kode_prodi' => 'A11', 'npp' => 'A11.001'],
            // Prodi A12 - user_id 21 (Dosen A12)
            ['user_id' => 21, 'kode_prodi' => 'A12', 'npp' => 'A12.001'],
            // Prodi A14 - user_id 32 (Dosen A14)
            ['user_id' => 32, 'kode_prodi' => 'A14', 'npp' => 'A14.001'],
            // Prodi A15 - user_id 43 (Dosen A15)
            ['user_id' => 43, 'kode_prodi' => 'A15', 'npp' => 'A15.001'],
        ];

        foreach ($dosens as $data) {
            $prodi = Prodi::where('kode_prodi', $data['kode_prodi'])->first();

            // Pastikan user-nya ada
            if (!User::find($data['user_id'])) {
                $this->command->warn("⚠ User ID {$data['user_id']} tidak ditemukan, dosen untuk {$data['kode_prodi']} dilewati.");
                continue;
            }

            Dosen::firstOrCreate(
                ['user_id' => $data['user_id']],
                [
                    'prodi_id' => $prodi?->id,
                    'npp'      => $data['npp'],
                ]
            );
        }

        $this->command->info('✔ DosenSeeder: ' . Dosen::count() . ' dosen tersedia.');
    }
}
