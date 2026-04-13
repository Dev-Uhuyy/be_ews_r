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
        $dosen = Dosen::first();

        if (!$dosen) {
            $this->command->error('✖ Dosen tidak ditemukan. Jalankan DosenSeeder terlebih dahulu.');
            return;
        }

        $mahasiswas = Mahasiswa::all();

        if ($mahasiswas->isEmpty()) {
            $this->command->warn('⚠ Tidak ada mahasiswa ditemukan.');
            return;
        }

        foreach ($mahasiswas as $mhs) {
            AkademikMahasiswa::firstOrCreate(
                ['mahasiswa_id' => $mhs->id],
                [
                    'dosen_wali_id'  => $dosen->id,
                    'semester_aktif' => 5,
                    'tahun_masuk'    => 2020,
                    'ipk'            => null,
                    'mk_nasional'    => 'no',
                    'mk_fakultas'    => 'no',
                    'mk_prodi'       => 'no',
                    'sks_tempuh'     => null,
                    'sks_now'        => null,
                    'sks_lulus'      => null,
                    'sks_gagal'      => null,
                    'nilai_d_melebihi_batas' => 'no',
                    'nilai_e'        => 'no',
                ]
            );
        }

        $this->command->info('✔ AkademikMahasiswaSeeder: ' . $mahasiswas->count() . ' record akademik dibuat.');
    }
}
