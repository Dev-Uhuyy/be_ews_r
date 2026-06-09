<?php

namespace Tests\Feature;

use Database\Seeders\BaselineUsersSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EwsDummyDataSeeder;
use Database\Seeders\EwsTargetedScenarioSeeder;
use Database\Seeders\MataKuliahSeeder;
use Database\Seeders\ProdiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SeederSmokeTest â€” verifikasi seeder utama berjalan tanpa error dan
 * menghasilkan data yang konsisten (FK integrity, row count masuk akal).
 *
 * PENTING: Tidak menjalankan SEMUA seeders (DosenSeeder & MahasiswaSeeder
 * butuh sti_api.sql data existing). Hanya seeders yang berdiri sendiri.
 */
class SeederSmokeTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function prodi_seeder_creates_10_prodies(): void
    {
        $this->seed(ProdiSeeder::class);

        $this->assertDatabaseCount('prodis', 10);
    }

    #[Test]
    public function role_seeder_creates_3_ews_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'super_fakultas']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'mahasiswa']);
    }

    #[Test]
    public function mata_kuliah_seeder_creates_minimum_mk_per_prodi(): void
    {
        $this->setupMinSchemaWithDosen();
        $this->seed(MataKuliahSeeder::class);

        // Tiap prodi minimal 9 MK (fallback nasional/fakultas/prodi)
        $mksPerProdi = \App\Models\MataKuliah::query()
            ->selectRaw('prodi_id, count(*) as cnt')
            ->groupBy('prodi_id')
            ->pluck('cnt', 'prodi_id')
            ->toArray();

        foreach ($mksPerProdi as $prodiId => $cnt) {
            $this->assertGreaterThanOrEqual(9, $cnt, "Prodi ID $prodiId harus punya >= 9 MK");
        }
    }

    /**
     * Helper: create minimal schema dengan prodies + 1 dosen per prodi (skip DosenSeeder yang butuh user_id 10/21/32/43).
     */
    private function setupMinSchemaWithDosen(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(ProdiSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        // Skip DosenSeeder — hardcode user_id tidak exist di test DB. Create 1 Dosen per prodi via factory.
        $prodies = \App\Models\Prodi::all();
        foreach ($prodies as $prodi) {
            \App\Models\Dosen::factory()->create(['prodi_id' => $prodi->id]);
        }
        $this->seed(\Database\Seeders\MataKuliahPeminatanSeeder::class);
        $this->seed(\Database\Seeders\MataKuliahSeeder::class);
        $this->seed(\Database\Seeders\KelompokMataKuliahSeeder::class);
    }

    #[Test]
    public function ews_dummy_seeder_creates_users_and_mahasiswa(): void
    {
        $this->setupMinSchemaWithDosen();

        $beforeUserCount = \App\Models\User::count();
        $beforeMhsCount = \App\Models\Mahasiswa::count();

        $this->seed(EwsDummyDataSeeder::class);

        // Seharusnya tambah ratusan user+mahasiswa
        $newUsers = \App\Models\User::count() - $beforeUserCount;
        $newMhs = \App\Models\Mahasiswa::count() - $beforeMhsCount;

        $this->assertGreaterThan(100, $newUsers, 'EwsDummyDataSeeder harus generate ratusan user');
        $this->assertEquals($newUsers, $newMhs, 'Setiap user baru harus punya 1 mahasiswa');
    }

    #[Test]
    public function ews_targeted_seeder_creates_ews_rows_for_all_scenarios(): void
    {
        $this->setupMinSchemaWithDosen();

        $beforeEws = \App\Models\EarlyWarningSystem::count();

        $this->seed(EwsTargetedScenarioSeeder::class);

        $afterEws = \App\Models\EarlyWarningSystem::count();
        $newEws = $afterEws - $beforeEws;

        // 50 skenario harus hasilkan 50 EWS rows
        $this->assertEquals(50, $newEws, 'EwsTargetedScenarioSeeder harus create 50 EWS rows');
    }

    #[Test]
    public function all_eenum_values_used_in_mahasiswa_are_valid(): void
    {
        $validStatuses = ['lulus', 'aktif', 'mangkir', 'tidak_aktif', 'cuti', 'DO'];

        $this->seed(RoleSeeder::class);
        $this->seed(ProdiSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->seed(\Database\Seeders\DosenSeeder::class);
        $this->seed(\Database\Seeders\MataKuliahPeminatanSeeder::class);
        $this->seed(\Database\Seeders\MataKuliahSeeder::class);
        $this->seed(\Database\Seeders\KelompokMataKuliahSeeder::class);
        $this->seed(EwsDummyDataSeeder::class);
        $this->seed(EwsTargetedScenarioSeeder::class);

        $invalidStatuses = \App\Models\Mahasiswa::query()
            ->whereNotIn('status_mahasiswa', $validStatuses)
            ->pluck('status_mahasiswa')
            ->unique()
            ->toArray();

        $this->assertEmpty($invalidStatuses, 'Tidak boleh ada status_mahasiswa di luar enum: '.implode(',', $invalidStatuses));
    }
}

