<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $buea = City::where('name', 'Buea')->first();

        $areas = [
            'Molyko',
            'Bonduma',
            'Great Soppo',
            'Muea',
            'Buea Town',
            'Sandpit',
            'Bokwango',
            'Buea Road',
            'Buea Hospital',
            'Village',
        ];

        foreach ($areas as $area) {
            Area::create([
                'city_id' => $buea->id,
                'name' => $area,
                'is_active' => true,
            ]);
        }

        // Limbe Areas
        $limbe = City::where('name', 'Limbe')->first();
        if ($limbe) {
            $limbeAreas = ['Down Beach', 'Mile 4', 'Mile 6', 'Limbe Town'];
            foreach ($limbeAreas as $area) {
                Area::create([
                    'city_id' => $limbe->id,
                    'name' => $area,
                    'is_active' => true,
                ]);
            }
        }
    }
}