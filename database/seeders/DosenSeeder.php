<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

class DosenSeeder extends Seeder
{
    /**
     * Seed dosen untuk SETIAP user berkategori 'dosen' di DB.
     *
     * DosenSeeder tidak hardcode user_id lagi (vulnerable to schema change).
     * Lookup by role 'dosen' + prodi_id. User dosen di-seed oleh UserSeeder
     * dengan email dosen_<KODEPRODI>_<N>@ews.com (6 per prodi).
     */
    public function run(): void
    {
        $prodis = Prodi::all();
        $dosenRole = \Spatie\Permission\Models\Role::where('name', 'dosen')->first();

        if (! $dosenRole) {
            $this->command->warn('⚠ Role "dosen" belum ada. Jalankan RoleSeeder dulu.');

            return;
        }

        // Ambil semua user dengan role 'dosen'
        $dosenUsers = User::role('dosen')->get();

        if ($dosenUsers->isEmpty()) {
            $this->command->warn('⚠ Tidak ada user dengan role "dosen". Jalankan UserSeeder dulu.');

            return;
        }

        $count = 0;
        $nppTracker = []; // Track NPP yang dipakai per prodi untuk hindari duplicate
        foreach ($dosenUsers as $i => $user) {
            $prodi = $prodis->firstWhere('id', $user->prodi_id);

            if (! $prodi) {
                $this->command->warn("⚠ User dosen {$user->email} tidak punya prodi, skip.");

                continue;
            }

            // Jika dosen untuk user ini sudah ada, skip
            $existing = Dosen::where('user_id', $user->id)->first();
            if ($existing) {
                $count++;
                continue;
            }

            // Generate NPP unik: increment kalau sudah dipakai
            $userIndex = ($nppTracker[$prodi->kode_prodi] ?? 0) + 1;
            $nppTracker[$prodi->kode_prodi] = $userIndex;

            // Cek kalau NPP sudah dipakai dosen LAIN
            while (Dosen::where('npp', "{$prodi->kode_prodi}.".str_pad((string) $userIndex, 3, '0', STR_PAD_LEFT))->exists()) {
                $userIndex++;
                $nppTracker[$prodi->kode_prodi] = $userIndex;
            }

            $npp = "{$prodi->kode_prodi}.".str_pad((string) $userIndex, 3, '0', STR_PAD_LEFT);

            Dosen::create([
                'user_id' => $user->id,
                'prodi_id' => $prodi->id,
                'npp' => $npp,
                'gelar_depan' => $this->getGelarDepan($userIndex),
                'gelar_belakang' => $this->getGelarBelakang($userIndex),
                'bidang_kajian' => $this->getBidangKajian($prodi->kode_prodi, $userIndex),
                'status_dosen' => 'Aktif',
                'jumlah_lulusan' => fake()->numberBetween(0, 50),
                'lulus_persen' => fake()->randomFloat(2, 50, 95),
                'total_mhs_ta' => fake()->numberBetween(0, 15),
                'total_mhs_saat_ini' => fake()->numberBetween(0, 12),
                'kuota_ta_baru' => fake()->numberBetween(2, 8),
            ]);
            $count++;
        }

        $this->command->info("✔ DosenSeeder: {$count} dosen tersedia untuk ".count($prodis)." prodi.");
    }

    private function getGelarDepan(int $i): string
    {
        $gelar = ['', 'Dr.', 'Prof.', 'Dr.', '', 'Prof.'];

        return $gelar[($i - 1) % 6];
    }

    private function getGelarBelakang(int $i): string
    {
        $gelar = ['M.Kom.', 'M.T.', 'M.Cs.', 'Ph.D.', 'M.Si.', 'M.Kom.'];

        return $gelar[($i - 1) % 6];
    }

    private function getBidangKajian(string $kodeProdi, int $i): string
    {
        // Peminatan dari prodi yang ada
        $map = [
            'A11' => ['SC', 'RPLD', 'SK3D'],
            'A12' => ['EIS', 'EB', 'DATA'],
            'A14' => ['DG', 'MM', 'AN'],
            'A15' => ['PR', 'JR', 'BROAD'],
            'A16' => ['PRD', 'VFX', 'AUD'],
            'A17' => ['2D', '3D', 'GAME'],
            'A18' => ['PJJ-A', 'PJJ-B', 'PJJ-C'],
            'A22' => ['A22-A', 'A22-B', 'A22-C'],
            'P31' => ['RISET', 'TERAPAN', 'KONS'],
            'P41' => ['P41-A', 'P41-B', 'P41-C'],
        ];
        $options = $map[$kodeProdi] ?? ['UMUM'];

        return $options[($i - 1) % 3];
    }
}
