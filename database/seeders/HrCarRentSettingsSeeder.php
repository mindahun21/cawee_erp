<?php

namespace Database\Seeders;

use App\Models\HrSettingOption;
use Illuminate\Database\Seeder;

class HrCarRentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            'branch_type' => ['Head Office', 'Regional Branch', 'Field Office', 'Satellite Office'],
            'agreement_payment_cycle' => ['Monthly', 'Quarterly', 'Semi-Annual', 'Annual'],
            'renewal_decision' => ['Renew', 'Amend', 'Terminate'],
            'utility_type' => ['Electricity', 'Water', 'Telephone', 'Internet', 'Other'],
            'utility_payment_cycle' => ['Monthly', 'Bi-Monthly', 'Quarterly'],
            'vehicle_service_type' => ['Preventive', 'Corrective', 'Repair', 'Inspection', 'Emergency'],
            'vehicle_urgency' => ['Low', 'Medium', 'High', 'Critical'],
            'maintenance_rule_type' => ['Mileage-Based', 'Time-Based', 'Hybrid'],
            'service_provider' => ['In-House Workshop', 'Authorized Dealer', 'Independent Garage'],
            'notification_channel' => ['ERP', 'Email', 'SMS'],
            'reminder_lead_time' => ['90', '60', '30', '7'],
        ];

        foreach ($options as $category => $labels) {
            foreach ($labels as $index => $label) {
                HrSettingOption::updateOrCreate(
                    ['category' => $category, 'label' => $label],
                    [
                        'code' => strtoupper(str_replace([' ', '-'], '_', $label)),
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

