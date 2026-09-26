<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessContact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('name', 'ABC Pharmacy')->first();
        
        if ($business) {
            $contacts = [
                ['type' => 'phone', 'value' => '+237 699 123 456', 'is_primary' => true],
                ['type' => 'whatsapp', 'value' => '+237 699 123 456', 'is_primary' => false],
                ['type' => 'facebook', 'value' => 'abcpharmacy', 'is_primary' => false],
                ['type' => 'instagram', 'value' => 'abcpharmacy', 'is_primary' => false],
            ];
            
            foreach ($contacts as $index => $contact) {
                BusinessContact::create([
                    'business_id' => $business->id,
                    'type' => $contact['type'],
                    'value' => $contact['value'],
                    'is_primary' => $contact['is_primary'],
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}