<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class AkademikMahasiswaSeeder extends Seeder
{
    /**
     * Buat record akademik_mahasiswa untuk mahasiswa yang sudah ada.
     * Data mahasiswa di sti_api.sql: id 1–8.
     */
    public function run(): void
    {
        $mahasiswas = Mahasiswa::all();

        if ($mahasiswas->isEmpty()) {
            $this->command->warn('⚠ Tidak ada mahasiswa ditemukan.');
            return;
        }
        
        $dosenCache = [];

        foreach ($mahasiswas as $mhs) {
            if (!array_key_exists($mhs->prodi_id, $dosenCache)) {
                $dosenProdi = Dosen::where('prodi_id', $mhs->prodi_id)->first();
                $dosenCache[$mhs->prodi_id] = $dosenProdi;
            }
            
            $dosen = $dosenCache[$mhs->prodi_id];
            
            if (!$dosen) continue;

            AkademikMahasiswa::firstOrCreate(
                ['mahasiswa_id' => $mhs->id],
                [
                    'dosen_wali_id'  => $dosen->id,
                    'semester_aktif' => 5,
                    'tahun_masuk'    => 2020,
                    'ipk'            => 3.0, // Realistic initial IPK
                    'mk_nasional'    => 'yes',
                    'mk_fakultas'    => 'no',
                    'mk_prodi'       => 'no',
                    'sks_tempuh'     => 70,  // Total SKS attempted (>= sks_lulus)
                    'sks_now'        => 20,  // SKS this semester
                    'sks_lulus'      => 68,  // SKS passed (<= sks_tempuh)
                    'sks_gagal'      => 2,   // Failed SKS = tempuh - lulus
                    'nilai_d_melebihi_batas' => 'no',
                    'nilai_e'        => 'no',
                    // NFU + IPS per-semester fields (migration 2026_04_19)
                    'status_done_nfu_ganjil' => 'no',
                    'status_done_nfu_genap' => 'no',
                    'ips_semester_1' => null,
                    'ips_semester_2' => null,
                    'ips_semester_3' => null,
                ]
            );
        }

        $this->command->info('✔ AkademikMahasiswaSeeder: ' . $mahasiswas->count() . ' record akademik dibuat (multi-prodi).');
    }
}
