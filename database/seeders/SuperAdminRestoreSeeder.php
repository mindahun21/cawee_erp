<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminRestoreSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Ensure the super_admin role exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        // 2. Assign ALL current permissions in the database to super_admin
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        // 3. Ensure the admin user exists and has the role
        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            $admin->assignRole($superAdminRole);
            $this->command->info("Super Admin access restored for: {$admin->email}");
            $this->command->info("Permissions synced: " . $allPermissions->count());
        } else {
            $this->command->warn("Admin user (admin@admin.com) not found. Checking for any user...");
            $anyUser = User::first();
            if ($anyUser) {
                $anyUser->assignRole($superAdminRole);
                $this->command->info("Super Admin role assigned to first user: {$anyUser->email}");
            }
        }
    }
}
