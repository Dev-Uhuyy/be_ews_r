<?php

namespace Database\Seeders;

use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class khs_krs_mahasiswa_seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contoh pengisian data KHS/KRS Mahasiswa
        // Silahkan sesuaikan data di bawah ini atau gunakan loop untuk generate data dummy
        
        $mahasiswas = Mahasiswa::limit(5)->get(); // Ambil beberapa mahasiswa
        $kelompoks = KelompokMataKuliah::with('mata_kuliah')->limit(5)->get(); // Ambil beberapa kelompok MK

        if ($mahasiswas->isEmpty() || $kelompoks->isEmpty()) {
            return;
        }

        foreach ($mahasiswas as $mahasiswa) {
            // Assign setiap mahasiswa ke acak kelompok mata kuliah
            if ($kelompoks->isNotEmpty()) {
                $kelompok = $kelompoks->random();
                
                KhsKrsMahasiswa::create([
                    'mahasiswa_id' => $mahasiswa->id,
                    'matakuliah_id' => $kelompok->mata_kuliah_id,
                    'kelompok_id' => $kelompok->id,
                    'absen' => rand(70, 100),
                    'status' => 'B',
                    'nilai_uts' => rand(70, 100),
                    'nilai_uas' => rand(70, 100),
                    'nilai_akhir_angka' => rand(70, 100),
                    'nilai_akhir_huruf' => 'A',
                ]);
            }
        }

        KhsKrsMahasiswa::create([
                    'mahasiswa_id' => 1776,
                    'matakuliah_id' => 1,
                    'kelompok_id' => 1,
                    'absen' => rand(70, 100),
                    'status' => 'B',
                    'nilai_uts' => rand(70, 100),
                    'nilai_uas' => rand(70, 100),
                    'nilai_akhir_angka' => rand(70, 100),
                    'nilai_akhir_huruf' => 'A',
                ]);
    }
}
