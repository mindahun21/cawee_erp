<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // To assign the permissions properly, we can just run the seeder
        // that handles the complex logic of creating all 5 finance roles
        // and granting them (plus super_admin) the proper permissions.
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Finance\\FinancePermissionsSeeder',
            '--force' => true, // Needed if running in production
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove the super_admin role, but we could remove the finance roles
        Role::whereIn('name', [
            'cashier',
            'finance_officer',
            'finance_manager',
            'cfo',
            'internal_auditor'
        ])->delete();
    }
};
