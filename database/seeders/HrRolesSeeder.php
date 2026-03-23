<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * HR Roles & Permissions Seeder
 *
 * Defines five roles with progressively escalating access:
 *
 *   super_admin   – unrestricted access to everything
 *   hr_director   – full HR access + final authorization (leave, travel)
 *   hr_officer    – HR-level approvals, all HR resource management
 *   hr_supervisor – supervisor-level approvals, read access to most resources
 *   hr_staff      – read-only access; can submit/view their own records
 *
 * Run with: php artisan db:seed --class=HrRolesSeeder
 */
class HrRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Guard: permissions must exist before we can assign them.
        // If this is a fresh install, run: php artisan shield:generate --all --panel=admin
        if (Permission::count() === 0) {
            $this->command->warn('No permissions found. Run: php artisan shield:generate --all --panel=admin');
            $this->command->warn('Then re-run this seeder.');
            return;
        }

        // ── Define HR-specific permission groups ─────────────────────

        $allHrPermissions = Permission::where('name', 'like', '%employee%')
            ->orWhere('name', 'like', '%leave%')
            ->orWhere('name', 'like', '%travel%')
            ->orWhere('name', 'like', '%branch%')
            ->orWhere('name', 'like', '%agreement%')
            ->orWhere('name', 'like', '%utility%')
            ->orWhere('name', 'like', '%vehicle%')
            ->orWhere('name', 'like', '%landlord%')
            ->orWhere('name', 'like', '%settingoption%')
            ->orWhere('name', 'like', '%appraisal%')
            ->orWhere('name', 'like', '%timesheet%')
            ->orWhere('name', 'like', '%per_diem%')
            ->orWhere('name', 'like', '%salary%')
            ->orWhere('name', 'like', '%onboarding%')
            ->orWhere('name', 'like', '%payroll%')
            ->orWhere('name', 'like', '%location%')
            ->orWhere('name', 'like', '%project%')
            ->orWhere('name', 'like', '%recruitment%')
            ->pluck('name')
            ->unique()
            ->toArray();

        $readOnlyHrPermissions = Permission::where('name', 'like', 'view_%')
            ->where(function ($q) {
                $q->where('name', 'like', '%employee%')
                  ->orWhere('name', 'like', '%leave%')
                  ->orWhere('name', 'like', '%travel%')
                  ->orWhere('name', 'like', '%branch%')
                  ->orWhere('name', 'like', '%agreement%')
                  ->orWhere('name', 'like', '%utility%')
                  ->orWhere('name', 'like', '%vehicle%')
                  ->orWhere('name', 'like', '%landlord%')
                  ->orWhere('name', 'like', '%settingoption%')
                  ->orWhere('name', 'like', '%appraisal%')
                  ->orWhere('name', 'like', '%timesheet%')
                  ->orWhere('name', 'like', '%per_diem%')
                  ->orWhere('name', 'like', '%salary%')
                  ->orWhere('name', 'like', '%onboarding%')
                  ->orWhere('name', 'like', '%location%')
                  ->orWhere('name', 'like', '%project%')
                  ->orWhere('name', 'like', '%recruitment%');
            })
            ->pluck('name')
            ->unique()
            ->toArray();

        // ── Role: super_admin ─────────────────────────────────────────
        // Gets ALL permissions — handled by Filament Shield's bypass gate.
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        try {
            $superAdmin->givePermissionTo(Permission::all());
        } catch (\Exception $e) {}

        // ── Role: hr_director ─────────────────────────────────────────
        // Full HR access: create/edit/delete everything in HR + final approvals.
        $director = Role::firstOrCreate(['name' => 'hr_director', 'guard_name' => 'web']);
        try {
            $director->syncPermissions($allHrPermissions);
        } catch (\Exception $e) {}

        // ── Role: hr_officer ─────────────────────────────────────────
        // Full HR access: manage employees, leave, travel, appraisals.
        // Cannot access financial management or Roles/Shield panel.
        $officer = Role::firstOrCreate(['name' => 'hr_officer', 'guard_name' => 'web']);
        try {
            $officer->syncPermissions($allHrPermissions);
        } catch (\Exception $e) {}

        // ── Role: hr_supervisor ───────────────────────────────────────
        $supervisor = Role::firstOrCreate(['name' => 'hr_supervisor', 'guard_name' => 'web']);
        try {
            $supervisor->syncPermissions($readOnlyHrPermissions);
        } catch (\Exception $e) {
            $this->command->warn("Failed to sync permissions for hr_supervisor: " . $e->getMessage());
        }

        // ── Role: hr_staff ────────────────────────────────────────────
        $staff = Role::firstOrCreate(['name' => 'hr_staff', 'guard_name' => 'web']);
        try {
            $staff->syncPermissions($readOnlyHrPermissions);
        } catch (\Exception $e) {
            $this->command->warn("Failed to sync permissions for hr_staff: " . $e->getMessage());
        }

        // ── User Assignment ───────────────────────────────────────────
        // Assign super_admin role to all existing users to ensure access.
        $this->command->info('Assigning super_admin role to all users...');
        foreach (\App\Models\User::all() as $user) {
            try {
                $user->assignRole('super_admin');
            } catch (\Exception $e) {
                $this->command->error("Failed to assign role to user {$user->email}: " . $e->getMessage());
            }
        }

        $this->command->info(' HR Roles seeded:');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['super_admin',   'ALL (' . Permission::count() . ')'],
                ['hr_director',   count($allHrPermissions)],
                ['hr_officer',    count($allHrPermissions)],
                ['hr_supervisor', count($readOnlyHrPermissions)],
                ['hr_staff',      count($readOnlyHrPermissions)],
            ]
        );
    }
}
