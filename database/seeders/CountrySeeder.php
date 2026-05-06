<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Ethiopia', 'code' => 'ET'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'Djibouti', 'code' => 'DJ'],
            ['name' => 'Sudan', 'code' => 'SD'],
            ['name' => 'Somalia', 'code' => 'SO'],
            ['name' => 'USA', 'code' => 'US'],
            ['name' => 'UK', 'code' => 'GB'],
            ['name' => 'China', 'code' => 'CN'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['name' => $country['name']], $country);
        }
    }
}
