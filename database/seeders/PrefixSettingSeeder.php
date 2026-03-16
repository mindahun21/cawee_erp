<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrefixSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'inventory_sku',
                'display_name' => 'Inventory SKU',
                'prefix' => 'SKU-',
                'next_number' => 1,
            ],
            [
                'key' => 'warehouse_code',
                'display_name' => 'Warehouse Code',
                'prefix' => 'WH-',
                'next_number' => 1,
            ],
            [
                'key' => 'asset_serial_number',
                'display_name' => 'Asset Serial Number',
                'prefix' => 'SN-',
                'next_number' => 1,
            ],
            [
                'key' => 'asset_barcode',
                'display_name' => 'Asset Barcode',
                'prefix' => 'BC-',
                'next_number' => 1,
            ],
            [
                'key' => 'asset_qr_code',
                'display_name' => 'Asset QR Code',
                'prefix' => 'QR-',
                'next_number' => 1,
            ],
            [
                'key' => 'asset_rfid_tag',
                'display_name' => 'Asset RFID Tag',
                'prefix' => 'RFID-',
                'next_number' => 1,
            ],
        ];

        foreach ($settings as $setting) {
            \App\Models\PrefixSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
