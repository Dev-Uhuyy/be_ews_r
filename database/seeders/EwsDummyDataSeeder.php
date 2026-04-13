<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Models\MataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\IpsMahasiswa;
use App\Services\Kaprodi\EwsService;

class EwsDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates 20 varied students per Prodi for EWS testing.
     */
    public function run(): void
    {
        $prodis = Prodi::all();
        $ewsService = app(EwsService::class);
        $dosenId = \App\Models\Dosen::first()?->id;
        $kelompokId = \App\Models\KelompokMataKuliah::first()?->id;

        foreach ($prodis as $prodi) {
            $this->command->info("Seeding Mahasiswa for Prodi: {$prodi->kode_prodi} - {$prodi->nama}");

            // Create some dummy MataKuliah for the prodi
            $mks = [];
            for ($i = 1; $i <= 5; $i++) {
                $mks[] = MataKuliah::firstOrCreate(
                    ['kode' => $prodi->kode_prodi . '.DUMMY.' . $i],
                    [
                        'prodi_id' => $prodi->id,
                        'name' => "MK Simulasi $i {$prodi->kode_prodi}",
                        'sks' => 3,
                        'semester' => rand(1, 8),
                        'tipe_mk' => 'prodi',
                    ]
                );
            }

            for ($i = 1; $i <= 20; $i++) {
                // 1. Create User
                $email = "dummy_{$prodi->kode_prodi}_mhs{$i}@ews.com";
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "MHS {$prodi->kode_prodi} Variatif {$i}",
                        'password' => Hash::make('password'),
                        'prodi_id' => $prodi->id,
                    ]
                );
                if (!$user->hasRole('mahasiswa')) {
                    $user->assignRole('mahasiswa');
                }

                // 2. Create Mahasiswa Profile
                $nim = $prodi->kode_prodi . '.DUMMY.' . str_pad($i, 5, '0', STR_PAD_LEFT);
                $statusMahasiswa = $this->getRandomStatus();

                $mahasiswa = Mahasiswa::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nim' => $nim,
                        'prodi_id' => $prodi->id,
                        'status_mahasiswa' => $statusMahasiswa,
                        'cuti_2' => 'no',
                    ]
                );

                // 3. Create Akademik Mahasiswa
                $semesterAktif = rand(1, 14); // Very varied
                $tahunMasuk = date('Y') - floor($semesterAktif / 2);
                if ($tahunMasuk > date('Y')) $tahunMasuk = date('Y');

                $ipk = rand(150, 400) / 100; // 1.50 to 4.00
                $sksLulus = rand(20, 150);
                if ($statusMahasiswa == 'lulus') $sksLulus = 144;

                $akademik = AkademikMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    [
                        'dosen_wali_id' => $dosenId,
                        'tahun_masuk' => $tahunMasuk,
                        'semester_aktif' => $semesterAktif,
                        'ipk' => $ipk,
                        'sks_lulus' => $sksLulus,
                        'sks_tempuh' => $sksLulus + rand(0, 10),
                        'mk_nasional' => $semesterAktif >= 4 ? 'yes' : 'no',
                        'mk_fakultas' => $semesterAktif >= 6 ? 'yes' : 'no',
                        'mk_prodi' => $semesterAktif >= 8 ? 'yes' : 'no',
                    ]
                );

                // 4. Create random KHS to trigger traits E, D, A
                $nilaiOptions = ['A', 'A', 'B', 'B', 'B', 'C', 'C', 'D', 'E']; // Varied weight
                $assigned_keys = array_rand($mks, 3);
                if (!is_array($assigned_keys)) $assigned_keys = [$assigned_keys];

                foreach ($assigned_keys as $key) {
                    $mk = $mks[$key];
                    $nilaiAkhir = $nilaiOptions[array_rand($nilaiOptions)];

                    KhsKrsMahasiswa::updateOrCreate(
                        ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $mk->id],
                        [
                            'kelompok_id' => $kelompokId,
                            'semester_ambil' => rand(1, $semesterAktif),
                            'status' => 'B',
                            'nilai_akhir_huruf' => $nilaiAkhir,
                        ]
                    );
                }

                // Add explicit E logic to see EWS triggers explicitly on scattered students
                if ($i % 4 == 0) { // Every 4th student gets an E to trigger kritis
                    KhsKrsMahasiswa::updateOrCreate(
                        ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $mks[0]->id],
                        [
                            'kelompok_id' => $kelompokId,
                            'semester_ambil' => $semesterAktif,
                            'status' => 'B',
                            'nilai_akhir_huruf' => 'E'
                        ]
                    );
                }

                // 5. Create IPS history
                IpsMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    [
                        'ips_1' => rand(200, 400)/100,
                        'ips_2' => $semesterAktif >= 2 ? rand(200, 400)/100 : null,
                        'ips_3' => $semesterAktif >= 3 ? rand(200, 400)/100 : null,
                        'ips_4' => $semesterAktif >= 4 ? rand(150, 400)/100 : null,
                        'ips_5' => $semesterAktif >= 5 ? rand(150, 300)/100 : null,
                        'ips_6' => $semesterAktif >= 6 ? rand(150, 400)/100 : null,
                    ]
                );

                // 6. Recalculate Status EWS for this student via EwsService
                $ewsService->updateStatus($akademik);
            }
        }
    }

    private function getRandomStatus() {
        $r = rand(1, 100);
        if ($r <= 70) return 'aktif';      // 70% aktif
        if ($r <= 80) return 'mangkir';    // 10% mangkir
        if ($r <= 90) return 'cuti';       // 10% cuti
        if ($r <= 95) return 'lulus';      // 5% lulus
        return 'do';                       // 5% DO
    }
}
