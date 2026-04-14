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
     * Generates 75 varied students per year per Prodi for EWS testing.
     */
    public function run(): void
    {
        $prodis = Prodi::all();
        $ewsService = app(EwsService::class);

        $tahunMasukList = [2020, 2021, 2022, 2023, 2024, 2025];
        $targetPerTahun = 75;

        // Ensure roles are assigned faster by getting the models
        $mahasiswaRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'mahasiswa']);

        foreach ($prodis as $prodi) {
            $this->command->info("Seeding Mahasiswa for Prodi: {$prodi->kode_prodi} - {$prodi->nama}");

            $dosenId = \App\Models\Dosen::where('prodi_id', $prodi->id)->first()?->id;

            // Jika dosen tidak ada untuk prodi ini, lewati mahasiswa untuk prodi ini agar tidak amburadul
            if (!$dosenId) {
                $this->command->warn("  ⚠ Dosen untuk prodi {$prodi->kode_prodi} kosong. Melewati seeding prodi ini.");
                continue;
            }

            // Ambil Mata Kuliah yang sudah disiapkan oleh MataKuliahSeeder
            $mks = MataKuliah::where('prodi_id', $prodi->id)->get();

            // Jika untuk suatu alasan kosong, tetap beri penanganan sementara
            if ($mks->isEmpty()) {
                $dummyMk = MataKuliah::firstOrCreate(
                    ['kode' => $prodi->kode_prodi . '.DUMMY.1'],
                    [
                        'prodi_id' => $prodi->id,
                        'name' => "Pemrograman Dasar {$prodi->kode_prodi}",
                        'sks' => 3,
                        'semester' => 1,
                        'tipe_mk' => 'prodi',
                    ]
                );
                
                $mks = collect([$dummyMk]);
                
                // Assign a generic kelompok
                \App\Models\KelompokMataKuliah::firstOrCreate([
                    'mata_kuliah_id' => $dummyMk->id,
                    'kode' => 'A'
                ], ['dosen_pengampu_id' => $dosenId]);
            }

            foreach ($tahunMasukList as $tahun) {
                $this->command->info("  - Tahun Masuk: {$tahun}");

                $currentYear = (int)date('Y');
                $diffYear = max(0, $currentYear - $tahun);
                // Assume 2 semesters per year.
                $baseSemesterAktif = ($diffYear * 2) + 1;

                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    for ($i = 100; $i <= (100 + $targetPerTahun); $i++) {
                        $nim = $prodi->kode_prodi . '.' . $tahun . '.' . str_pad($i, 5, '0', STR_PAD_LEFT);
                        $email = "{$nim}@ews.com";

                        // 1. Create User
                        $user = User::firstOrCreate(
                            ['email' => $email],
                            [
                                'name' => "MHS {$prodi->kode_prodi} {$tahun} {$i}",
                                'password' => Hash::make('password'),
                                'prodi_id' => $prodi->id,
                            ]
                        );

                        if (!$user->hasRole('mahasiswa')) {
                            $user->assignRole($mahasiswaRole);
                        }

                        // 2. Create Mahasiswa Profile
                        $statusMahasiswa = $this->getRandomStatus($baseSemesterAktif);

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
                        // Randomize slightly the semester aktif
                        $semesterAktif = max(1, $baseSemesterAktif + rand(-1, 1));

                        $ipk = rand(150, 400) / 100; // 1.50 to 4.00
                        $sksLulus = rand(20, 150);
                        if ($statusMahasiswa == 'lulus') $sksLulus = max(144, $sksLulus);

                        $akademik = AkademikMahasiswa::updateOrCreate(
                            ['mahasiswa_id' => $mahasiswa->id],
                            [
                                'dosen_wali_id' => $dosenId,
                                'tahun_masuk' => $tahun,
                                'semester_aktif' => $semesterAktif,
                                'ipk' => $ipk,
                                'sks_lulus' => $sksLulus,
                                'sks_tempuh' => $sksLulus + rand(0, 15),
                                'mk_nasional' => $semesterAktif >= 4 ? 'yes' : 'no',
                                'mk_fakultas' => $semesterAktif >= 5 ? 'yes' : 'no',
                                'mk_prodi' => $semesterAktif >= 7 ? 'yes' : 'no',
                            ]
                        );

                        // 4. Create random KHS
                        $nilaiOptions = ['A', 'A', 'B', 'B', 'B', 'C', 'C', 'D', 'E'];
                        $mkCount = $mks->count();
                        $takeAmount = min($mkCount, rand(3, 6)); // 3 to 6 matakuliah

                        $randomMks = $takeAmount > 0 ? $mks->random($takeAmount) : collect([]);

                        foreach ($randomMks as $mk) {
                            $nilaiAkhir = $nilaiOptions[array_rand($nilaiOptions)];

                            // Get kelompok specifically for this dosen/mk
                            $kelompok = \App\Models\KelompokMataKuliah::where('mata_kuliah_id', $mk->id)->first();
                            $kId = $kelompok ? $kelompok->id : null;

                            KhsKrsMahasiswa::updateOrCreate(
                                ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $mk->id],
                                [
                                    'kelompok_id' => $kId,
                                    'semester_ambil' => rand(1, max(1, $semesterAktif)),
                                    'status' => 'B',
                                    'nilai_akhir_huruf' => $nilaiAkhir,
                                ]
                            );
                        }

                        // Trigger E for someone randomly to make EWS more active
                        if ($mkCount > 0 && rand(1, 100) <= 25) { // 25% chance
                            $firstMk = $mks->first();
                            $kelompok = \App\Models\KelompokMataKuliah::where('mata_kuliah_id', $firstMk->id)->first();
                            $kId = $kelompok ? $kelompok->id : null;

                            KhsKrsMahasiswa::updateOrCreate(
                                ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $firstMk->id],
                                [
                                    'kelompok_id' => $kId,
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
                                'ips_3' => $semesterAktif >= 3 ? rand(150, 400)/100 : null,
                                'ips_4' => $semesterAktif >= 4 ? rand(150, 400)/100 : null,
                                'ips_5' => $semesterAktif >= 5 ? rand(150, 300)/100 : null,
                                'ips_6' => $semesterAktif >= 6 ? rand(150, 400)/100 : null,
                            ]
                        );

                        // 6. Recalculate Status EWS for this student via EwsService
                        $ewsService->updateStatus($akademik);
                    }
                    \Illuminate\Support\Facades\DB::commit();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->command->error("Gagal seeding tahun {$tahun}: " . $e->getMessage());
                }
            }
        }
    }

    private function getRandomStatus($semester) {
        $r = rand(1, 100);
        if ($semester <= 6) {
            if ($r <= 85) return 'aktif';
            if ($r <= 95) return 'cuti';
            return 'mangkir';
        } else {
            if ($r <= 60) return 'aktif';
            if ($r <= 80) return 'lulus';
            if ($r <= 85) return 'cuti';
            if ($r <= 95) return 'mangkir';
            return 'do';
        }
    }
}
