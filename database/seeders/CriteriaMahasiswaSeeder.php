<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\EarlyWarningSystem;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Models\KelompokMataKuliah;
use App\Models\TindakLanjut;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CriteriaMahasiswaSeeder extends Seeder
{
    /**
     * Helper to log messages safely
     */
    private function logInfo($message)
    {
        if (isset($this->command)) {
            $this->command->info($message);
        } else {
            echo "INFO: $message\n";
        }
    }

    private function logError($message)
    {
        if (isset($this->command)) {
            $this->command->error($message);
        } else {
            echo "ERROR: $message\n";
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodi = Prodi::first();
        if (!$prodi) {
            $this->logError('Prodi tidak ditemukan. Harap jalankan ProdiSeeder.');
            return;
        }

        $dosen = Dosen::first();
        if (!$dosen) {
            $this->logError('Dosen tidak ditemukan. Harap jalankan DosenSeeder.');
            return;
        }

        $this->logInfo('Memulai seeding data mahasiswa berdasarkan kriteria (Events Disabled)...');

        AkademikMahasiswa::withoutEvents(function () {
            // 1. IPK Rendah (< 2) & Semester 1-3
            $this->seedCriteria('IPK Rendah & Semester 1-3', 50, [
                'ipk' => 1.5,
                'semester_aktif' => rand(1, 3),
                'tahun_masuk' => 2024,
            ]);

            // 2. IPK Rendah (< 2) & Angkatan 2023
            $this->seedCriteria('IPK Rendah & Angkatan 2023', 50, [
                'ipk' => 1.7,
                'tahun_masuk' => 2023,
                'semester_aktif' => 4,
            ]);

            // 3. SKS Kurang (< 144) & Angkatan 2021
            $this->seedCriteria('SKS Kurang & Angkatan 2021', 50, [
                'sks_lulus' => 100,
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 4. MK Ulang & Angkatan 2021
            $this->seedCriteria('MK Ulang & Angkatan 2021', 50, [
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
                'with_repeats' => true,
            ]);

            // 5. MK Nasional & Angkatan 2021
            $this->seedCriteria('MK Nasional & Angkatan 2021', 50, [
                'mk_nasional' => 'yes',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 6. MK Fakultas & Angkatan 2021
            $this->seedCriteria('MK Fakultas & Angkatan 2021', 50, [
                'mk_fakultas' => 'yes',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 7. MK Prodi & Angkatan 2021
            $this->seedCriteria('MK Prodi & Angkatan 2021', 50, [
                'mk_prodi' => 'yes',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 8. Nilai D Melebihi Batas & Angkatan 2021
            $this->seedCriteria('Nilai D Melebihi Batas & Angkatan 2021', 50, [
                'nilai_d_melebihi_batas' => 'yes',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 9. Nilai E & Angkatan 2021
            $this->seedCriteria('Nilai E & Angkatan 2021', 50, [
                'nilai_e' => 'yes',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 10. Status Mahasiswa (Mangkir)
            $this->seedCriteria('Status Mahasiswa Mangkir', 50, [
                'status_mahasiswa' => 'mangkir',
                'tahun_masuk' => 2022,
                'semester_aktif' => 6,
            ]);

            // 11. Status EWS (Kritis)
            $this->seedCriteria('Status EWS Kritis', 50, [
                'status_ews' => 'kritis',
                'tahun_masuk' => 2020,
                'semester_aktif' => 10,
            ]);

            // 12. Status Kelulusan (Non-Eligible) & Angkatan 2021
            $this->seedCriteria('Status Kelulusan Non-Eligible & Angkatan 2021', 50, [
                'status_kelulusan' => 'noneligible',
                'tahun_masuk' => 2021,
                'semester_aktif' => 8,
            ]);

            // 13. Status Kelulusan (Eligible)
            $this->seedCriteria('Status Kelulusan Eligible', 50, [
                'status_kelulusan' => 'eligible',
                'tahun_masuk' => 2020,
                'semester_aktif' => 12,
                'ipk' => 3.5,
                'sks_lulus' => 144,
                'mk_nasional' => 'no',
                'mk_fakultas' => 'no',
                'mk_prodi' => 'no',
                'nilai_d_melebihi_batas' => 'no',
                'nilai_e' => 'no',
            ]);
        });

        $this->logInfo('Seeding selesai!');
    }

    private function seedCriteria(string $label, int $count, array $overrides): void
    {
        $this->logInfo("Seeding $label...");
        
        $dosen = Dosen::first();
        $mataKuliahs = MataKuliah::all();
        $randomKelompok = KelompokMataKuliah::first();

        for ($i = 0; $i < $count; $i++) {
            try {
                // $this->logInfo("Processing $i...");
                $uniqueInt = rand(10000000, 99999999);
                $tahunMasuk = $overrides['tahun_masuk'] ?? 2023;
                $nim = 'TEST.' . $tahunMasuk . '.' . $uniqueInt;
                $email = "test.{$uniqueInt}@ews.com";
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Student $label $i",
                        'password' => Hash::make('password'),
                    ]
                );
                
                if (!$user->wasRecentlyCreated && !$user->hasRole('mahasiswa')) {
                    $user->syncRoles(['mahasiswa']);
                } elseif ($user->wasRecentlyCreated) {
                    $user->assignRole('mahasiswa');
                }

                $mahasiswa = Mahasiswa::updateOrCreate(
                    ['nim' => $nim],
                    [
                        'user_id' => $user->id,
                        'status_mahasiswa' => $overrides['status_mahasiswa'] ?? 'aktif',
                        'telepon' => '08' . rand(111111111, 999999999),
                        'minat' => 'RPL',
                    ]
                );

                $akademik = AkademikMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    [
                        'dosen_wali_id' => $dosen->id,
                        'semester_aktif' => $overrides['semester_aktif'] ?? 2,
                        'tahun_masuk' => $tahunMasuk,
                        'ipk' => $overrides['ipk'] ?? 3.0,
                        'sks_lulus' => $overrides['sks_lulus'] ?? 40,
                        'sks_tempuh' => ($overrides['sks_lulus'] ?? 40) + 20,
                        'sks_now' => 20,
                        'sks_gagal' => 0,
                        'mk_nasional' => $overrides['mk_nasional'] ?? 'no',
                        'mk_fakultas' => $overrides['mk_fakultas'] ?? 'no',
                        'mk_prodi' => $overrides['mk_prodi'] ?? 'no',
                        'nilai_d_melebihi_batas' => $overrides['nilai_d_melebihi_batas'] ?? 'no',
                        'nilai_e' => $overrides['nilai_e'] ?? 'no',
                    ]
                );

                $ews = EarlyWarningSystem::updateOrCreate(
                    ['akademik_mahasiswa_id' => $akademik->id],
                    [
                        'status' => $overrides['status_ews'] ?? 'normal',
                        'status_kelulusan' => $overrides['status_kelulusan'] ?? 'noneligible',
                    ]
                );

                // Create/Update TindakLanjut record
                TindakLanjut::updateOrCreate(
                    ['id_ews' => $ews->id],
                    [
                        'kategori' => 'rekomitmen',
                        'status' => 'belum_diverifikasi',
                        'tanggal_pengajuan' => now(),
                    ]
                );

                // Add Ips details
                $ipsData = [];
                for ($s = 1; $s <= 14; $s++) {
                    if ($s <= ($overrides['semester_aktif'] ?? 2)) {
                        $ipsData["ips_$s"] = $overrides['ipk'] ?? 3.0;
                    }
                }
                IpsMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    $ipsData
                );

                // Add MK Ulang if requested
                if (($overrides['with_repeats'] ?? false) && $randomKelompok && $mataKuliahs->isNotEmpty()) {
                    $mk = $mataKuliahs->random();
                    KhsKrsMahasiswa::create([
                        'mahasiswa_id' => $mahasiswa->id,
                        'matakuliah_id' => $mk->id,
                        'kelompok_id' => $randomKelompok->id,
                        'status' => 'B',
                        'nilai_akhir_huruf' => 'E',
                        'absen' => 100,
                        'nilai_uts' => 50,
                        'nilai_uas' => 50,
                        'nilai_akhir_angka' => 50,
                    ]);
                    KhsKrsMahasiswa::create([
                        'mahasiswa_id' => $mahasiswa->id,
                        'matakuliah_id' => $mk->id,
                        'kelompok_id' => $randomKelompok->id,
                        'status' => 'U',
                        'nilai_akhir_huruf' => 'C',
                        'absen' => 100,
                        'nilai_uts' => 60,
                        'nilai_uas' => 60,
                        'nilai_akhir_angka' => 60,
                    ]);
                }
            } catch (\Exception $e) {
                $this->logError("Gagal pada kriteria $label index $i: " . $e->getMessage());
                throw $e; // Rethrow to see full stack trace
            }
        }
    }
}
