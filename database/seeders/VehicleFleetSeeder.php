<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use App\Models\VehicleStatus;
use Illuminate\Database\Seeder;

class VehicleFleetSeeder extends Seeder
{
    public function run(): void
    {
        // Vehicle Types
        $types = [
            'Sedan',
            'SUV',
            'Truck',
            'Van',
            'Pickup',
            'Bus',
            'Motorcycle',
            'Machinery',
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(['name' => $type]);
        }

        // Vehicle Statuses
        $statuses = [
            'Active',
            'In Maintenance',
            'Repairing',
            'Out of Service',
            'Accident',
            'Disposed',
            'Sold',
        ];

        foreach ($statuses as $status) {
            VehicleStatus::firstOrCreate(['name' => $status]);
        }
    }
}
