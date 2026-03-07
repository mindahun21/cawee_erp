<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class MeSuperAdminAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $mePermissions = Permission::query()
            ->where(function ($query) {
                // Shield generates permissions in lowercase underscore format e.g. view_me_indicator
                $query->where('name', 'like', '%_me_%')        // resource actions: view_me_indicator, create_me_project, etc.
                    ->orWhere('name', 'like', 'view_any_me_%') // viewAny_ prefix variant
                    ->orWhere('name', 'like', 'page_MeDashboard')       // MeDashboard page
                    ->orWhere('name', 'like', 'page_Me%')               // any other Me* pages
                    ->orWhere('name', 'like', 'page_%WeeklyReport%')    // ImportWeeklyReport page
                    ->orWhere('name', 'like', 'page_%ImportWeekly%');   // alternate naming
            })
            ->get();

        if ($mePermissions->isEmpty()) {
            $this->command?->warn('No M&E permissions found. Run: php artisan shield:generate --all --panel=admin');

            return;
        }

        $superAdmin->givePermissionTo($mePermissions);

        $this->command?->info('M&E permissions granted to super_admin: ' . $mePermissions->count());
    }
}
