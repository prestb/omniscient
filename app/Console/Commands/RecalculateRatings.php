<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\RatingService;
use Illuminate\Console\Command;

class RecalculateRatings extends Command
{
    protected $signature = 'ratings:recalculate';
    protected $description = 'Recalculate average ratings for all businesses';

    public function handle()
    {
        $this->info('Recalculating ratings for all businesses...');
        
        $businesses = Business::all();
        $count = 0;
        
        foreach ($businesses as $business) {
            $total = $business->approvedReviews()->count();
            
            if ($total > 0) {
                $average = $business->approvedReviews()->avg('rating');
                $business->update([
                    'average_rating' => round($average, 1),
                    'total_reviews' => $total,
                ]);
                $this->line("✓ {$business->name}: {$business->average_rating} ({$business->total_reviews} reviews)");
            } else {
                $business->update([
                    'average_rating' => 0,
                    'total_reviews' => 0,
                ]);
                $this->line("✓ {$business->name}: 0 (0 reviews)");
            }
            $count++;
        }
        
        $this->info("\n✅ Fixed ratings for {$count} businesses.");
        
        return Command::SUCCESS;
    }
}