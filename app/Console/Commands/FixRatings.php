<?php

namespace App\Console\Commands;

use App\Models\Business;
use Illuminate\Console\Command;

class FixRatings extends Command
{
    protected $signature = 'ratings:fix';
    protected $description = 'Fix all business ratings by recalculating from approved reviews';

    public function handle()
    {
        $this->info('Fixing ratings for all businesses...');
        
        $businesses = Business::all();
        $count = 0;
        
        foreach ($businesses as $business) {
            $total = $business->reviews()->where('status', 'approved')->count();
            
            if ($total > 0) {
                $average = $business->reviews()->where('status', 'approved')->avg('rating');
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