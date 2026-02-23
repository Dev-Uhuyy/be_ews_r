<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\AkademikMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\MataKuliah;
use App\Models\KelompokMataKuliah;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\EarlyWarningSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyMahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodi = Prodi::first();

        if (!$prodi) {
            $this->command->error('Prodi tidak ditemukan. Jalankan ProdiSeeder terlebih dahulu.');
            return;
        }

        // Gunakan dosen ID 1
        $dosenUtama = Dosen::find(1);

        if (!$dosenUtama) {
            $this->command->error('Dosen dengan ID 1 tidak ditemukan.');
            return;
        }

        $this->command->info("Menggunakan Dosen ID 1: {$dosenUtama->user->name}");
        $dosens = [1]; // Gunakan dosen ID 1

        // Load mata kuliah SEKALI di awal (bukan di loop)
        $mataKuliahs = MataKuliah::with('kelompok_mata_kuliah')->get();
        if ($mataKuliahs->isEmpty()) {
            $this->command->info('Mata kuliah belum ada, menjalankan MataKuliahSeeder...');
            $this->call(MataKuliahSeeder::class);
            $this->call(KelompokMataKuliahSeeder::class);
            $mataKuliahs = MataKuliah::with('kelompok_mata_kuliah')->get();
        }

        // Disable observer saat seeding untuk performa
        // Observer akan trigger EwsService yang query KHS lagi (double work)
        AkademikMahasiswa::withoutEvents(function() use ($dosens, $mataKuliahs) {
            $this->generateMahasiswa($dosens, $mataKuliahs);
        });

        $this->command->info('Selesai generate mahasiswa!');
    }

    private function generateMahasiswa($dosens, $mataKuliahs)
    {

        // 10 angkatan (2015-2024), masing-masing 5 mahasiswa
        $angkatans = range(2015, 2024);
        $mahasiswaCounter = 2000; // Start ID dari 2000

        foreach ($angkatans as $tahunMasuk) {
            $this->command->info("Generating mahasiswa angkatan $tahunMasuk...");

            for ($i = 1; $i <= 5; $i++) {
                $mahasiswaCounter++;

                // 1. Create User
                $email = "mahasiswa{$tahunMasuk}{$i}@ews.com";
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Mahasiswa {$tahunMasuk}-{$i}",
                        'password' => Hash::make('password'),
                    ]
                );
                if (!$user->hasRole('mahasiswa')) {
                    $user->assignRole('mahasiswa');
                }

                // 2. Create Mahasiswa
                $nim = "A11.{$tahunMasuk}." . str_pad($i, 5, '0', STR_PAD_LEFT);
                $mahasiswa = Mahasiswa::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nim' => $nim,
                        'status_mahasiswa' => $this->getStatusMahasiswa($tahunMasuk),
                        'telepon' => '08' . rand(1000000000, 9999999999),
                        'minat' => $this->getRandomMinat(),
                    ]
                );

                // 3. Create AkademikMahasiswa
                $semesterAktif = $this->getSemesterAktif($tahunMasuk);
                $ipk = $this->generateIPK($tahunMasuk);

                // Buat akademik mahasiswa dulu dengan nilai temporary
                $akademikMahasiswa = AkademikMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    [
                        'dosen_wali_id' => $dosens[array_rand($dosens)],
                        'semester_aktif' => $semesterAktif,
                        'tahun_masuk' => $tahunMasuk,
                        'ipk' => $ipk,
                        'mk_nasional' => 'no',
                        'mk_fakultas' => 'no',
                        'mk_prodi' => 'no',
                        'sks_tempuh' => 0,
                        'sks_now' => 0,
                        'sks_lulus' => 0,
                        'sks_gagal' => 0,
                    ]
                );

                // 3b. Generate KHS/KRS terlebih dahulu
                $this->createKhsKrs($mahasiswa->id, $semesterAktif, $mataKuliahs);

                // 3c. Hitung SKS dari KHS/KRS yang sudah dibuat
                $khsRecords = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                    ->with('mata_kuliah')
                    ->get();

                $sksLulus = 0;
                $sksGagal = 0;
                $sksNow = 0;
                $adaNilaiD = false;
                $adaNilaiE = false;
                $totalSksNilaiD = 0; // Total SKS dengan nilai D

                // Hitung dari KHS/KRS yang paling baru per mata kuliah
                // Jika ada retake (status 'U'), ambil nilai terakhir berdasarkan ID terbesar
                $latestKhs = [];
                foreach ($khsRecords as $khs) {
                    $matkulId = $khs->matakuliah_id;

                    // Simpan KHS terakhir untuk setiap mata kuliah (berdasarkan ID terbesar)
                    if (!isset($latestKhs[$matkulId]) || $khs->id > $latestKhs[$matkulId]->id) {
                        $latestKhs[$matkulId] = $khs;
                    }
                }

                // Hitung SKS dan track D/E HANYA dari nilai terakhir setiap mata kuliah
                foreach ($latestKhs as $khs) {
                    $sksMatkul = $khs->mata_kuliah->sks ?? 3; // Default 3 SKS jika tidak ada

                    // Track nilai D dan E dari nilai TERAKHIR saja
                    if ($khs->nilai_akhir_huruf === 'D') {
                        $adaNilaiD = true;
                        $totalSksNilaiD += $sksMatkul; // Akumulasi SKS nilai D
                    } elseif ($khs->nilai_akhir_huruf === 'E') {
                        $adaNilaiE = true;
                    }

                    // Hitung berdasarkan nilai terakhir
                    // D termasuk SKS lulus (karena nilai D adalah lulus dengan syarat max 5%)
                    if ($khs->nilai_akhir_huruf === 'E') {
                        $sksGagal += $sksMatkul;
                    } elseif (in_array($khs->nilai_akhir_huruf, ['A', 'AB', 'B', 'BC', 'C', 'D'])) {
                        $sksLulus += $sksMatkul;
                    }
                }

                // SKS semester ini (18-24) - ini input manual
                $sksNow = rand(18, 24);
                $sksTempuh = $sksLulus + $sksGagal + $sksNow;

                // Batasi maksimal 155
                if ($sksTempuh > 150) {
                    $sksTempuh = 150;
                    $sksLulus = min($sksLulus, 150 - $sksGagal - $sksNow);
                }

                // Hitung syarat kelulusan MK (berdasarkan semester)
                $mkNasional = ($semesterAktif >= 4) ? 'yes' : 'no';
                $mkFakultas = ($semesterAktif >= 6) ? 'yes' : 'no';
                $mkProdi = ($sksLulus >= 120) ? 'yes' : 'no';

                // Cek apakah nilai D melebihi batas 5%
                $maxSksNilaiD = $sksLulus * 0.05;
                $nilaiDMelebihiBatas = $totalSksNilaiD > $maxSksNilaiD;

                // Update akademik mahasiswa dengan nilai real dan nilai_d_melebihi_batas/nilai_e
                $akademikMahasiswa->update([
                    'mk_nasional' => $mkNasional,
                    'mk_fakultas' => $mkFakultas,
                    'mk_prodi' => $mkProdi,
                    'sks_tempuh' => $sksTempuh,
                    'sks_now' => $sksNow,
                    'sks_lulus' => $sksLulus,
                    'sks_gagal' => $sksGagal,
                    'nilai_d_melebihi_batas' => $nilaiDMelebihiBatas ? 'yes' : 'no',
                    'nilai_e' => $adaNilaiE ? 'yes' : 'no',
                ]);

                // 3b. Create EarlyWarningSystem untuk akademik mahasiswa
                $nilaiDMelebihiBatasValue = $nilaiDMelebihiBatas ? 'yes' : 'no';
                $nilaiEValue = $adaNilaiE ? 'yes' : 'no';
                $statusKelulusan = $this->hitungStatusKelulusan($ipk, $sksLulus, $mkNasional, $mkFakultas, $mkProdi, $nilaiDMelebihiBatasValue, $nilaiEValue);
                $status = $this->hitungStatus($sksLulus, $semesterAktif);

                EarlyWarningSystem::updateOrCreate(
                    ['akademik_mahasiswa_id' => $akademikMahasiswa->id],
                    [
                        'status' => $status,
                        'status_kelulusan' => $statusKelulusan,
                        'status_rekomitmen' => 'belum diverifikasi',
                        'link_rekomitmen' => null,
                    ]
                );

                // 4. Create IpsMahasiswa
                $ipsData = [];
                for ($sem = 1; $sem <= min($semesterAktif, 14); $sem++) {
                    $ipsData["ips_$sem"] = $this->generateIPS($tahunMasuk, $sem);
                }
                IpsMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    $ipsData
                );
            }
        }

    }

    private function getStatusMahasiswa($tahunMasuk)
    {
        $tahunSekarang = 2026;
        $lamaStudi = $tahunSekarang - $tahunMasuk;

        if ($lamaStudi > 8) return 'Lulus';
        if ($lamaStudi > 6) return rand(0, 1) ? 'Aktif' : 'Lulus';
        if ($lamaStudi > 4) return 'Aktif';
        return 'Aktif';
    }

    private function getSemesterAktif($tahunMasuk)
    {
        $tahunSekarang = 2026;
        $semester = ($tahunSekarang - $tahunMasuk) * 2;
        return min($semester, 14);
    }

    private function generateIPK($tahunMasuk)
    {
        // IPK berbeda berdasarkan angkatan (older = higher chance of better IPK)
        $tahunSekarang = 2026;
        $lamaStudi = $tahunSekarang - $tahunMasuk;

        if ($lamaStudi > 6) {
            // Angkatan lama, IPK lebih stabil
            return round(rand(280, 380) / 100, 2);
        } else {
            // Angkatan muda, IPK bervariasi
            return round(rand(250, 390) / 100, 2);
        }
    }

    private function generateIPS($tahunMasuk, $semester)
    {
        // IPS cenderung naik seiring semester
        $baseIps = rand(250, 350);
        $improvement = $semester * 5; // Improvement per semester
        $ips = $baseIps + $improvement;

        // Cap at 4.00
        $ips = min($ips, 400);

        // Random variation
        $ips += rand(-20, 20);

        return round($ips / 100, 2);
    }

    private function getRandomMinat()
    {
        $minats = ['RPL', 'Data Science', 'Cyber Security', 'AI', 'Mobile Development'];
        return $minats[array_rand($minats)];
    }

    private function hitungStatusKelulusan($ipk, $sksLulus, $mkNasional, $mkFakultas, $mkProdi, $nilaiDMelebihiBatas, $nilaiE)
    {
        // Eligible jika IPK > 2.0, SKS lulus >= 144, semua MK selesai, nilai D tidak melebihi 5%, dan TIDAK ada nilai E
        $ipkMemenuhi = $ipk > 2.0;
        $sksMemenuhi = $sksLulus >= 144;
        $mkSelesai = ($mkNasional === 'yes' && $mkFakultas === 'yes' && $mkProdi === 'yes');
        $nilaiDTidakMelebihiBatas = ($nilaiDMelebihiBatas === 'no');
        $tidakAdaNilaiE = ($nilaiE === 'no');

        if ($ipkMemenuhi && $sksMemenuhi && $mkSelesai && $nilaiDTidakMelebihiBatas && $tidakAdaNilaiE) {
            return 'eligible';
        }
        return 'noneligible';
    }

    private function hitungStatus($sksLulus, $semesterAktif)
    {
        $sisaSks = 144 - $sksLulus;

        // Simplified logic dari Python
        // Hitung SKS max yang bisa diambil
        $sksBisaDiambil = 0;
        for ($s = $semesterAktif; $s <= 14; $s++) {
            $sksBisaDiambil += ($s <= 10) ? 20 : 24;
        }

        // MERAH -> kritis: Tidak bisa lulus dalam 7 tahun (14 semester)
        if ($sisaSks > $sksBisaDiambil) {
            return 'kritis';
        }

        // Hitung untuk KUNING (10 semester)
        $sksBisaDiambilSD10 = 0;
        for ($s = $semesterAktif; $s <= 10; $s++) {
            $sksBisaDiambilSD10 += 20;
        }
        // KUNING -> perhatian: Tidak bisa lulus 5 tahun
        if ($sisaSks > $sksBisaDiambilSD10) {
            return 'perhatian';
        }

        // Hitung untuk HIJAU (8 semester)
        $sksBisaDiambilSD8 = 0;
        for ($s = $semesterAktif; $s <= 8; $s++) {
            $sksBisaDiambilSD8 += 20;
        }
        // HIJAU -> normal: Tidak bisa lulus 4 tahun
        if ($sisaSks > $sksBisaDiambilSD8) {
            return 'normal';
        }

        // BIRU -> tepat_waktu: Bisa lulus 4 tahun (8 semester) dengan kondisi bagus
        if ($semesterAktif >= 7 && $sisaSks <= $sksBisaDiambilSD8) {
            return 'tepat_waktu';
        }

        return 'normal';
    }

    private function createKhsKrs($mahasiswaId, $semesterAktif, $mataKuliahs)
    {

        $totalSksLulus = 0;
        $targetSks = 144; // Target SKS lulus
        $matkulGagal = []; // Track mata kuliah yang gagal untuk di-retake

        // Generate KHS/KRS untuk setiap semester yang sudah ditempuh
        for ($semester = 1; $semester < $semesterAktif; $semester++) {
            // Ambil mata kuliah sesuai semester (5-7 mata kuliah per semester)
            $matkulsInSemester = $mataKuliahs->where('semester', $semester)->shuffle()->take(rand(5, 7));

            // Jika tidak ada mata kuliah di semester ini, ambil dari semester lain
            if ($matkulsInSemester->isEmpty()) {
                $matkulsInSemester = $mataKuliahs->shuffle()->take(rand(5, 7));
            }

            foreach ($matkulsInSemester as $matkul) {
                // Stop jika sudah mencapai target SKS
                if ($totalSksLulus >= $targetSks) {
                    break 2; // Break dari nested loop
                }

                // Ambil kelompok random jika ada
                $kelompok = $matkul->kelompok_mata_kuliah->first();

                if (!$kelompok) continue;

                // Generate nilai (80% kemungkinan lulus)
                $lulus = rand(1, 100) <= 80;

                if ($lulus) {
                    $nilaiUts = rand(65, 100);
                    $nilaiUas = rand(65, 100);
                    $absen = rand(75, 100);
                } else {
                    $nilaiUts = rand(40, 65);
                    $nilaiUas = rand(40, 65);
                    $absen = rand(60, 85);
                }

                // Hitung nilai akhir (30% UTS, 40% UAS, 30% absen)
                $nilaiAkhirAngka = round(($nilaiUts * 0.3) + ($nilaiUas * 0.4) + ($absen * 0.3), 2);
                $nilaiAkhirHuruf = $this->getNilaiHuruf($nilaiAkhirAngka);

                // Hitung SKS lulus
                if (in_array($nilaiAkhirHuruf, ['A', 'AB', 'B', 'BC', 'C'])) {
                    $totalSksLulus += $matkul->sks;
                } elseif (in_array($nilaiAkhirHuruf, ['D', 'E'])) {
                    // Track mata kuliah yang gagal untuk di-retake
                    $matkulGagal[] = $matkul->id;
                }

                // Status: B = Baru untuk pengambilan pertama
                KhsKrsMahasiswa::create([
                    'mahasiswa_id' => $mahasiswaId,
                    'matakuliah_id' => $matkul->id,
                    'kelompok_id' => $kelompok->id,
                    'absen' => $absen,
                    'status' => 'B',
                    'nilai_uts' => $nilaiUts,
                    'nilai_uas' => $nilaiUas,
                    'nilai_akhir_angka' => $nilaiAkhirAngka,
                    'nilai_akhir_huruf' => $nilaiAkhirHuruf,
                ]);
            }

            // Handle retakes untuk mata kuliah yang gagal (D/E)
            if (!empty($matkulGagal) && $semester < $semesterAktif - 1) {
                // Retake di semester berikutnya (30% kemungkinan retake)
                foreach ($matkulGagal as $index => $matkulId) {
                    if (rand(1, 100) <= 30) {
                        $matkul = $mataKuliahs->firstWhere('id', $matkulId);
                        if (!$matkul) continue;

                        $kelompok = $matkul->kelompok_mata_kuliah->first();
                        if (!$kelompok) continue;

                        // Generate nilai ulang (70% kemungkinan lulus saat retake)
                        $lulusRetake = rand(1, 100) <= 70;

                        if ($lulusRetake) {
                            $nilaiUts = rand(65, 95);
                            $nilaiUas = rand(65, 95);
                            $absen = rand(80, 100);
                        } else {
                            $nilaiUts = rand(50, 70);
                            $nilaiUas = rand(50, 70);
                            $absen = rand(70, 90);
                        }

                        $nilaiAkhirAngka = round(($nilaiUts * 0.3) + ($nilaiUas * 0.4) + ($absen * 0.3), 2);
                        $nilaiAkhirHuruf = $this->getNilaiHuruf($nilaiAkhirAngka);

                        // Update SKS lulus jika berhasil
                        if (in_array($nilaiAkhirHuruf, ['A', 'AB', 'B', 'BC', 'C'])) {
                            $totalSksLulus += $matkul->sks;
                            // Remove dari list gagal
                            unset($matkulGagal[$index]);
                        }

                        // Create new record dengan status 'U' = Ulang
                        // TIDAK UPDATE record lama, tapi buat record BARU
                        KhsKrsMahasiswa::create([
                            'mahasiswa_id' => $mahasiswaId,
                            'matakuliah_id' => $matkul->id,
                            'kelompok_id' => $kelompok->id,
                            'absen' => $absen,
                            'status' => 'U', // Status ULANG
                            'nilai_uts' => $nilaiUts,
                            'nilai_uas' => $nilaiUas,
                            'nilai_akhir_angka' => $nilaiAkhirAngka,
                            'nilai_akhir_huruf' => $nilaiAkhirHuruf,
                        ]);
                    }
                }
            }
        }
    }



    private function getNilaiHuruf($nilaiAngka)
    {
        if ($nilaiAngka >= 85) return 'A';
        if ($nilaiAngka >= 75) return 'AB';
        if ($nilaiAngka >= 70) return 'B';
        if ($nilaiAngka >= 65) return 'BC';
        if ($nilaiAngka >= 60) return 'C';
        if ($nilaiAngka >= 56) return 'D';
        return 'E';
    }
}
