<?php

namespace Tests\Feature;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\IpsMahasiswa;
use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Admin\EwsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EwsCalculationTest â€” 30+ test cases untuk EwsServiceBase calculation logic.
 *
 * Setiap test setup:
 * 1. Prodi + User + Mahasiswa + Dosen
 * 2. AkademikMahasiswa dengan semester_aktif, sks_lulus, ipk
 * 3. KHS rows dengan nilai spesifik untuk drive EWS branch
 * 4. Panggil EwsService->updateStatus()
 * 5. Assert early_warning_system.status & status_kelulusan
 */
class EwsCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private EwsService $ewsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ewsService = app(EwsService::class);
    }

    /**
     * Helper: setup prodi + dosen + 1 user + akademik.
     */
    private function setupMahasiswa(array $akademikAttrs, array $khsSpecs = []): AkademikMahasiswa
    {
        $prodi = Prodi::factory()->create();
        $user = User::factory()->create(['prodi_id' => $prodi->id]);
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'prodi_id' => $prodi->id,
            'status_mahasiswa' => $akademikAttrs['status_mahasiswa'] ?? 'aktif',
        ]);
        $dosen = Dosen::factory()->create(['prodi_id' => $prodi->id]);

        // status_mahasiswa belongs to mahasiswa table, NOT akademik_mahasiswa
        unset($akademikAttrs['status_mahasiswa']);

        $akademikAttrsDefault = [
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_wali_id' => $dosen->id,
            'semester_aktif' => 7,
            'tahun_masuk' => 2022,
            'ipk' => 3.5,
            'sks_lulus' => 110,
            'sks_tempuh' => 120,
            'mk_nasional' => 'no',
            'mk_fakultas' => 'no',
            'mk_prodi' => 'no',
        ];
        $akademik = AkademikMahasiswa::factory()->create(array_merge($akademikAttrsDefault, $akademikAttrs));

        // Create IPS placeholder
        IpsMahasiswa::factory()->create(['mahasiswa_id' => $mahasiswa->id]);

        // Create KHS rows
        foreach ($khsSpecs as $spec) {
            $mk = MataKuliah::factory()->create([
                'prodi_id' => $prodi->id,
                'tipe_mk' => $spec['tipe_mk'] ?? 'prodi',
                'semester' => $spec['semester'] ?? 1,
                'sks' => $spec['sks'] ?? 2,
            ]);
            $kelompok = KelompokMataKuliah::factory()->create([
                'mata_kuliah_id' => $mk->id,
                'dosen_pengampu_id' => $dosen->id,
            ]);
            KhsKrsMahasiswa::factory()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'matakuliah_id' => $mk->id,
                'kelompok_id' => $kelompok->id,
                'semester_ambil' => $spec['semester_ambil'] ?? $spec['semester'] ?? 1,
                'status' => 'B',
                'nilai_akhir_huruf' => $spec['nilai'] ?? 'A',
                'nilai_akhir_angka' => 4,
            ]);
        }

        return $akademik;
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 1: Branch a â€” sks_lulus >= 144 short-circuit
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function sks_lulus_144_semester_7_returns_tepat_waktu(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 144, 'semester_aktif' => 7]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('tepat_waktu', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function sks_lulus_144_semester_9_returns_normal(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 144, 'semester_aktif' => 9]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('normal', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function sks_lulus_144_semester_11_returns_perhatian(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 144, 'semester_aktif' => 11]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function sks_lulus_144_semester_14_returns_perhatian(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 144, 'semester_aktif' => 14]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 2: Branch k â€” Tepat Waktu S7/S8
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_7_sks_110_no_e_no_d_returns_tepat_waktu(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 110, 'semester_aktif' => 7]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('tepat_waktu', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_8_sks_130_no_e_no_d_returns_tepat_waktu(): void
    {
        // sks_lulus=130 → sisaSks=14, sksBisaSD8=20, 14 <= 20 → branch k 'tepat_waktu'
        $ak = $this->setupMahasiswa(['sks_lulus' => 130, 'semester_aktif' => 8]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('tepat_waktu', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_7_sks_110_one_d_in_genap_mk_returns_tepat_waktu(): void
    {
        // Untuk branch k fire, D harus di MK semester genap (2,4,6,8).
        // Kalau di MK semester ganjil, branch i fire lebih dulu → 'normal'.
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 7],
            [['nilai' => 'D', 'tipe_mk' => 'fakultas', 'semester' => 2, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('tepat_waktu', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_7_sks_110_two_d_returns_normal_not_tepat_waktu(): void
    {
        // 2 D's > 1 â†’ branch k fails â†’ branch h (sisa SKS) atau default
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 7],
            [
                ['nilai' => 'D', 'tipe_mk' => 'nasional', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'nasional', 'semester' => 1, 'sks' => 2],
            ]
        );
        $this->ewsService->updateStatus($ak);

        $status = $ak->earlyWarningSystem->fresh()->status;
        $this->assertNotEquals('tepat_waktu', $status, 'Should NOT be tepat_waktu with 2 D');
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 3: Branch h â€” Normal via sisa SKS > sksBisa(8)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_7_sks_80_sisa_64_returns_normal_via_sisa(): void
    {
        // sks_lulus=80 → sisaSks=64, sksBisaSD8=40, 64>40 → branch h 'normal'
        // (sksBisaSD10=80, 64<=80, jadi tidak masuk branch e)
        $ak = $this->setupMahasiswa(['sks_lulus' => 80, 'semester_aktif' => 7]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('normal', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_8_sks_100_sisa_44_returns_normal_via_sisa(): void
    {
        // sks_lulus=100 → sisaSks=44, sksBisaSD8=20, 44>20 → branch h 'normal'
        // (sksBisaSD10=60, 44<=60, jadi tidak masuk branch e)
        $ak = $this->setupMahasiswa(['sks_lulus' => 100, 'semester_aktif' => 8]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('normal', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 4: Branch i, j â€” Normal via E/D in MK ganjil/genap
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_7_e_in_odd_semester_mk_returns_normal(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 7],
            [['nilai' => 'E', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('normal', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_8_d_in_even_semester_mk_returns_normal(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 120, 'semester_aktif' => 8],
            [['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 2, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('normal', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 5: Branch e â€” Perhatian via sisa SKS > sksBisa(10)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_9_sks_50_returns_perhatian_via_sisa(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 50, 'semester_aktif' => 9]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_10_sks_50_returns_perhatian(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 50, 'semester_aktif' => 10]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 6: Branch f, g â€” Perhatian via E/D
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_9_e_in_odd_mk_returns_perhatian(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 9],
            [['nilai' => 'E', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_10_d_in_even_mk_returns_perhatian(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 10],
            [['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 2, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('perhatian', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 7: Branch b â€” Kritis via sisa SKS > sksBisa(14)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_13_sks_20_returns_kritis_via_sisa(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 20, 'semester_aktif' => 13]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('kritis', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_14_sks_20_returns_kritis(): void
    {
        $ak = $this->setupMahasiswa(['sks_lulus' => 20, 'semester_aktif' => 14]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('kritis', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 8: Branch c, d â€” Kritis via E/D
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function semester_13_e_in_odd_mk_returns_kritis(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 80, 'semester_aktif' => 13],
            [['nilai' => 'E', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('kritis', $ak->earlyWarningSystem->fresh()->status);
    }

    #[Test]
    public function semester_14_d_in_even_mk_returns_kritis(): void
    {
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 80, 'semester_aktif' => 14],
            [['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 2, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('kritis', $ak->earlyWarningSystem->fresh()->status);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 9: Eligible / Noneligible (7 conditions)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function all_7_conditions_met_returns_eligible(): void
    {
        $ak = $this->setupMahasiswa([
            'sks_lulus' => 144,
            'semester_aktif' => 7,
            'ipk' => 3.5,
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('eligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function ipk_under_2_returns_noneligible(): void
    {
        $ak = $this->setupMahasiswa([
            'sks_lulus' => 144,
            'semester_aktif' => 7,
            'ipk' => 1.8,
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function sks_under_144_returns_noneligible(): void
    {
        $ak = $this->setupMahasiswa([
            'sks_lulus' => 100,
            'semester_aktif' => 7,
            'ipk' => 3.5,
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function having_nilai_e_returns_noneligible(): void
    {
        $ak = $this->setupMahasiswa(
            [
                'sks_lulus' => 144,
                'semester_aktif' => 7,
                'ipk' => 3.5,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
            ],
            [['nilai' => 'E', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2]]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function d_total_sks_more_than_7_2_returns_noneligible(): void
    {
        // 4 MK @ 2 SKS D = 8 SKS D > 7.2 â†’ nilai_d_melebihi_batas='yes'
        $ak = $this->setupMahasiswa(
            [
                'sks_lulus' => 144,
                'semester_aktif' => 7,
                'ipk' => 3.5,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
            ],
            [
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
            ]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function d_exactly_3_mk_returns_noneligible(): void
    {
        // 3 MK with D (count > 2) â†’ nilai_d_melebihi_batas='yes'
        $ak = $this->setupMahasiswa(
            [
                'sks_lulus' => 144,
                'semester_aktif' => 7,
                'ipk' => 3.5,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
            ],
            [
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
            ]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 10: Boundary tests
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function sks_exactly_144_returns_eligible(): void
    {
        $ak = $this->setupMahasiswa([
            'sks_lulus' => 144, // exact boundary, >= 144 inclusive
            'semester_aktif' => 7,
            'ipk' => 2.01, // > 2.0 strict
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('eligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function ipk_exactly_2_returns_noneligible(): void
    {
        // IPK > 2.0 strict, IPK=2.0 fails
        $ak = $this->setupMahasiswa([
            'sks_lulus' => 144,
            'semester_aktif' => 7,
            'ipk' => 2.0, // exact boundary
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ]);
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    #[Test]
    public function d_exactly_2_mk_returns_eligible(): void
    {
        // count > 2 strict, count=2 OK
        $ak = $this->setupMahasiswa(
            [
                'sks_lulus' => 144,
                'semester_aktif' => 7,
                'ipk' => 3.5,
                'mk_nasional' => 'yes',
                'mk_fakultas' => 'yes',
                'mk_prodi' => 'yes',
            ],
            [
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
            ]
        );
        $this->ewsService->updateStatus($ak);

        $this->assertEquals('eligible', $ak->earlyWarningSystem->fresh()->status_kelulusan);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 11: Retake scenario
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function retake_latest_khs_overrides_older(): void
    {
        // Setup 1 KHS row di MK semester genap (supaya branch k reachable)
        $ak = $this->setupMahasiswa(
            ['sks_lulus' => 110, 'semester_aktif' => 7],
            [['nilai' => 'B', 'tipe_mk' => 'fakultas', 'semester' => 2, 'sks' => 2]]
        );

        $khs = KhsKrsMahasiswa::first();
        $this->assertNotNull($khs, 'KHS row harus ada dari setupMahasiswa');

        // Insert older D row (id lebih kecil)
        KhsKrsMahasiswa::create([
            'mahasiswa_id' => $khs->mahasiswa_id,
            'matakuliah_id' => $khs->matakuliah_id,
            'kelompok_id' => $khs->kelompok_id,
            'semester_ambil' => 1,
            'status' => 'B',
            'nilai_akhir_huruf' => 'D',
            'nilai_akhir_angka' => 1,
        ]);

        // Insert newer B row (auto-id lebih besar)
        KhsKrsMahasiswa::create([
            'mahasiswa_id' => $khs->mahasiswa_id,
            'matakuliah_id' => $khs->matakuliah_id,
            'kelompok_id' => $khs->kelompok_id,
            'semester_ambil' => 2,
            'status' => 'U', // Ulang
            'nilai_akhir_huruf' => 'B',
            'nilai_akhir_angka' => 3,
        ]);

        $this->ewsService->updateStatus($ak->fresh());
        $ak->refresh();

        // Latest (B) wins, jadi nilai_e='no' dan nilai_d_melebihi_batas='no'
        $this->assertEquals('no', $ak->nilai_e);
        $this->assertEquals('no', $ak->nilai_d_melebihi_batas);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // GROUP 12: Update all status (batch) â€” exclude Lulus & DO
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    #[Test]
    public function update_all_status_excludes_lulus_and_do(): void
    {
        // Create 3 mhs: aktif, lulus, DO
        $akAktif = $this->setupMahasiswa(['sks_lulus' => 110, 'semester_aktif' => 7, 'status_mahasiswa' => 'aktif']);
        $akLulus = $this->setupMahasiswa(['sks_lulus' => 144, 'semester_aktif' => 7, 'status_mahasiswa' => 'lulus']);
        $akDo = $this->setupMahasiswa(['sks_lulus' => 50, 'semester_aktif' => 7, 'status_mahasiswa' => 'DO']);

        $result = $this->ewsService->updateAllStatus();

        // Total processed harus hanya yang aktif (2 here? no, only 1 aktif)
        // Karena setupMahasiswa bikin akademik baru tiap call, total akademik aktif = 1
        $this->assertEquals(1, $result['total_processed']);
    }
}

