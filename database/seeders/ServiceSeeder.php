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
            // PHASE 11 / WAVE 1D-3 — services are Listing-owned. Resolve the demo
            // Listing deterministically by (business, name); never select an
            // arbitrary Listing from the Business.
            $listing = \App\Models\Listing::firstOrCreate(
                ['business_id' => $business->id, 'name' => $business->name],
                [
                    'owner_id' => $business->owner_id,
                    'type' => \App\Support\ListingType::BUSINESS->value,
                    'status' => \App\Models\Listing::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]
            );

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