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
     * Urutan mengikuti dependency FK + fase improvement 2026-06-04:
     *   1. RoleSeeder              - tambah permission EWS ke role existing
     *   2. ProdiSeeder             - pastikan 4 prodi ada (firstOrCreate)
     *   3. UserSeeder              - tambah user EWS test jika belum ada
     *   4. DosenSeeder             - buat record dosen untuk user dosen existing
     *   5. MataKuliahPeminatanSeeder - buat 3 peminatan A11
     *   6. MataKuliahSeeder        - seed MK A11-A15 lengkap 8 semester + koordinator_mk
     *   7. KelompokMataKuliahSeeder - 1 kelompok per MK
     *   8. MahasiswaSeeder         - isi prodi_id & kolom EWS di mahasiswa existing
     *   9. AkademikMahasiswaSeeder - record akademik per mahasiswa (dari mahasiswa existing)
     *  10. BaselineUsersSeeder     - lengkapi 8 user baseline dgn KHS+IPS+EWS (NEW 2026-06-04)
     *  11. EwsDummyDataSeeder      - generate ~1,824 mhs random dgn KHS-derived sks_lulus & mk_*
     *  12. EwsTargetedScenarioSeeder - 50 skenario deterministik boundary case (NEW 2026-06-04)
     *
     * Catatan: IpsMahasiswaSeeder DIHAPUS dari daftar (orphan seeder).
     *   Logic create IPS row untuk 8 baseline sudah dipindah ke
     *   BaselineUsersSeeder. Untuk bulk data, EwsDummyDataSeeder
     *   create IPS row-nya sendiri.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProdiSeeder::class,
            UserSeeder::class,
            DosenSeeder::class,
            MataKuliahPeminatanSeeder::class,
            MataKuliahSeeder::class,
            KelompokMataKuliahSeeder::class,
            MahasiswaSeeder::class,
            AkademikMahasiswaSeeder::class,
            BaselineUsersSeeder::class,
            EwsDummyDataSeeder::class,
            EwsTargetedScenarioSeeder::class,
        ]);
    }
}
