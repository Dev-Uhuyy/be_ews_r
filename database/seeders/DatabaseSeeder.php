<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed database EWS.
     *
     * PENTING: Database sudah memiliki data awal dari sti_api.sql
     * (prodis, users, mahasiswa, roles, dll.). Semua seeder di sini
     * menggunakan firstOrCreate / updateOrCreate agar AMAN dijalankan
     * di atas database yang sudah ada - TIDAK akan hapus/duplikat data.
     *
     * Urutan mengikuti dependency FK:
     *   1. RoleSeeder              - tambah permission EWS ke role existing
     *   2. ProdiSeeder             - pastikan 4 prodi ada (firstOrCreate)
     *   3. UserSeeder              - tambah user EWS test jika belum ada
     *   4. DosenSeeder             - buat record dosen untuk user dosen existing
     *   5. MahasiswaSeeder         - isi prodi_id & kolom EWS di mahasiswa existing
     *   6. MataKuliahPeminatanSeeder - buat 3 peminatan A11
     *   7. MataKuliahSeeder        - seed MK A11 lengkap 8 semester
     *   8. KelompokMataKuliahSeeder - 1 kelompok per MK
     *   9. AkademikMahasiswaSeeder - record akademik per mahasiswa (dari mahasiswa existing)
     *  10. EwsDummyDataSeeder     - generate 75 mahasiswa/thn/prodi + IPS + KHS + EWS
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProdiSeeder::class,
            UserSeeder::class,
            DosenSeeder::class,
            MahasiswaSeeder::class,
            MataKuliahPeminatanSeeder::class,
            MataKuliahSeeder::class,
            KelompokMataKuliahSeeder::class,
            AkademikMahasiswaSeeder::class,
            EwsDummyDataSeeder::class,
        ]);
    }
}