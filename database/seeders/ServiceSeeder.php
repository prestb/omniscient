<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ListingService;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('name', 'ABC Pharmacy')->first();
        
        if ($business) {
            // PHASE 11 / WAVE 1B — services are listing-owned.
            $listing = $business->primaryListing();

            if (!$listing) {
                return;
            }

            $services = [
                'Prescription Medicines',
                'Over-the-counter Medicines',
                'Health Products',
                'Baby Products',
                'Vitamins and Supplements',
                'First Aid Supplies',
            ];
            
            foreach ($services as $index => $service) {
                ListingService::create([
                    'listing_id' => $listing->id,
                    'name' => $service,
                    'description' => "Professional {$service}",
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}