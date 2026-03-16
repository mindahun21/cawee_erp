<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assetTypes = [
            ['name' => 'Assets', 'description' => 'Standard physical assets'],
            ['name' => 'Licenses', 'description' => 'Software licenses and digital rights'],
            ['name' => 'Accessories', 'description' => 'Asset accessories and peripherals'],
            ['name' => 'Consumables', 'description' => 'Items that are used up (e.g., ink, paper)'],
            ['name' => 'Components', 'description' => 'Parts of a larger asset'],
        ];

        foreach ($assetTypes as $type) {
            AssetType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }
}
