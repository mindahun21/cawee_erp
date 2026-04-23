<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Ensure super_admin role has all ME (Monitoring & Evaluation) permissions.
 * This is idempotent — safe to run multiple times.
 */
return new class extends Migration
{
    public function up(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();

        if (! $superAdmin) {
            return;
        }

        // All the ME resource permission names that FilamentShield generates
        $meModels = [
            'MeProject',
            'MeIndicator',
            'MeIndicatorReport',
            'MeBeneficiaryFeedback',
            'MeAlert',
            'MeDisaggregationCategory',
            'MeSurvey',
        ];

        $actions = [
            'ViewAny', 'View', 'Create', 'Update',
            'Delete', 'Restore', 'ForceDelete',
            'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder',
        ];

        $permissionNames = [];
        foreach ($meModels as $model) {
            foreach ($actions as $action) {
                $permissionNames[] = "{$action}:{$model}";
            }
        }

        // Only insert permissions that don't exist yet
        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Sync all ME permissions to super_admin
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $superAdmin->givePermissionTo($permissions);

        // Clear permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Nothing to rollback — permissions remain
    }
};
