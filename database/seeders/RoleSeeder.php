<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed roles & permissions untuk sistem EWS.
     *
     * Role EWS:
     *  - mahasiswa  → melihat status EWS, KHS/KRS, dan mengajukan tindak lanjut
     *  - kaprodi    → dashboard EWS prodi, manage status, verifikasi tindak lanjut
     *  - dekan      → dashboard level fakultas, statistik semua prodi
     *
     * Catatan: sti-api sudah punya role 'mahasiswa', 'dosen', 'koordinator', 'mitra'.
     * Seeder ini menambahkan role EWS baru tanpa mengganggu role existing.
     */
    public function run(): void
    {
        // Reset cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions EWS ───────────────────────────────────────────────────
        $permEwsMahasiswa = Permission::firstOrCreate(['name' => 'ews-mahasiswa', 'guard_name' => 'web']);
        $permEwsKaprodi   = Permission::firstOrCreate(['name' => 'ews-kaprodi',   'guard_name' => 'web']);
        $permEwsDekan     = Permission::firstOrCreate(['name' => 'ews-dekan',     'guard_name' => 'web']);

        // ─── Role: mahasiswa ────────────────────────────────────────────────────
        // Role ini sudah ada di DB dari sti-api. Kita hanya tambahkan permission EWS.
        $roleMahasiswa = Role::firstOrCreate(['name' => 'mahasiswa', 'guard_name' => 'web']);
        $roleMahasiswa->givePermissionTo($permEwsMahasiswa);

        // ─── Role: kaprodi (baru — Kepala Program Studi) ───────────────────────
        $roleKaprodi = Role::firstOrCreate(['name' => 'kaprodi', 'guard_name' => 'web']);
        $roleKaprodi->givePermissionTo($permEwsKaprodi);
        // kaprodi juga bisa akses fitur mahasiswa
        $roleKaprodi->givePermissionTo($permEwsMahasiswa);

        // ─── Role: dekan (baru — Dekan Fakultas) ───────────────────────────────
        $roleDekan = Role::firstOrCreate(['name' => 'dekan', 'guard_name' => 'web']);
        $roleDekan->givePermissionTo($permEwsDekan);
        // dekan bisa akses semua
        $roleDekan->givePermissionTo($permEwsKaprodi);
        $roleDekan->givePermissionTo($permEwsMahasiswa);

        $this->command->info('✔ RoleSeeder: 3 role EWS siap (mahasiswa, kaprodi, dekan).');
    }
}
