<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class IpsMahasiswaSeeder extends Seeder
{
    /**
     * Buat record ips_mahasiswa untuk mahasiswa yang sudah ada.
     * Generate realistic IPS values based on semester count.
     */
    public function run(): void
    {
        $mahasiswas = Mahasiswa::all();

        if ($mahasiswas->isEmpty()) {
            $this->command->warn('⚠ Tidak ada mahasiswa ditemukan.');
            return;
        }

        $count = 0;
        foreach ($mahasiswas as $mhs) {
            $akademik = AkademikMahasiswa::where('mahasiswa_id', $mhs->id)->first();
            
            // Generate realistic IPS values per student based on semester count
            $maxSemester = $akademik ? min((int)$akademik->semester_aktif, 14) : 1;
            
            $ipsData = [];
            for ($s = 1; $s <= 14; $s++) {
                if ($s <= $maxSemester) {
                    // IPS in realistic range: 1.50 to 4.00
                    $ipsData["ips_$s"] = round(rand(150, 400) / 100, 2);
                } else {
                    $ipsData["ips_$s"] = null;
                }
            }

            IpsMahasiswa::updateOrCreate(
                ['mahasiswa_id' => $mhs->id],
                $ipsData
            );
            $count++;
        }

        $this->command->info('✔ IpsMahasiswaSeeder: ' . $count . ' record IPS dibuat.');
    }
}