<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // South-West Region
        $southWest = Region::where('name', 'South-West')->first();

        $cities = [
            'Buea',
            'Limbe',
            'Kumba',
            'Tiko',
            'Mamfe',
            'Muyuka',
            'Ekondo Titi',
            'Bamenda', // North-West
            'Douala', // Littoral
            'Yaounde', // Centre
        ];

        foreach ($cities as $city) {
            // Find or create region
            $region = Region::firstOrCreate(
                ['name' => $this->getRegionForCity($city)],
                ['country_id' => $southWest->country_id, 'is_active' => true]
            );

            City::create([
                'region_id' => $region->id,
                'name' => $city,
                'is_active' => true,
            ]);
        }
    }

    private function getRegionForCity($city)
    {
        $map = [
            'Buea' => 'South-West',
            'Limbe' => 'South-West',
            'Kumba' => 'South-West',
            'Tiko' => 'South-West',
            'Mamfe' => 'South-West',
            'Muyuka' => 'South-West',
            'Ekondo Titi' => 'South-West',
            'Bamenda' => 'North-West',
            'Douala' => 'Littoral',
            'Yaounde' => 'Centre',
        ];

        return $map[$city] ?? 'South-West';
    }
}