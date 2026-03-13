<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetRefactorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = ['New', 'Good', 'Fair', 'Poor', 'Broken'];
        foreach ($conditions as $condition) {
            \App\Models\AssetCondition::firstOrCreate(['name' => $condition]);
        }

        $statuses = [
            'available' => 'Available',
            'assigned' => 'Assigned',
            'maintenance' => 'Maintenance',
            'disposed' => 'Disposed',
            'lost' => 'Lost',
        ];
        foreach ($statuses as $id => $name) {
            \App\Models\AssetStatus::firstOrCreate(['name' => $name]);
        }

        $acquisitionTypes = ['Purchase', 'Donation', 'Lease'];
        foreach ($acquisitionTypes as $type) {
            \App\Models\AcquisitionType::firstOrCreate(['name' => $type]);
        }

        // Migrate existing data
        $assets = \App\Models\Asset::all();
        foreach ($assets as $asset) {
            $condition = \App\Models\AssetCondition::where('name', $asset->condition)->first();
            $statusName = $statuses[strtolower($asset->status)] ?? $asset->status;
            $status = \App\Models\AssetStatus::where('name', $statusName)->first();
            $acquisitionType = \App\Models\AcquisitionType::where('name', $asset->acquisition_type)->first();

            $asset->update([
                'asset_condition_id' => $condition?->id,
                'asset_status_id' => $status?->id,
                'acquisition_type_id' => $acquisitionType?->id,
            ]);
        }
    }
}
