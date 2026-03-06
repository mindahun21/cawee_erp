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
                $query->where('name', 'like', '%:Me%')
                    ->orWhere('name', 'like', 'page_Me%')
                    ->orWhere('name', 'like', 'page_%WeeklyReport%');
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
