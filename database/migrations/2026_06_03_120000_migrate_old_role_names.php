<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migrate role names from old ("dekan", "kaprodi") to new ("super_fakultas", "admin").
     *
     * Background: per .ai-system/plan/2026-06-03-rename-role-dekan-kaprodi.md, the
     * EWS role names are being renamed to match Spatie roles already used by
     * RoleSeeder (admin, super_fakultas) and existing permissions
     * (ews-admin, ews-super-fakultas, ews-mahasiswa).
     *
     * This migration:
     * - Re-points model_has_roles rows from old role IDs to new role IDs.
     * - Renames the old role row (does NOT delete) so that the old name is
     *   preserved as "*_old_DO_NOT_USE" for emergency rollback.
     *
     * Idempotent: safe to run multiple times. Checks existence at every step.
     *
     * Pre-conditions:
     * - Roles "super_fakultas" and "admin" MUST already exist in the `roles` table
     *   (created by RoleSeeder). If they don't exist, this migration will abort
     *   with a clear error message.
     */
    public function up(): void
    {
        $newSuperFakultas = DB::table('roles')->where('name', 'super_fakultas')->first();
        $newAdmin = DB::table('roles')->where('name', 'admin')->first();
        $oldDekan = DB::table('roles')->where('name', 'dekan')->first();
        $oldKaprodi = DB::table('roles')->where('name', 'kaprodi')->first();

        if (! $newSuperFakultas || ! $newAdmin) {
            throw new \RuntimeException(
                'Required new roles (super_fakultas, admin) not found. '.
                'Please run "php artisan db:seed --class=RoleSeeder" first to create them.'
            );
        }

        if ($oldDekan) {
            $migrated = DB::table('model_has_roles')
                ->where('role_id', $oldDekan->id)
                ->update(['role_id' => $newSuperFakultas->id]);

            DB::table('roles')
                ->where('id', $oldDekan->id)
                ->update(['name' => 'dekan_old_DO_NOT_USE']);

            Log::info("Migration: migrated {$migrated} user(s) from role 'dekan' to 'super_fakultas'.");
            echo "  ✔ Migrated {$migrated} user(s) from 'dekan' → 'super_fakultas'\n";
        } else {
            echo "  ℹ Old role 'dekan' not found — assuming already migrated. Skipping.\n";
        }

        if ($oldKaprodi) {
            $migrated = DB::table('model_has_roles')
                ->where('role_id', $oldKaprodi->id)
                ->update(['role_id' => $newAdmin->id]);

            DB::table('roles')
                ->where('id', $oldKaprodi->id)
                ->update(['name' => 'kaprodi_old_DO_NOT_USE']);

            Log::info("Migration: migrated {$migrated} user(s) from role 'kaprodi' to 'admin'.");
            echo "  ✔ Migrated {$migrated} user(s) from 'kaprodi' → 'admin'\n";
        } else {
            echo "  ℹ Old role 'kaprodi' not found — assuming already migrated. Skipping.\n";
        }

        $remainingOldDekan = DB::table('roles')->where('name', 'dekan')->count();
        $remainingOldKaprodi = DB::table('roles')->where('name', 'kaprodi')->count();

        if ($remainingOldDekan > 0 || $remainingOldKaprodi > 0) {
            throw new \RuntimeException(
                'Post-migration check FAILED. Old role names still present. '.
                'Manual intervention required.'
            );
        }

        echo "  ✔ Migration complete. Old roles renamed to *_old_DO_NOT_USE for rollback safety.\n";
    }

    /**
     * Reverse the migration: restore old role names and re-point user assignments.
     *
     * WARNING: This will FAIL if any new user has been assigned to
     * "super_fakultas" or "admin" between the up() and down() runs, because
     * we cannot determine which of those users originally came from "dekan"
     * vs "kaprodi". Use only for true emergency rollback.
     */
    public function down(): void
    {
        $oldDekanRenamed = DB::table('roles')->where('name', 'dekan_old_DO_NOT_USE')->first();
        $oldKaprodiRenamed = DB::table('roles')->where('name', 'kaprodi_old_DO_NOT_USE')->first();

        if ($oldDekanRenamed) {
            $superFakultas = DB::table('roles')->where('name', 'super_fakultas')->first();
            if ($superFakultas) {
                $migrated = DB::table('model_has_roles')
                    ->where('role_id', $superFakultas->id)
                    ->update(['role_id' => $oldDekanRenamed->id]);
                echo "  ⚠ Reverted {$migrated} user(s) from 'super_fakultas' → 'dekan'\n";
            }
            DB::table('roles')->where('id', $oldDekanRenamed->id)->update(['name' => 'dekan']);
            echo "  ⚠ Renamed 'dekan_old_DO_NOT_USE' → 'dekan'\n";
        }

        if ($oldKaprodiRenamed) {
            $admin = DB::table('roles')->where('name', 'admin')->first();
            if ($admin) {
                $migrated = DB::table('model_has_roles')
                    ->where('role_id', $admin->id)
                    ->update(['role_id' => $oldKaprodiRenamed->id]);
                echo "  ⚠ Reverted {$migrated} user(s) from 'admin' → 'kaprodi'\n";
            }
            DB::table('roles')->where('id', $oldKaprodiRenamed->id)->update(['name' => 'kaprodi']);
            echo "  ⚠ Renamed 'kaprodi_old_DO_NOT_USE' → 'kaprodi'\n";
        }
    }
};
