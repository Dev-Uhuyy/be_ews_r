<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed roles & permissions untuk sistem EWS.
     *
     * Role EWS:
     *  - mahasiswa      → melihat status EWS, KHS/KRS, dan mengajukan tindak lanjut
     *  - admin          → dashboard EWS prodi, manage status, verifikasi tindak lanjut
     *  - super_fakultas → dashboard level fakultas, statistik semua prodi
     *
     * Catatan: sti-api sudah punya role 'mahasiswa', 'dosen', 'koordinator', 'mitra'.
     * Seeder ini menambahkan role EWS baru tanpa mengganggu role existing.
     */
    public function run(): void
    {
        // Reset cache permission
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions EWS ───────────────────────────────────────────────────
        $permEwsMahasiswa = Permission::firstOrCreate(['name' => 'ews-mahasiswa', 'guard_name' => 'web']);
        $permEwsAdmin = Permission::firstOrCreate(['name' => 'ews-admin',   'guard_name' => 'web']);
        $permEwsSuperFakultass = Permission::firstOrCreate(['name' => 'ews-super-fakultass',     'guard_name' => 'web']);

        // ─── Role: mahasiswa ────────────────────────────────────────────────────
        // Role ini sudah ada di DB dari sti-api. Kita hanya tambahkan permission EWS.
        $roleMahasiswa = Role::firstOrCreate(['name' => 'mahasiswa', 'guard_name' => 'web']);
        $roleMahasiswa->givePermissionTo($permEwsMahasiswa);

        // ─── Role: admin (Kepala Program Studi) ───────────────────────────────
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo($permEwsAdmin);
        $roleAdmin->givePermissionTo($permEwsMahasiswa);

        // ─── Role: super_fakultas (Dekan Fakultas) ───────────────────────────────
        $roleSuperFakultass = Role::firstOrCreate(['name' => 'super_fakultas', 'guard_name' => 'web']);
        $roleSuperFakultass->givePermissionTo($permEwsSuperFakultass);
        $roleSuperFakultass->givePermissionTo($permEwsAdmin);
        $roleSuperFakultass->givePermissionTo($permEwsMahasiswa);

        $this->command->info('✔ RoleSeeder: 3 role EWS siap (mahasiswa, admin, super_fakultas).');
    }
}
