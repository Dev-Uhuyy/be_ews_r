<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\AkademikMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Dosen;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Ensure we have a dosen for relation
        $dosen = Dosen::first();
        if (!$dosen) {
            // Create a dummy dosen if none exists
             $userDosen = User::create([
                'name' => 'Dosen Dummy',
                'email' => 'dosendummy@ews.com',
                'password' => bcrypt('password'),
            ]);
            $userDosen->assignRole('dosen');

            
            $dosen = Dosen::create([
                'user_id' => $userDosen->id,
                'prodi_id' => 1,
                'npp' => '999.999.999',
                'gelar_depan' => 'Dr.',
                'gelar_belakang' => 'M.Kom',
            ]);
        }

        // Generate 20 Dummy Mahasiswa
        for ($i = 0; $i < 20; $i++) {
            // 1. Create User
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->email,
                'password' => bcrypt('password'),
            ]);
            $user->assignRole('mahasiswa');

            // 2. Create Mahasiswa
            // Random status as per previous controller logic
            $statuses = ['aktif', 'cuti', 'mangkir', 'DO', 'lulus', 'tidak_aktif'];
            $status = $statuses[array_rand($statuses)];

            
            $cuti2 = ($status == 'cuti') ? 'yes' : 'no';

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => $faker->unique()->numerify('A11.202#.#####'),
                'telepon' => $faker->phoneNumber,
                'minat' => $faker->randomElement(['Web', 'Mobile', 'Data Science']),
                'cuti_2' => $cuti2,
                // 'status_mahasiswa' is not mass assignable in some previous steps or default?
                // 'status_mahasiswa' is not mass assignable in some previous steps or default? 
                // In migration 'status_mahasiswa' is enum default 'aktif'.
                // Ideally it should be fillable but I don't recall adding it to fillable in Step 213 (only added cuti_2).
                // Let's check Mahasiswa model again if needed, but for now assuming it might be guarded or I should force it.
                // Wait, if it's not in fillable, create won't work for it.
            ]);

            
            // Force update status if not fillable
            $mahasiswa->status_mahasiswa = $status;
            $mahasiswa->save();

            // 3. Create AkademikMahasiswa
            $akademik = AkademikMahasiswa::create([
                'mahasiswa_id' => $mahasiswa->id,
                'dosen_wali_id' => $dosen->id,
                'semester_aktif' => $faker->numberBetween(1, 8),
                'tahun_masuk' => $faker->rand(2018,2025),
                'ipk' => $faker->randomFloat(2, 2.00, 4.00),
                'sks_tempuh' => $faker->numberBetween(10, 140),
                'sks_now' => $faker->numberBetween(18, 24),
                'sks_lulus' => $faker->numberBetween(10, 140),
                'sks_gagal' => $faker->numberBetween(0, 10),
            ]);

            // 4. Create IpsMahasiswa
            $ipsData = ['mahasiswa_id' => $mahasiswa->id];
            for ($j = 1; $j <= rand(1,14); $j++) {
                $ipsData["ips_$j"] = $faker->randomFloat(2, 2.00, 4.00);
            }
            IpsMahasiswa::create($ipsData);

            // 5. Create EarlyWarningSystem
            $ewsStatus = $faker->randomElement(['tepat_waktu', 'normal', 'perhatian', 'kritis']);
            $kelulusanStatus = $faker->randomElement(['eligible', 'noneligible']);

            
            EarlyWarningSystem::create([
                'akademik_mahasiswa_id' => $akademik->id,
                'status' => $ewsStatus,
                'status_kelulusan' => $kelulusanStatus,
                'status_rekomitmen' => 'no',
            ]);
        }
    }
}
