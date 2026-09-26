<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CountrySeeder::class,
            RegionSeeder::class,
            CitySeeder::class,
            AreaSeeder::class,
            CategorySeeder::class,
            BusinessSeeder::class,
            SettingsSeeder::class,
            ContactSeeder::class,
            ServiceSeeder::class,
            FixRatingsSeeder::class,
            PlanSeeder::class,
        ]);
    }
}