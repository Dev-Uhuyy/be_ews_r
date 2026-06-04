<?php

namespace Database\Seeders;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\IpsMahasiswa;
use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\User;
use App\Services\Admin\EwsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * EwsTargetedScenarioSeeder
 *
 * Insert 50 mahasiswa DETERMINISTIK yang target tepat satu branch
 * dari EwsServiceBase::hitungStatus() / hitungStatusKelulusan().
 *
 * Tiap skenario menghasilkan tepat 1 row di early_warning_system dengan
 * status yang diprediksi. NIM prefix `.SCN.` (scenario) agar tidak
 * konflik dengan EwsDummyDataSeeder (.XXX = tahun) atau baseline user.
 *
 * TIDAK menulis langsung ke early_warning_system — panggil EwsService
 * yang sudah ada supaya test integration valid.
 */
class EwsTargetedScenarioSeeder extends Seeder
{
    /**
     * Daftar 50 skenario deterministik.
     * Field: name, prodi_kode, tahun_masuk, semester_aktif, status_mahasiswa,
     *        cuti_2, sks_lulus, ipk, mk_nasional, mk_fakultas, mk_prodi,
     *        khs_specs: [['mk_tipe'=>nasional/fakultas/prodi, 'mk_semester'=>1..8, 'nilai'=>A/B/C/D/E, 'jumlah'=>1]]
     *        retake_spec: optional [['mk_tipe', 'mk_semester', 'old_nilai', 'new_nilai']]
     */
    private array $scenarios = [
        // ─── GROUP A: Branch a (sks_lulus >= 144 short-circuit) — 4 skenario
        ['name' => 'A1 S7 sks144', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2]]],
        ['name' => 'A2 S9 sks144', 'prodi' => 'A11', 'tahun' => 2021, 'smt' => 9,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.20,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'A3 S11 sks144', 'prodi' => 'A11', 'tahun' => 2020, 'smt' => 11,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.00,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'A4 S13 sks144', 'prodi' => 'A11', 'tahun' => 2019, 'smt' => 13,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 2.80,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],

        // ─── GROUP B: Branch k (Tepat Waktu S7/S8) — 4 skenario
        ['name' => 'B1 S7 sks110 0E0D', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'A', 'jumlah' => 2]]],
        ['name' => 'B2 S7 sks110 0E1D', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 3.00,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'A', 'jumlah' => 1],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]],
        ['name' => 'B3 S8 sks120 0E0D', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 8,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 120, 'ipk' => 3.40,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'B', 'jumlah' => 2]]],
        ['name' => 'B4 S8 sks130 0E1D', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 8,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 130, 'ipk' => 2.90,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'B', 'jumlah' => 1],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]],

        // ─── GROUP C: Branch h (Normal sisa SKS > sksBisa(8)) — 3 skenario
        ['name' => 'C1 S7 sks60 normal', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 60, 'ipk' => 2.50,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'B', 'jumlah' => 2]]],
        ['name' => 'C2 S7 sks50 normal', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 50, 'ipk' => 2.30,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],
        ['name' => 'C3 S8 sks60 normal', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 8,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 60, 'ipk' => 2.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],

        // ─── GROUP D: Branch i, j (Normal via E/D in MK ganjil/genap) — 2 skenario
        ['name' => 'D1 S7 E di MK ganjil', 'prodi' => 'A15', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 2.10,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'E', 'jumlah' => 1]]],
        ['name' => 'D2 S8 D di MK genap', 'prodi' => 'A15', 'tahun' => 2022, 'smt' => 8,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 120, 'ipk' => 2.40,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]],

        // ─── GROUP E: Default branch l (Normal fallback) — 1 skenario
        ['name' => 'E1 S5 default normal', 'prodi' => 'A11', 'tahun' => 2023, 'smt' => 5,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 70, 'ipk' => 3.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],

        // ─── GROUP F: Branch e (Perhatian sisa SKS > 10) — 2 skenario
        ['name' => 'F1 S9 sks50 perhatian', 'prodi' => 'A12', 'tahun' => 2021, 'smt' => 9,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 50, 'ipk' => 2.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],
        ['name' => 'F2 S10 sks50 perhatian', 'prodi' => 'A12', 'tahun' => 2021, 'smt' => 10,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 50, 'ipk' => 2.30,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],

        // ─── GROUP G: Branch f, g (Perhatian via E/D) — 2 skenario
        ['name' => 'G1 S9 E di MK ganjil', 'prodi' => 'A14', 'tahun' => 2021, 'smt' => 9,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 1.90,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'E', 'jumlah' => 1]]],
        ['name' => 'G2 S10 D di MK genap', 'prodi' => 'A14', 'tahun' => 2021, 'smt' => 10,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 2.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]],

        // ─── GROUP H: Branch b (Kritis sisa SKS > 14) — 2 skenario
        ['name' => 'H1 S13 sks20 kritis', 'prodi' => 'A15', 'tahun' => 2019, 'smt' => 13,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 20, 'ipk' => 1.50,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],
        ['name' => 'H2 S14 sks20 kritis', 'prodi' => 'A15', 'tahun' => 2019, 'smt' => 14,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 20, 'ipk' => 1.40,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => []],

        // ─── GROUP I: Branch c, d (Kritis via E/D) — 2 skenario
        ['name' => 'I1 S13 E di MK ganjil', 'prodi' => 'A11', 'tahun' => 2019, 'smt' => 13,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 80, 'ipk' => 1.80,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'E', 'jumlah' => 1]]],
        ['name' => 'I2 S14 D di MK genap', 'prodi' => 'A11', 'tahun' => 2019, 'smt' => 14,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 80, 'ipk' => 1.70,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]],

        // ─── GROUP J: Boundary tests — 5 skenario
        ['name' => 'J1 IPK=2.0 exact (noneligible)', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 2.00,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'J2 SKS=144 exact (eligible)', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'J3 D exactly 7.2 SKS (eligible)', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.20,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 1],
                   ['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 1],
                   ['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 1]]], // 3 MK @ 2 SKS = 6 SKS D
        ['name' => 'J4 D exactly 2 MK (eligible)', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.20,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 2]]],
        ['name' => 'J5 D 3 MK 2 SKS each = 6 SKS (eligible)', 'prodi' => 'A15', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.10,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 3]]],

        // ─── GROUP K: Eligible/Noneligible specific — 4 skenario
        ['name' => 'K1 Eligible (full)', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.80,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'K2 Noneligible IPK<2', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 1.80,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'K3 Noneligible SKS<144', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 100, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => []],
        ['name' => 'K4 Noneligible ada E', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'E', 'jumlah' => 1]]],

        // ─── GROUP L: Status Mahasiswa — 7 skenario
        ['name' => 'L1 aktif', 'prodi' => 'A12', 'tahun' => 2023, 'smt' => 5,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 70, 'ipk' => 3.10,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'L2 cuti', 'prodi' => 'A12', 'tahun' => 2023, 'smt' => 5,
         'status' => 'cuti', 'cuti_2' => 'no', 'sks_lulus' => 50, 'ipk' => 2.80,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'L3 cuti_2x', 'prodi' => 'A14', 'tahun' => 2023, 'smt' => 5,
         'status' => 'cuti', 'cuti_2' => 'yes', 'sks_lulus' => 50, 'ipk' => 2.50,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'L4 mangkir', 'prodi' => 'A14', 'tahun' => 2023, 'smt' => 5,
         'status' => 'mangkir', 'cuti_2' => 'no', 'sks_lulus' => 40, 'ipk' => 2.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'L5 tidak_aktif', 'prodi' => 'A15', 'tahun' => 2023, 'smt' => 5,
         'status' => 'tidak_aktif', 'cuti_2' => 'no', 'sks_lulus' => 30, 'ipk' => 1.50,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'L6 lulus', 'prodi' => 'A15', 'tahun' => 2020, 'smt' => 11,
         'status' => 'lulus', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes', 'khs' => [],
         'tanggal_yusidium' => '2025-06-30'],
        ['name' => 'L7 DO', 'prodi' => 'A11', 'tahun' => 2020, 'smt' => 11,
         'status' => 'DO', 'cuti_2' => 'no', 'sks_lulus' => 30, 'ipk' => 1.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],

        // ─── GROUP M: Retake (latest KHS wins) — 1 skenario
        ['name' => 'M1 Retake D→B', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 3.00,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'no',
         'khs' => [['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2],
                   ['tipe' => 'fakultas', 'sem' => 2, 'nilai' => 'A', 'jumlah' => 2]],
         'retake' => ['tipe' => 'prodi', 'sem' => 1, 'old' => 'D', 'new' => 'B']],

        // ─── GROUP N: More EWS distribution variety — 11 skenario
        ['name' => 'N1 S7 sks100 0E2D', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 100, 'ipk' => 2.80,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'D', 'jumlah' => 2]]], // >1 D → fail k → 'normal'
        ['name' => 'N2 S9 0E0D mid', 'prodi' => 'A12', 'tahun' => 2021, 'smt' => 9,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 100, 'ipk' => 2.50,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N3 S11 0E0D low', 'prodi' => 'A14', 'tahun' => 2020, 'smt' => 11,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 80, 'ipk' => 2.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N4 S13 0E0D low', 'prodi' => 'A15', 'tahun' => 2019, 'smt' => 13,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 60, 'ipk' => 1.80,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N5 S5 sks80 normal default', 'prodi' => 'A11', 'tahun' => 2023, 'smt' => 5,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 80, 'ipk' => 3.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N6 S3 sks30 normal', 'prodi' => 'A12', 'tahun' => 2024, 'smt' => 3,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 30, 'ipk' => 3.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N7 S1 sks10 normal', 'prodi' => 'A14', 'tahun' => 2025, 'smt' => 1,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 10, 'ipk' => 3.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no', 'khs' => []],
        ['name' => 'N8 Eligible exact', 'prodi' => 'A11', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 145, 'ipk' => 2.01,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes', 'khs' => []],
        ['name' => 'N9 Cuti eligible-like', 'prodi' => 'A12', 'tahun' => 2022, 'smt' => 7,
         'status' => 'cuti', 'cuti_2' => 'no', 'sks_lulus' => 144, 'ipk' => 3.50,
         'mk_n' => 'yes', 'mk_f' => 'yes', 'mk_p' => 'yes', 'khs' => []],
        ['name' => 'N10 S7 D MK genap', 'prodi' => 'A14', 'tahun' => 2022, 'smt' => 7,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 110, 'ipk' => 3.20,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 2, 'nilai' => 'D', 'jumlah' => 1]]], // D in genap, smt=7 → no special branch
        ['name' => 'N11 S8 E MK ganjil', 'prodi' => 'A15', 'tahun' => 2022, 'smt' => 8,
         'status' => 'aktif', 'cuti_2' => 'no', 'sks_lulus' => 120, 'ipk' => 3.00,
         'mk_n' => 'no', 'mk_f' => 'no', 'mk_p' => 'no',
         'khs' => [['tipe' => 'prodi', 'sem' => 1, 'nilai' => 'E', 'jumlah' => 1]]], // E in ganjil, smt=8 → no special branch
    ];

    public function run(): void
    {
        $ewsService = app(EwsService::class);

        // Cache dosen per prodi
        $dosenCache = [];
        $kelompokCache = [];

        $count = 0;
        foreach ($this->scenarios as $idx => $sc) {
            $prodi = \App\Models\Prodi::where('kode_prodi', $sc['prodi'])->first();
            if (! $prodi) {
                $this->command->warn("  ⚠ Prodi {$sc['prodi']} tidak ada, skip skenario {$sc['name']}.");

                continue;
            }

            $dosen = $dosenCache[$prodi->id] ??= Dosen::where('prodi_id', $prodi->id)->first();
            if (! $dosen) {
                $this->command->warn("  ⚠ Dosen untuk prodi {$sc['prodi']} tidak ada, skip skenario {$sc['name']}.");

                continue;
            }

            $i = str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
            $nim = "{$sc['prodi']}.SCN.{$i}";
            $email = "{$nim}@ews.com";

            DB::beginTransaction();
            try {
                // 1. User
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "SCN {$sc['name']}",
                        'password' => Hash::make('password'),
                        'prodi_id' => $prodi->id,
                    ]
                );
                if (! $user->hasRole('mahasiswa')) {
                    $user->assignRole('mahasiswa');
                }

                // 2. Mahasiswa
                $mahasiswa = Mahasiswa::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nim' => $nim,
                        'prodi_id' => $prodi->id,
                        'status_mahasiswa' => $sc['status'],
                        'cuti_2' => $sc['cuti_2'],
                        'tanggal_yusidium' => $sc['tanggal_yusidium'] ?? null,
                    ]
                );

                // 3. Akademik
                $akademik = AkademikMahasiswa::updateOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id],
                    [
                        'dosen_wali_id' => $dosen->id,
                        'semester_aktif' => $sc['smt'],
                        'tahun_masuk' => $sc['tahun'],
                        'ipk' => $sc['ipk'],
                        'sks_lulus' => $sc['sks_lulus'],
                        'sks_tempuh' => $sc['sks_lulus'] + 6,
                        'sks_now' => 20,
                        'sks_gagal' => 0,
                        'mk_nasional' => $sc['mk_n'],
                        'mk_fakultas' => $sc['mk_f'],
                        'mk_prodi' => $sc['mk_p'],
                    ]
                );

                // 4. IPS (random, 1..smt)
                $ipsData = [];
                for ($n = 1; $n <= 14; $n++) {
                    $ipsData["ips_{$n}"] = $n <= $sc['smt']
                        ? round(rand(180, 390) / 100, 2)
                        : null;
                }
                IpsMahasiswa::updateOrCreate(['mahasiswa_id' => $mahasiswa->id], $ipsData);

                // 5. KHS — cari MK yang match tipe+semester, insert dengan nilai yg ditentukan
                foreach ($sc['khs'] as $spec) {
                    $this->insertKhsSpec($mahasiswa->id, $prodi->id, $dosen->id, $spec, $kelompokCache);
                }

                // 6. Retake scenario (insert older row, then newer row with MAX id)
                if (isset($sc['retake'])) {
                    $this->insertRetakeSpec($mahasiswa->id, $prodi->id, $dosen->id, $sc['retake'], $kelompokCache);
                }

                // 7. Trigger EWS recalc
                $ewsService->updateStatus($akademik);

                DB::commit();
                $count++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->command->error("  ✖ Skenario {$sc['name']} gagal: ".$e->getMessage());
            }
        }

        $this->command->info("✔ EwsTargetedScenarioSeeder: {$count} skenario targeted di-seed.");
    }

    /**
     * Insert KHS row sesuai spec.
     * Spec: ['tipe' => 'nasional', 'sem' => 1, 'nilai' => 'A', 'jumlah' => 2]
     * Akan insert 'jumlah' KHS rows, masing-masing dengan MK berbeda yang match tipe+semester.
     */
    private function insertKhsSpec(int $mahasiswaId, int $prodiId, int $dosenId, array $spec, array &$kelompokCache): void
    {
        $mks = MataKuliah::where('prodi_id', $prodiId)
            ->where('tipe_mk', $spec['tipe'])
            ->where('semester', $spec['sem'])
            ->limit($spec['jumlah'])
            ->get();

        if ($mks->isEmpty()) {
            return;
        }

        foreach ($mks as $mk) {
            $key = "{$prodiId}-{$mk->id}";
            $kelompok = $kelompokCache[$key] ??= KelompokMataKuliah::firstOrCreate(
                ['mata_kuliah_id' => $mk->id, 'kode' => 'A'],
                ['dosen_pengampu_id' => $dosenId]
            );

            KhsKrsMahasiswa::create([
                'mahasiswa_id' => $mahasiswaId,
                'matakuliah_id' => $mk->id,
                'kelompok_id' => $kelompok->id,
                'semester_ambil' => $spec['sem'],
                'status' => 'B',
                'absen' => rand(80, 100),
                'nilai_uts' => rand(60, 90),
                'nilai_uas' => rand(60, 90),
                'nilai_akhir_angka' => $this->nilaiHurufToAngka($spec['nilai']),
                'nilai_akhir_huruf' => $spec['nilai'],
            ]);
        }
    }

    /**
     * Insert retake: 2 rows untuk MK yg sama, older dengan old_nilai, newer (max id) dengan new_nilai.
     * EwsService akan baca MAX(id) → new_nilai menang.
     */
    private function insertRetakeSpec(int $mahasiswaId, int $prodiId, int $dosenId, array $spec, array &$kelompokCache): void
    {
        $mk = MataKuliah::where('prodi_id', $prodiId)
            ->where('tipe_mk', $spec['tipe'])
            ->where('semester', $spec['sem'])
            ->first();

        if (! $mk) {
            return;
        }

        $key = "{$prodiId}-{$mk->id}";
        $kelompok = $kelompokCache[$key] ??= KelompokMataKuliah::firstOrCreate(
            ['mata_kuliah_id' => $mk->id, 'kode' => 'A'],
            ['dosen_pengampu_id' => $dosenId]
        );

        // Old row (D) — id lebih kecil
        KhsKrsMahasiswa::create([
            'mahasiswa_id' => $mahasiswaId,
            'matakuliah_id' => $mk->id,
            'kelompok_id' => $kelompok->id,
            'semester_ambil' => $spec['sem'],
            'status' => 'B',
            'absen' => 80,
            'nilai_uts' => 50,
            'nilai_uas' => 40,
            'nilai_akhir_angka' => $this->nilaiHurufToAngka($spec['old']),
            'nilai_akhir_huruf' => $spec['old'],
        ]);

        // New row (B) — auto-id lebih besar
        KhsKrsMahasiswa::create([
            'mahasiswa_id' => $mahasiswaId,
            'matakuliah_id' => $mk->id,
            'kelompok_id' => $kelompok->id,
            'semester_ambil' => $spec['sem'] + 1,
            'status' => 'U', // Ulang (retake)
            'absen' => 95,
            'nilai_uts' => 80,
            'nilai_uas' => 85,
            'nilai_akhir_angka' => $this->nilaiHurufToAngka($spec['new']),
            'nilai_akhir_huruf' => $spec['new'],
        ]);
    }

    private function nilaiHurufToAngka(string $huruf): int
    {
        return match ($huruf) {
            'A' => 4,
            'B' => 3,
            'C' => 2,
            'D' => 1,
            'E' => 0,
            default => 0,
        };
    }
}
