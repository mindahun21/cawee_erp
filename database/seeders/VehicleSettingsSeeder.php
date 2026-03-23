<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;
use App\Models\VehicleStatus;
use App\Models\VehicleServiceType;

class VehicleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 5 Vehicle Types
        $types = [
            ['name' => 'Sedan', 'is_active' => true],
            ['name' => 'SUV', 'is_active' => true],
            ['name' => 'Pickup Truck', 'is_active' => true],
            ['name' => 'Van / Minibus', 'is_active' => true],
            ['name' => 'Motorcycle', 'is_active' => true],
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(['name' => $type['name']], $type);
        }

        // 5 Vehicle Statuses
        $statuses = [
            ['name' => 'Active / In Use', 'color' => '#10b981', 'is_active' => true],
            ['name' => 'Pending Maintenance', 'color' => '#f59e0b', 'is_active' => true],
            ['name' => 'In Repair / Workshop', 'color' => '#ef4444', 'is_active' => true],
            ['name' => 'Out of Service', 'color' => '#6b7280', 'is_active' => true],
            ['name' => 'Sold / Disposed', 'color' => '#000000', 'is_active' => true],
        ];

        foreach ($statuses as $status) {
            VehicleStatus::firstOrCreate(['name' => $status['name']], $status);
        }

        // 5 Vehicle Service Types
        $services = [
            ['name' => 'Routine Oil Change', 'is_active' => true],
            ['name' => 'Tire Replacement & Balance', 'is_active' => true],
            ['name' => 'Brake Inspection', 'is_active' => true],
            ['name' => 'Engine / Transmission Repair', 'is_active' => true],
            ['name' => 'General Technical Inspection', 'is_active' => true],
        ];

        foreach ($services as $service) {
            VehicleServiceType::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
