<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixSubscriptionData extends Command
{
    protected $signature = 'subscriptions:fix-data';
    protected $description = 'Fix missing subscription data (duration, prices)';

    public function handle()
    {
        $this->info('Fixing subscription data...');
        
        $subscriptions = Subscription::all();
        $updated = 0;
        
        foreach ($subscriptions as $subscription) {
            $updatedThis = false;
            
            // Fix duration_months if missing
            if (!$subscription->duration_months && $subscription->start_date && $subscription->end_date) {
                $start = Carbon::parse($subscription->start_date);
                $end = Carbon::parse($subscription->end_date);
                $subscription->duration_months = $start->diffInMonths($end);
                $updatedThis = true;
            }
            
            // Fix monthly_price if missing and plan exists
            if (!$subscription->monthly_price && $subscription->plan) {
                $subscription->monthly_price = $subscription->plan->price_monthly ?? 0;
                $updatedThis = true;
            }
            
            // Fix total_price if missing
            if (!$subscription->total_price && $subscription->duration_months && $subscription->monthly_price) {
                $basePrice = $subscription->monthly_price * $subscription->duration_months;
                $discount = $subscription->discount_percentage ?? 0;
                $subscription->total_price = $basePrice * (1 - ($discount / 100));
                $updatedThis = true;
            }
            
            // Fix discount_percentage if missing (default to 15% for yearly)
            if (!$subscription->discount_percentage && $subscription->duration_months && $subscription->duration_months >= 12) {
                $subscription->discount_percentage = 15;
                $updatedThis = true;
            }
            
            if ($updatedThis) {
                $subscription->save();
                $updated++;
                $this->info("Updated subscription #{$subscription->id}");
            }
        }
        
        $this->info("Completed! Updated {$updated} subscriptions.");
    }
}