<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetSuperAdminAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure the super_admin role exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        // 2. Define the permissions needed for Inventory and Asset
        $resources = ['Asset', 'InventoryMovement', 'AssetAssignment'];
        $actions = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'Replicate', 'Reorder'];
        
        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}:{$resource}";
            }
        }

        // Add permission for the Inventory Report page
        $permissions[] = 'view_InventoryReport';

        // 3. Create and assign permissions to the role
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
            $superAdminRole->givePermissionTo($permission);
        }

        // 4. Assign the role to the first user or a specific user
        $user = User::first(); // Assumes the first user is the admin
        if ($user) {
            $user->assignRole($superAdminRole);
            $this->command->info("Permissions and 'super_admin' role assigned to user: {$user->email}");
        } else {
            $this->command->warn("No user found to assign the 'super_admin' role.");
        }

        $this->command->info('AssetSuperAdminAccessSeeder completed successfully.');
    }
}
