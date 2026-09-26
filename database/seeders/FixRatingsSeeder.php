<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class FixRatingsSeeder extends Seeder
{
    public function run()
    {
        $businesses = Business::all();
        
        foreach ($businesses as $business) {
            $total = $business->reviews()->where('status', 'approved')->count();
            
            if ($total > 0) {
                $average = $business->reviews()->where('status', 'approved')->avg('rating');
                $business->update([
                    'average_rating' => round($average, 1),
                    'total_reviews' => $total,
                ]);
                $this->command->info("Updated: {$business->name} - {$business->average_rating} ({$business->total_reviews} reviews)");
            } else {
                $business->update([
                    'average_rating' => 0,
                    'total_reviews' => 0,
                ]);
                $this->command->info("Reset: {$business->name} - No reviews");
            }
        }
    }
}