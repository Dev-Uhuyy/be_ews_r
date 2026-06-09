<?php

namespace Tests\Feature;

use App\Models\AkademikMahasiswa;
use App\Models\Dosen;
use App\Models\IpsMahasiswa;
use App\Models\KelompokMataKuliah;
use App\Models\KhsKrsMahasiswa;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Admin\EwsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EwsKelulusanTest â€” verifikasi 7 kondisi eligible + boundary.
 */
class EwsKelulusanTest extends TestCase
{
    use DatabaseTransactions;

    private function makeMhs(array $attrs, array $khs = []): AkademikMahasiswa
    {
        $prodi = Prodi::factory()->create();
        $user = User::factory()->create(['prodi_id' => $prodi->id]);
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id, 'prodi_id' => $prodi->id]);
        $dosen = Dosen::factory()->create(['prodi_id' => $prodi->id]);

        // status_mahasiswa belongs to mahasiswa table, NOT akademik_mahasiswa
        unset($attrs['status_mahasiswa']);

        $ak = AkademikMahasiswa::factory()->create(array_merge([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_wali_id' => $dosen->id,
            'semester_aktif' => 7,
            'tahun_masuk' => 2022,
            'ipk' => 3.5,
            'sks_lulus' => 144,
            'mk_nasional' => 'yes',
            'mk_fakultas' => 'yes',
            'mk_prodi' => 'yes',
        ], $attrs));
        IpsMahasiswa::factory()->create(['mahasiswa_id' => $mahasiswa->id]);

        foreach ($khs as $spec) {
            $mk = MataKuliah::factory()->create([
                'prodi_id' => $prodi->id,
                'tipe_mk' => $spec['tipe_mk'] ?? 'prodi',
                'semester' => $spec['semester'] ?? 1,
                'sks' => $spec['sks'] ?? 2,
            ]);
            $kel = KelompokMataKuliah::factory()->create([
                'mata_kuliah_id' => $mk->id,
                'dosen_pengampu_id' => $dosen->id,
            ]);
            KhsKrsMahasiswa::factory()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'matakuliah_id' => $mk->id,
                'kelompok_id' => $kel->id,
                'nilai_akhir_huruf' => $spec['nilai'] ?? 'A',
                'nilai_akhir_angka' => 4,
            ]);
        }

        return $ak;
    }

    #[Test]
    public function all_7_conditions_met_yields_eligible(): void
    {
        $ak = $this->makeMhs([]); // all defaults are eligible
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('eligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_ipk_fails(): void
    {
        $ak = $this->makeMhs(['ipk' => 1.5]);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_sks_lulus_fails(): void
    {
        $ak = $this->makeMhs(['sks_lulus' => 100]);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_mk_nasional_fails(): void
    {
        $ak = $this->makeMhs(['mk_nasional' => 'no']);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_mk_fakultas_fails(): void
    {
        $ak = $this->makeMhs(['mk_fakultas' => 'no']);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_mk_prodi_fails(): void
    {
        $ak = $this->makeMhs(['mk_prodi' => 'no']);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_nilai_e_yes_yields_noneligible(): void
    {
        $ak = $this->makeMhs(
            [],
            [['nilai' => 'E', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2]]
        );
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function condition_d_melebihi_batas_yields_noneligible(): void
    {
        // 3 MK with D (count > 2 strict)
        $ak = $this->makeMhs(
            [],
            [
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
            ]
        );
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function boundary_d_count_exactly_2_is_eligible(): void
    {
        $ak = $this->makeMhs(
            [],
            [
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
                ['nilai' => 'D', 'tipe_mk' => 'prodi', 'semester' => 1, 'sks' => 2],
            ]
        );
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('eligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function boundary_ipk_exactly_2_0_is_noneligible(): void
    {
        $ak = $this->makeMhs(['ipk' => 2.0]);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('noneligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }

    #[Test]
    public function boundary_sks_exactly_144_is_eligible(): void
    {
        $ak = $this->makeMhs(['sks_lulus' => 144]);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('eligible', $ak->fresh()->earlyWarningSystem->status_kelulusan);
    }
}

