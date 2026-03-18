<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LeavePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();

        if (!$superAdmin) {
            $this->command->error("super_admin role not found!");
            return;
        }

        $resources = [
            'HrLeaveType',
            'HrLeaveRequest',
            'HrLeavePolicy',
        ];

        $actions = [
            'ViewAny', 'View', 'Create', 'Update', 'Delete',
            'ForceDelete', 'ForceDeleteAny', 'Restore', 'RestoreAny',
            'Replicate', 'Reorder',
        ];

        $created  = 0;
        $assigned = 0;

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $name = "{$action}:{$resource}";
                $perm = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );

                if ($perm->wasRecentlyCreated) {
                    $created++;
                }

                if (!$superAdmin->hasPermissionTo($perm)) {
                    $superAdmin->givePermissionTo($perm);
                    $assigned++;
                }
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info("Done! Created {$created} new permissions, assigned {$assigned} to super_admin.");
    }
}
