<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ListingContact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('name', 'ABC Pharmacy')->first();
        
        if ($business) {
            // PHASE 11 / WAVE 1D-3 — contacts are Listing-owned. Resolve the demo
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

            $contacts = [
                ['type' => 'phone', 'value' => '+237 699 123 456', 'is_primary' => true],
                ['type' => 'whatsapp', 'value' => '+237 699 123 456', 'is_primary' => false],
                ['type' => 'facebook', 'value' => 'abcpharmacy', 'is_primary' => false],
                ['type' => 'instagram', 'value' => 'abcpharmacy', 'is_primary' => false],
            ];
            
            foreach ($contacts as $index => $contact) {
                ListingContact::create([
                    'listing_id' => $listing->id,
                    'type' => $contact['type'],
                    'value' => $contact['value'],
                    'is_primary' => $contact['is_primary'],
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}