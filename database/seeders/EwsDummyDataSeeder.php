<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Models\MataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Dosen;
use App\Models\KelompokMataKuliah;

class EwsDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates varied students per year per Prodi for EWS testing.
     *
     * Usage:
     *   php artisan db:seed --class=EwsDummyDataSeeder              # Full seeding
     *   php artisan db:seed --class=EwsDummyDataSeeder --limit=10   # Quick test with 10 students per year
     */
    public function run(): void
    {
        $limit = ($this->command->hasOption('limit') && $this->command->option('limit') !== null)
            ? (int) $this->command->option('limit')
            : 0;

        $prodis = Prodi::all();
        $tahunMasukList = [2020, 2021, 2022, 2023, 2024, 2025];
        $targetPerTahun = $limit > 0 ? $limit : 75;

        $this->command->info("Seeding EWS Dummy Data...");
        $this->command->info("Target: {$targetPerTahun} students per year");
        $this->command->info("Years: " . implode(', ', $tahunMasukList));
        $this->command->info("Prodis: " . $prodis->count());
        $this->command->info("");

        // Ensure roles are assigned faster by getting the models
        $mahasiswaRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'mahasiswa']);

        // Pre-load all dosens to avoid N+1 queries
        $dosenByProdi = Dosen::where('prodi_id', '!=', null)->get()->groupBy('prodi_id');

        // Pre-load all MK to avoid N+1 queries
        $mkByProdi = MataKuliah::where('prodi_id', '!=', null)->get()->groupBy('prodi_id');

        // Pre-fetch or create dummy MK if needed
        foreach ($prodis as $prodi) {
            $mks = $mkByProdi->get($prodi->id, collect());
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

                $dosenId = $dosenByProdi->get($prodi->id, collect())->first()?->id;
                if ($dosenId) {
                    KelompokMataKuliah::firstOrCreate(
                        ['mata_kuliah_id' => $dummyMk->id, 'kode' => 'A'],
                        ['dosen_pengampu_id' => $dosenId]
                    );
                }
            }
        }

        // Re-fetch MK after potential dummy creation
        $mkByProdi = MataKuliah::where('prodi_id', '!=', null)->get()->groupBy('prodi_id');

        $totalStudents = 0;
        $startTime = microtime(true);

        foreach ($prodis as $prodi) {
            $dosenId = $dosenByProdi->get($prodi->id, collect())->first()?->id;

            if (!$dosenId) {
                $this->command->warn("  ⚠ Dosen untuk prodi {$prodi->kode_prodi} kosong. Melewati seeding prodi ini.");
                continue;
            }

            $mks = $mkByProdi->get($prodi->id, collect());
            $mkArray = $mks->values()->all();
            $mkCount = count($mkArray);

            $this->command->info("Prodi: {$prodi->kode_prodi} - {$prodi->nama}");

            foreach ($tahunMasukList as $tahun) {
                $currentYear = (int)date('Y');
                $diffYear = max(0, $currentYear - $tahun);
                $baseSemesterAktif = ($diffYear * 2) + 1;

                // Batch data collection for bulk insert
                $usersToInsert = [];
                $mahasiswasToInsert = [];
                $akademiksToInsert = [];
                $khsToInsert = [];
                $ewsToInsert = [];

                for ($i = 100; $i <= (100 + $targetPerTahun); $i++) {
                    $nim = $prodi->kode_prodi . '.' . $tahun . '.' . str_pad($i, 5, '0', STR_PAD_LEFT);
                    $email = "{$nim}@ews.com";
                    $statusMahasiswa = $this->getRandomStatus($baseSemesterAktif);
                    $semesterAktif = max(1, $baseSemesterAktif + rand(-1, 1));
                    $ipk = rand(150, 400) / 100;
                    $sksLulus = rand(20, 150);
                    if ($statusMahasiswa == 'lulus') $sksLulus = max(144, $sksLulus);

                    // Skip if user already exists
                    $existingUser = User::where('email', $email)->first();
                    if ($existingUser) {
                        continue;
                    }

                    // Collect User data
                    $usersToInsert[] = [
                        'name' => "MHS {$prodi->kode_prodi} {$tahun} {$i}",
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'prodi_id' => $prodi->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Bulk insert users
                if (!empty($usersToInsert)) {
                    DB::table('users')->insert($usersToInsert);
                }

                // Get inserted user IDs
                $insertedEmails = array_column($usersToInsert, 'email');
                $users = User::whereIn('email', $insertedEmails)->get();

                foreach ($users as $user) {
                    // Parse user name to extract tahun
                    preg_match('/MHS\s+(\S+)\s+(\d{4})\s+(\d+)/', $user->name, $matches);
                    $tahunMhs = (int)($matches[2] ?? $tahun);
                    $semesterAktif = max(1, (max(0, (int)date('Y') - $tahunMhs) * 2 + 1) + rand(-1, 1));
                    $statusMahasiswa = $this->getRandomStatus($semesterAktif);

                    // Assign role
                    if (!$user->hasRole('mahasiswa')) {
                        $user->assignRole($mahasiswaRole);
                    }

                    // Collect Mahasiswa data only (NOT akademik yet — need real mahasiswa.id first)
                    $mahasiswasToInsert[] = [
                        'user_id'          => $user->id,
                        'nim'              => str_replace('@ews.com', '', $user->email),
                        'prodi_id'         => $prodi->id,
                        'status_mahasiswa' => $statusMahasiswa,
                        'cuti_2'           => 'no',
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }

                // Bulk insert mahasiswa
                if (!empty($mahasiswasToInsert)) {
                    DB::table('mahasiswa')->insert($mahasiswasToInsert);
                }

                // Fetch real mahasiswa.id — keyed by user_id for easy lookup
                $insertedMahasiswas = Mahasiswa::whereIn('user_id', $users->pluck('id'))
                    ->get()
                    ->keyBy('user_id');

                // Now build akademik data using correct mahasiswa.id
                foreach ($users as $user) {
                    $mahasiswa = $insertedMahasiswas->get($user->id);
                    if (!$mahasiswa) continue;

                    preg_match('/MHS\s+(\S+)\s+(\d{4})\s+(\d+)/', $user->name, $matches);
                    $tahunMhs    = (int)($matches[2] ?? $tahun);
                    $semesterAktif = max(1, (max(0, (int)date('Y') - $tahunMhs) * 2 + 1) + rand(-1, 1));
                    $ipk         = rand(150, 400) / 100;
                    $sksLulus    = rand(20, 150);
                    if ($mahasiswa->status_mahasiswa === 'lulus') $sksLulus = max(144, $sksLulus);

                    $mkNasional = $semesterAktif >= 4 ? 'yes' : 'no';
                    $mkFakultas = $semesterAktif >= 5 ? 'yes' : 'no';
                    $mkProdi    = $semesterAktif >= 7 ? 'yes' : 'no';

                    $akademiksToInsert[] = [
                        'mahasiswa_id'  => $mahasiswa->id,   // ← real mahasiswa.id
                        'dosen_wali_id' => $dosenId,
                        'tahun_masuk'   => $tahunMhs,
                        'semester_aktif'=> $semesterAktif,
                        'ipk'           => $ipk,
                        'sks_lulus'     => $sksLulus,
                        'sks_tempuh'    => $sksLulus + rand(0, 15),
                        'mk_nasional'   => $mkNasional,
                        'mk_fakultas'   => $mkFakultas,
                        'mk_prodi'      => $mkProdi,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                // Bulk insert akademik_mahasiswa
                if (!empty($akademiksToInsert)) {
                    DB::table('akademik_mahasiswa')->insert($akademiksToInsert);
                }

                // Get inserted akademik records using real mahasiswa IDs
                $akademiks = AkademikMahasiswa::whereIn('mahasiswa_id', $insertedMahasiswas->pluck('id'))->get();


                foreach ($akademiks as $akademik) {
                    $ips1 = $akademik->semester_aktif >= 1 ? rand(200, 400)/100 : null;
                    $ips2 = $akademik->semester_aktif >= 2 ? rand(200, 400)/100 : null;
                    $ips3 = $akademik->semester_aktif >= 3 ? rand(150, 400)/100 : null;

                    // Update akademik with ips_semester fields
                    $akademik->update([
                        'ips_semester_1' => $ips1,
                        'ips_semester_2' => $ips2,
                        'ips_semester_3' => $ips3,
                    ]);

                    // Calculate EWS status directly without expensive queries
                    $sksLulus = $akademik->sks_lulus ?? 0;
                    $semesterAktif = $akademik->semester_aktif ?? 1;
                    $sisaSks = max(0, 144 - $sksLulus);

                    // Simple status calculation for seeder
                    $status = 'normal';
                    if ($sksLulus >= 144) {
                        $status = $semesterAktif <= 8 ? 'tepat_waktu' : ($semesterAktif <= 10 ? 'normal' : ($semesterAktif <= 14 ? 'perhatian' : 'kritis'));
                    } else {
                        $sksBisaDiambilSD14 = $this->hitungSksMaksBisaDiambil($semesterAktif, 14);
                        $sksBisaDiambilSD10 = $this->hitungSksMaksBisaDiambil($semesterAktif, 10);
                        $sksBisaDiambilSD8 = $this->hitungSksMaksBisaDiambil($semesterAktif, 8);

                        if ($sisaSks > $sksBisaDiambilSD14) {
                            $status = 'kritis';
                        } elseif ($sisaSks > $sksBisaDiambilSD10) {
                            $status = 'perhatian';
                        } elseif ($sisaSks > $sksBisaDiambilSD8) {
                            $status = 'normal';
                        } else {
                            $status = 'tepat_waktu';
                        }
                    }

                    // SPS fields (uppercase to match DB)
                    $sps1 = ($ips1 !== null && $ips1 < 2.0) ? 'yes' : 'no';
                    $sps2 = ($ips2 !== null && $ips2 < 2.0) ? 'yes' : 'no';
                    $sps3 = ($ips3 !== null && $ips3 < 2.0) ? 'yes' : 'no';

                    // Collect EWS data
                    $ewsToInsert[] = [
                        'akademik_mahasiswa_id' => $akademik->id,
                        'status' => $status,
                        'status_kelulusan' => ($akademik->ipk > 2.0 && $sksLulus >= 144) ? 'eligible' : 'noneligible',
                        'SPS1' => $sps1,
                        'SPS2' => $sps2,
                        'SPS3' => $sps3,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // KHS data
                    $nilaiOptions = ['A', 'A', 'B', 'B', 'B', 'C', 'C', 'D', 'E'];
                    $mksForStudent = $mkArray;
                    $mkCountLocal = count($mksForStudent);
                    $takeAmount = min($mkCountLocal, rand(3, 6));

                    if ($takeAmount > 0) {
                        $randomKeys = array_rand($mksForStudent, $takeAmount);
                        if (!is_array($randomKeys)) {
                            $randomKeys = [$randomKeys];
                        }

                        foreach ($randomKeys as $key) {
                            $mk = $mksForStudent[$key];
                            $kelompok = KelompokMataKuliah::where('mata_kuliah_id', $mk->id)->first();
                            $kId = $kelompok?->id;

                            $khsToInsert[] = [
                                'mahasiswa_id' => $akademik->mahasiswa_id,
                                'matakuliah_id' => $mk->id,
                                'kelompok_id' => $kId,
                                'semester_ambil' => rand(1, max(1, $semesterAktif)),
                                'status' => 'B',
                                'nilai_akhir_huruf' => $nilaiOptions[array_rand($nilaiOptions)],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                // Bulk insert KHS
                if (!empty($khsToInsert)) {
                    DB::table('khs_krs_mahasiswa')->insert($khsToInsert);
                }

                // Bulk insert EWS
                if (!empty($ewsToInsert)) {
                    DB::table('early_warning_system')->insert($ewsToInsert);
                }

                $totalStudents += count($users);
                $this->command->info("  - Tahun {$tahun}: " . count($users) . " mahasiswa");
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->command->info("");
        $this->command->info("Seeding completed!");
        $this->command->info("Total students: {$totalStudents}");
        $this->command->info("Time elapsed: {$elapsed} seconds");
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

    private function hitungSksMaksBisaDiambil($semesterSekarang, $semesterTarget)
    {
        if ($semesterSekarang > $semesterTarget) {
            return 0;
        }

        $totalSks = 0;
        for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
            if ($smt <= 10) {
                $totalSks += 20;
            } else {
                $totalSks += 24;
            }
        }

        return $totalSks;
    }
}
