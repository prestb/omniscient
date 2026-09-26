<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessService;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('name', 'ABC Pharmacy')->first();
        
        if ($business) {
            $services = [
                'Prescription Medicines',
                'Over-the-counter Medicines',
                'Health Products',
                'Baby Products',
                'Vitamins and Supplements',
                'First Aid Supplies',
            ];
            
            foreach ($services as $index => $service) {
                BusinessService::create([
                    'business_id' => $business->id,
                    'name' => $service,
                    'description' => "Professional {$service}",
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}