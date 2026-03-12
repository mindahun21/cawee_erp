<?php

namespace Database\Seeders;

use App\Models\InventoryMovementReason;
use App\Models\InventoryMovementStatus;
use Illuminate\Database\Seeder;

class InventoryMovementSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'New Purchase', 'description' => 'Inventory movement for newly purchased items.'],
            ['name' => 'Donation', 'description' => 'Inventory movement for donated items.'],
            ['name' => 'Return', 'description' => 'Items returned to inventory.'],
            ['name' => 'Issue/Assignment', 'description' => 'Items issued or assigned to departments/locations.'],
            ['name' => 'Damage/Breakage', 'description' => 'Items reported as damaged or broken.'],
            ['name' => 'Expired', 'description' => 'Items that have expired.'],
            ['name' => 'Disposal', 'description' => 'Items disposed of.'],
            ['name' => 'Lost/Stolen', 'description' => 'Items reported as lost or stolen.'],
            ['name' => 'Audit Adjustment', 'description' => 'Adjustments made after an audit.'],
            ['name' => 'Other', 'description' => 'Other reasons for inventory movement.'],
        ];

        foreach ($reasons as $reason) {
            InventoryMovementReason::updateOrCreate(['name' => $reason['name']], $reason);
        }

        $statuses = [
            ['name' => 'Pending Approval', 'description' => 'Movement is waiting for approval.'],
            ['name' => 'In Transit', 'description' => 'Items are currently being moved.'],
            ['name' => 'Completed / Received', 'description' => 'Movement has been completed and items received.'],
            ['name' => 'Rejected', 'description' => 'Movement request has been rejected.'],
        ];

        foreach ($statuses as $status) {
            InventoryMovementStatus::updateOrCreate(['name' => $status['name']], $status);
        }
    }
}
