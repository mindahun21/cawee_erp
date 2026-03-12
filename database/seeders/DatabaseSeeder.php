<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(HrRolesSeeder::class);

        // Default test user (kept from original, using firstOrCreate to prevent crash)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name'  => 'Test User', 'password' => Hash::make('password')]
        );

        // Super admin account
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole('super_admin');

        // HR seed data
        $this->call(HrSeeder::class);

        // Prefix settings
        $this->call(PrefixSettingSeeder::class);
    }
}
