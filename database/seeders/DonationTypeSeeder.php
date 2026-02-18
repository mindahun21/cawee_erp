<?php

namespace Database\Seeders;

use App\Models\DonationType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DonationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'One-Time Donation',
                'code' => 'one_time',
                'description' => 'Single, non-recurring donation',
                'is_recurring' => false,
                'supports_gift_aid' => true,
                'tax_deductible' => true,
                'sort_order' => 1,
                'is_active' => true,
                'has_pledge_management' => false,
                'is_in_kind' => false,
                'requires_pledge_amount' => false,
                'requires_in_kind_description' => false,
            ],
            [
                'name' => 'Recurring Donation',
                'code' => 'recurring',
                'description' => 'Automatically recurring donation',
                'is_recurring' => true,
                'supports_gift_aid' => true,
                'tax_deductible' => true,
                'sort_order' => 2,
                'is_active' => true,
                'has_pledge_management' => false,
                'is_in_kind' => false,
                'requires_pledge_amount' => false,
                'requires_in_kind_description' => false,
            ],
            [
                'name' => 'Pledge',
                'code' => 'pledge',
                'description' => 'Pledged donation for future',
                'is_recurring' => false,
                'has_pledge_management' => true,
                'requires_pledge_amount' => true,
                'supports_gift_aid' => true,
                'tax_deductible' => true,
                'sort_order' => 3,
                'is_active' => true,
                'is_in_kind' => false,
                'requires_in_kind_description' => false,
            ],
            [
                'name' => 'In-Kind Donation',
                'code' => 'in_kind',
                'description' => 'Non-monetary donation',
                'is_recurring' => false,
                'is_in_kind' => true,
                'requires_in_kind_description' => true,
                'tax_deductible' => true,
                'sort_order' => 4,
                'is_active' => true,
                'has_pledge_management' => false,
                'requires_pledge_amount' => false,
                'supports_gift_aid' => false,
            ]
        ];

        foreach ($defaults as $type) {
            DonationType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
