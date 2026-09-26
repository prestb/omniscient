<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $cameroon = Country::where('code', 'CM')->first();

        $regions = [
            'Adamawa',
            'Centre',
            'East',
            'Far North',
            'Littoral',
            'North',
            'North-West',
            'South',
            'South-West',
            'West',
        ];

        foreach ($regions as $region) {
            Region::create([
                'country_id' => $cameroon->id,
                'name' => $region,
                'is_active' => true,
            ]);
        }
    }
}