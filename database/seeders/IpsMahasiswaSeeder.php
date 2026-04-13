<?php

namespace Database\Seeders;

use App\Models\IpsMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class IpsMahasiswaSeeder extends Seeder
{
    /**
     * Buat record ips_mahasiswa untuk mahasiswa yang sudah ada.
     * Semua IPS diisi null — akan diisi saat import data nyata.
     */
    public function run(): void
    {
        $mahasiswas = Mahasiswa::all();

        if ($mahasiswas->isEmpty()) {
            $this->command->warn('⚠ Tidak ada mahasiswa ditemukan.');
            return;
        }

        foreach ($mahasiswas as $mhs) {
            IpsMahasiswa::firstOrCreate(
                ['mahasiswa_id' => $mhs->id]
                // Semua kolom ips_1..ips_14 default NULL
            );
        }

        $this->command->info('✔ IpsMahasiswaSeeder: ' . $mahasiswas->count() . ' record IPS dibuat.');
    }
}
