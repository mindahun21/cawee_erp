<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaintenanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'maintainance', 'description' => 'Regular maintainance tasks'],
            ['name' => 'repair', 'description' => 'Fixing broken assets'],
            ['name' => 'upgrade', 'description' => 'Upgrading assets'],
            ['name' => 'PAT test', 'description' => 'Portable Appliance Testing'],
            ['name' => 'calibration', 'description' => 'Calibrating assets'],
        ];

        foreach ($types as $type) {
            DB::table('maintenance_types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
