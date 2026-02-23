<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            ProdiSeeder::class,
            UserSeeder::class,
            DosenSeeder::class,
            MahasiswaSeeder::class,
            ProdiUserSeeder::class,
            MataKuliahPeminatanSeeder::class,
            MataKuliahSeeder::class,
            KelompokMataKuliahSeeder::class,
            khs_krs_mahasiswa_seeder::class,
            IpsMahasiswaSeeder::class,
            AkademikMahasiswaSeeder::class,
            DummyKhsKrsSeeder::class,
            DummyMahasiswaSeeder::class, // Tambahan untuk dummy 10 angkatan
        ]);
    }
}
