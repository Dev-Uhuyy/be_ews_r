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
 * EwsStatusTest â€” verifikasi 4 EWS status values (kritis/perhatian/normal/tepat_waktu)
 * muncul di berbagai skenario semester.
 */
class EwsStatusTest extends TestCase
{
    use DatabaseTransactions;

    private function makeMhs(array $attrs, array $khs = [], ?string $gelar = null): AkademikMahasiswa
    {
        $prodi = Prodi::factory()->create(['gelar' => $gelar]);
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
            'ipk' => 3.0,
            'sks_lulus' => 100,
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
    public function all_4_status_values_are_reachable(): void
    {
        $service = app(EwsService::class);

        // Tepat Waktu: S7, sks=110, 0E 0D
        $ak1 = $this->makeMhs(['sks_lulus' => 110, 'semester_aktif' => 7]);
        $service->updateStatus($ak1);

        // Normal via branch h: S7, sks=80, sisa=64 (antara sksBisaSD8=40 dan sksBisaSD10=80)
        $ak2 = $this->makeMhs(['sks_lulus' => 80, 'semester_aktif' => 7]);
        $service->updateStatus($ak2);

        // Perhatian: S9, sks=50
        $ak3 = $this->makeMhs(['sks_lulus' => 50, 'semester_aktif' => 9]);
        $service->updateStatus($ak3);

        // Kritis: S13, sks=20
        $ak4 = $this->makeMhs(['sks_lulus' => 20, 'semester_aktif' => 13]);
        $service->updateStatus($ak4);

        $this->assertEquals('tepat_waktu', $ak1->fresh()->earlyWarningSystem->status);
        $this->assertEquals('normal', $ak2->fresh()->earlyWarningSystem->status);
        $this->assertEquals('perhatian', $ak3->fresh()->earlyWarningSystem->status);
        $this->assertEquals('kritis', $ak4->fresh()->earlyWarningSystem->status);
    }

    #[Test]
    public function ews_status_is_tepat_waktu_via_kondisi_a(): void
    {
        $ak = $this->makeMhs(['sks_lulus' => 144, 'semester_aktif' => 7]);
        app(EwsService::class)->updateStatus($ak);

        $this->assertEquals('tepat_waktu', $ak->fresh()->earlyWarningSystem->status);
    }

    #[Test]
    public function ews_status_persists_after_recalculation(): void
    {
        $ak = $this->makeMhs(['sks_lulus' => 110, 'semester_aktif' => 7]);
        $svc = app(EwsService::class);

        $svc->updateStatus($ak);
        $status1 = $ak->fresh()->earlyWarningSystem->status;

        // Re-run harus idem
        $svc->updateStatus($ak);
        $status2 = $ak->fresh()->earlyWarningSystem->status;

        $this->assertEquals($status1, $status2);
    }

    #[Test]
    public function d3_status_values_follow_kurikulum_6_semester(): void
    {
        $service = app(EwsService::class);

        // Tepat Waktu: S6, sks=100/108, sisa=8 (<= sksBisaSDNormal=20)
        $ak1 = $this->makeMhs(['sks_lulus' => 100, 'semester_aktif' => 6], gelar: 'D3');
        $service->updateStatus($ak1);

        // Kritis: S13 > 2K=12, sksBisaMaks=0
        $ak2 = $this->makeMhs(['sks_lulus' => 50, 'semester_aktif' => 13], gelar: 'D3');
        $service->updateStatus($ak2);

        $this->assertEquals('tepat_waktu', $ak1->fresh()->earlyWarningSystem->status);
        $this->assertEquals('kritis', $ak2->fresh()->earlyWarningSystem->status);
    }

    #[Test]
    public function s2_status_values_follow_kurikulum_4_semester(): void
    {
        $service = app(EwsService::class);

        // Tepat Waktu: S4, sks=30/36, sisa=6 (<= sksBisaSDNormal=20)
        $ak1 = $this->makeMhs(['sks_lulus' => 30, 'semester_aktif' => 4], gelar: 'S2');
        $service->updateStatus($ak1);

        // Kritis: S9 > 2K=8, sksBisaMaks=0
        $ak2 = $this->makeMhs(['sks_lulus' => 10, 'semester_aktif' => 9], gelar: 'S2');
        $service->updateStatus($ak2);

        $this->assertEquals('tepat_waktu', $ak1->fresh()->earlyWarningSystem->status);
        $this->assertEquals('kritis', $ak2->fresh()->earlyWarningSystem->status);
    }

    #[Test]
    public function s1_perhatian_boundary_moved_from_14_to_16(): void
    {
        $service = app(EwsService::class);

        // S16 = 2K untuk S1 (K=8) → masih 'perhatian', bukan 'kritis'
        $ak1 = $this->makeMhs(['sks_lulus' => 130, 'semester_aktif' => 16], gelar: 'S1');
        $service->updateStatus($ak1);

        // S17 > 2K → 'kritis'
        $ak2 = $this->makeMhs(['sks_lulus' => 130, 'semester_aktif' => 17], gelar: 'S1');
        $service->updateStatus($ak2);

        $this->assertEquals('perhatian', $ak1->fresh()->earlyWarningSystem->status);
        $this->assertEquals('kritis', $ak2->fresh()->earlyWarningSystem->status);
    }
}
