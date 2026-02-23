<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissionKoor = Permission::firstOrCreate(['name' => 'koor-ews', 'guard_name' => 'web']);
        $permissionDosen = Permission::firstOrCreate(['name' => 'dosen-ews', 'guard_name' => 'web']);
        $permissionMahasiswa = Permission::firstOrCreate(['name' => 'mahasiswa-ews', 'guard_name' => 'web']);

        // Create Roles and Assign Permissions
        $roleKoor = Role::firstOrCreate(['name' => 'koor', 'guard_name' => 'web']);
        $roleKoor->givePermissionTo($permissionKoor);

        $roleDosen = Role::firstOrCreate(['name' => 'dosen', 'guard_name' => 'web']);
        $roleDosen->givePermissionTo($permissionDosen);

        $roleMahasiswa = Role::firstOrCreate(['name' => 'mahasiswa', 'guard_name' => 'web']);
        $roleMahasiswa->givePermissionTo($permissionMahasiswa);

        // Super Admin
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleSuperAdmin->givePermissionTo($permissionKoor);

    }
}
