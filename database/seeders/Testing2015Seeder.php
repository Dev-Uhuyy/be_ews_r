<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\EarlyWarningSystem;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\User;
use App\Models\KelompokMataKuliah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Testing2015Seeder extends Seeder
{
    public function run(): void
    {
        $doswal = Dosen::first();
        $kelompok = KelompokMataKuliah::first();
        $tahun = 2015;

        // Cleanup existing test data if any
        for ($i = 1; $i <= 5; $i++) {
            $nim = 'A11.2015.0000' . $i;
            $oldMhs = Mahasiswa::withTrashed()->where('nim', $nim)->first();
            if ($oldMhs) {
                DB::table('khs_krs_mahasiswa')->where('mahasiswa_id', $oldMhs->id)->delete();
                DB::table('early_warning_system')->whereIn('akademik_mahasiswa_id', function($q) use ($oldMhs) {
                    $q->select('id')->from('akademik_mahasiswa')->where('mahasiswa_id', $oldMhs->id);
                })->delete();
                AkademikMahasiswa::where('mahasiswa_id', $oldMhs->id)->delete();
                IpsMahasiswa::where('mahasiswa_id', $oldMhs->id)->delete();
                $oldMhs->forceDelete();
            }
        }

        AkademikMahasiswa::withoutEvents(function () use ($doswal, $kelompok, $tahun) {
            // 1. Mahasiswa Teladan (Semua YES, No D/E)
            $this->createMahasiswaWithCriteria([
                'name' => 'Budi Si Teladan 2015',
                'nim' => 'A11.2015.00001',
                'tahun_masuk' => $tahun,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
                'nilai_d_melebihi_batas' => 'no',
                'nilai_e' => 'no',
                'khs' => [
                    ['id' => 60, 'grade' => 'A'], // Nasional
                    ['id' => 57, 'grade' => 'B'], // Fakultas
                    ['id' => 1, 'grade' => 'A'],  // Prodi
                ]
            ], $doswal, $kelompok);

            // 2. Mahasiswa Bermasalah MK Wajib (Status 'no', list detail)
            $this->createMahasiswaWithCriteria([
                'name' => 'Iwan Masalah Wajib 2015',
                'nim' => 'A11.2015.00002',
                'tahun_masuk' => $tahun,
                'mk_nasional' => 'no',
                'mk_fakultas' => 'no',
                'mk_prodi' => 'yes',
                'nilai_d_melebihi_batas' => 'no',
                'nilai_e' => 'no',
                'khs' => [
                    ['id' => 57, 'grade' => 'E'], // Fakultas (Taken but E = Missing)
                    ['id' => 1, 'grade' => 'C'],  // Prodi
                ]
            ], $doswal, $kelompok);

            // 3. Mahasiswa Penuh Nilai E (nilai_e = no in API)
            $this->createMahasiswaWithCriteria([
                'name' => 'Eko Banyak E 2015',
                'nim' => 'A11.2015.00003',
                'tahun_masuk' => $tahun,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
                'nilai_d_melebihi_batas' => 'no',
                'nilai_e' => 'yes', // Has E in DB -> No in API
                'khs' => [
                    ['id' => 60, 'grade' => 'B'],
                    ['id' => 57, 'grade' => 'B'],
                    ['id' => 1, 'grade' => 'B'],
                    ['id' => 2, 'grade' => 'E'],
                    ['id' => 3, 'grade' => 'E'],
                ]
            ], $doswal, $kelompok);

            // 4. Mahasiswa Penuh Nilai D (jumlah_nilai_d > 0)
            $this->createMahasiswaWithCriteria([
                'name' => 'Dedi Banyak D 2015',
                'nim' => 'A11.2015.00004',
                'tahun_masuk' => $tahun,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
                'nilai_d_melebihi_batas' => 'yes', // Excessive D
                'nilai_e' => 'no',
                'khs' => [
                    ['id' => 60, 'grade' => 'B'],
                    ['id' => 57, 'grade' => 'B'],
                    ['id' => 1, 'grade' => 'B'],
                    ['id' => 4, 'grade' => 'D'],
                    ['id' => 5, 'grade' => 'D'],
                    ['id' => 6, 'grade' => 'D'],
                ]
            ], $doswal, $kelompok);

            // 5. Mahasiswa Komplikasi (Semua Masalah)
            $this->createMahasiswaWithCriteria([
                'name' => 'Kiki Komplikasi 2015',
                'nim' => 'A11.2015.00005',
                'tahun_masuk' => $tahun,
                'mk_nasional' => 'no',
                'mk_fakultas' => 'no',
                'mk_prodi' => 'no',
                'nilai_d_melebihi_batas' => 'yes',
                'nilai_e' => 'yes',
                'khs' => [
                    ['id' => 7, 'grade' => 'D'],
                    ['id' => 8, 'grade' => 'E'],
                ]
            ], $doswal, $kelompok);
        });

        echo "Seeding Testing2015Seeder completed.\n";
    }

    private function createMahasiswaWithCriteria(array $data, $doswal, $kelompok)
    {
        try {
            $email = str_replace([' ', '.'], '', strtolower($data['name'])) . '.' . rand(1,999) . '@test.com';
            
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $user->assignRole('mahasiswa');

            $mahasiswa = Mahasiswa::updateOrCreate(
                ['nim' => $data['nim']],
                [
                    'user_id' => $user->id,
                    'status_mahasiswa' => 'aktif',
                ]
            );

            $akademik = AkademikMahasiswa::updateOrCreate(
                ['mahasiswa_id' => $mahasiswa->id],
                [
                    'dosen_wali_id' => $doswal->id,
                    'semester_aktif' => 14,
                    'tahun_masuk' => $data['tahun_masuk'],
                    'ipk' => 2.5,
                    'sks_lulus' => 100,
                    'mk_nasional' => $data['mk_nasional'],
                    'mk_fakultas' => $data['mk_fakultas'],
                    'mk_prodi' => $data['mk_prodi'],
                    'nilai_d_melebihi_batas' => $data['nilai_d_melebihi_batas'],
                    'nilai_e' => $data['nilai_e'],
                ]
            );

            EarlyWarningSystem::updateOrCreate(
                ['akademik_mahasiswa_id' => $akademik->id],
                [
                    'status' => 'kritis',
                    'status_kelulusan' => 'noneligible',
                ]
            );

            IpsMahasiswa::updateOrCreate(['mahasiswa_id' => $mahasiswa->id], ['ips_1' => 2.5]);

            foreach ($data['khs'] ?? [] as $khs) {
                KhsKrsMahasiswa::create([
                    'mahasiswa_id' => $mahasiswa->id,
                    'matakuliah_id' => $khs['id'],
                    'kelompok_id' => $kelompok->id,
                    'status' => 'B',
                    'nilai_akhir_huruf' => $khs['grade'],
                    'nilai_akhir_angka' => $khs['grade'] === 'A' ? 4 : ($khs['grade'] === 'B' ? 3 : 1),
                    'absen' => 100,
                ]);
            }
        } catch (\Exception $e) {
            echo "ERROR in createMahasiswaWithCriteria for {$data['nim']}: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
