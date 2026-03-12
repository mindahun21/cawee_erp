<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'description' => 'Standard unit of mass (kg)'],
            ['name' => 'Gram', 'description' => 'Smaller unit of mass (g)'],
            ['name' => 'Liter', 'description' => 'Standard unit of volume for liquids (l)'],
            ['name' => 'Milliliter', 'description' => 'Smaller unit of volume for liquids (ml)'],
            ['name' => 'Meter', 'description' => 'Standard unit of length (m)'],
            ['name' => 'Centimeter', 'description' => 'Smaller unit of length (cm)'],
            ['name' => 'Piece', 'description' => 'Individual items counted separately (pcs)'],
            ['name' => 'Box', 'description' => 'Items grouped in a box'],
            ['name' => 'Pack', 'description' => 'Items grouped in a pack (pk)'],
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::updateOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
