<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Check if columns exist before adding
            
            // Rename price_yearly to price_annual if it exists
            // if (Schema::hasColumn('plans', 'price_yearly') && !Schema::hasColumn('plans', 'price_annual')) {
            //     $table->renameColumn('price_yearly', 'price_annual');
            // }
            
            // Add price_monthly if it doesn't exist
            if (!Schema::hasColumn('plans', 'price_monthly')) {
                $table->decimal('price_monthly', 10, 2)->nullable()->after('price_annual');
            }
            
            // Add available_durations if it doesn't exist
            if (!Schema::hasColumn('plans', 'available_durations')) {
                $table->json('available_durations')->nullable()->after('price_monthly');
            }
            
            // Add duration_discounts if it doesn't exist
            if (!Schema::hasColumn('plans', 'duration_discounts')) {
                $table->json('duration_discounts')->nullable()->after('available_durations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('plans', 'price_monthly')) {
                $table->dropColumn('price_monthly');
            }
            if (Schema::hasColumn('plans', 'available_durations')) {
                $table->dropColumn('available_durations');
            }
            if (Schema::hasColumn('plans', 'duration_discounts')) {
                $table->dropColumn('duration_discounts');
            }
            
            // Rename back if needed
            // if (Schema::hasColumn('plans', 'price_annual') && !Schema::hasColumn('plans', 'price_yearly')) {
            //     $table->renameColumn('price_annual', 'price_yearly');
            // }
        });
    }
};