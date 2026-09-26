<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        // Get the owner user
        $owner = User::where('email', 'owner@omniscient.com')->first();
        
        if (!$owner) {
            return;
        }

        // Create ABC Pharmacy
        $pharmacy = Business::create([
            'owner_id' => $owner->id,
            'name' => 'ABC Pharmacy',
            'description' => 'Leading pharmacy chain in Cameroon offering quality healthcare products and services.',
            'email' => 'info@abcpharmacy.cm',
            'website' => 'https://abcpharmacy.cm',
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now(),
        ]);

        // Attach categories
        $pharmacyCategories = Category::whereIn('name', ['Pharmacies', 'Health & Medical'])->get();
        $pharmacy->categories()->attach($pharmacyCategories->pluck('id'), ['is_primary' => true]);

        // Create branches for ABC Pharmacy
        $branches = [
            [
                'name' => 'Buea Branch',
                'is_primary' => true,
                'city' => 'Buea',
                'area' => 'Molyko',
                'address' => '123 Molyko Main Road',
                'phone' => '+237 699 123 456',
                'whatsapp' => '+237 699 123 456',
            ],
            [
                'name' => 'Limbe Branch',
                'is_primary' => false,
                'city' => 'Limbe',
                'area' => 'Down Beach',
                'address' => '456 Beach Road',
                'phone' => '+237 699 123 457',
                'whatsapp' => '+237 699 123 457',
            ],
            [
                'name' => 'Douala Branch',
                'is_primary' => false,
                'city' => 'Douala',
                'area' => null, // No area for Douala in our seeder
                'address' => '789 Akwa Boulevard',
                'phone' => '+237 699 123 458',
                'whatsapp' => '+237 699 123 458',
            ],
        ];

        foreach ($branches as $branchData) {
            $city = \App\Models\City::where('name', $branchData['city'])->first();
            
            if (!$city) {
                continue;
            }

            $area = null;
            if ($branchData['area']) {
                $area = \App\Models\Area::where('name', $branchData['area'])
                    ->where('city_id', $city->id)
                    ->first();
            }

            Branch::create([
                'business_id' => $pharmacy->id,
                'name' => $branchData['name'],
                'is_primary' => $branchData['is_primary'],
                'country_id' => $city->region->country_id,
                'region_id' => $city->region_id,
                'city_id' => $city->id,
                'area_id' => $area ? $area->id : null,
                'address' => $branchData['address'],
                'phone' => $branchData['phone'],
                'whatsapp' => $branchData['whatsapp'],
                'status' => 'active',
                'sort_order' => $branchData['is_primary'] ? 0 : 1,
            ]);
        }

        // Create Healthy Life Hospital
        $hospital = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Healthy Life Hospital',
            'description' => 'State-of-the-art hospital providing comprehensive healthcare services in Buea.',
            'email' => 'info@healthylife.cm',
            'website' => 'https://healthylife.cm',
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $hospitalCategories = Category::whereIn('name', ['Hospitals', 'Health & Medical'])->get();
        $hospital->categories()->attach($hospitalCategories->pluck('id'), ['is_primary' => true]);

        // Create branch for hospital
        $buea = \App\Models\City::where('name', 'Buea')->first();
        $molyko = \App\Models\Area::where('name', 'Molyko')->first();

        Branch::create([
            'business_id' => $hospital->id,
            'name' => 'Main Hospital',
            'is_primary' => true,
            'country_id' => $buea->region->country_id,
            'region_id' => $buea->region_id,
            'city_id' => $buea->id,
            'area_id' => $molyko ? $molyko->id : null,
            'address' => '456 Hospital Road, Molyko',
            'phone' => '+237 699 123 459',
            'whatsapp' => '+237 699 123 459',
            'status' => 'active',
        ]);

        // Create Delicious Restaurant
        $restaurant = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Delicious Restaurant',
            'description' => 'Award-winning restaurant serving authentic Cameroonian and international cuisine.',
            'email' => 'info@delicious.cm',
            'website' => 'https://delicious.cm',
            'status' => 'published',
            'is_featured' => false,
            'published_at' => now(),
        ]);

        $restaurantCategories = Category::whereIn('name', ['Restaurants', 'Food & Dining'])->get();
        $restaurant->categories()->attach($restaurantCategories->pluck('id'), ['is_primary' => true]);

        $buea = \App\Models\City::where('name', 'Buea')->first();
        $greatSoppo = \App\Models\Area::where('name', 'Great Soppo')->first();

        Branch::create([
            'business_id' => $restaurant->id,
            'name' => 'Buea Branch',
            'is_primary' => true,
            'country_id' => $buea->region->country_id,
            'region_id' => $buea->region_id,
            'city_id' => $buea->id,
            'area_id' => $greatSoppo ? $greatSoppo->id : null,
            'address' => '789 Great Soppo Road',
            'phone' => '+237 699 123 460',
            'whatsapp' => '+237 699 123 460',
            'status' => 'active',
        ]);

        // Create Pending Business (for review)
        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Tech Hub Cameroon',
            'description' => 'Co-working space and tech innovation hub in Buea.',
            'email' => 'info@techhub.cm',
            'website' => 'https://techhub.cm',
            'status' => 'submitted',
            'is_featured' => false,
            'submitted_at' => now(),
        ]);

        // Create Draft Business
        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Green Energy Solutions',
            'description' => 'Solar panel installation and renewable energy solutions.',
            'email' => 'info@greenenergy.cm',
            'status' => 'draft',
            'is_featured' => false,
        ]);
    }
}