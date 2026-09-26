<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Add yearly discount percentage
            if (!Schema::hasColumn('plans', 'yearly_discount_percentage')) {
                $table->decimal('yearly_discount_percentage', 5, 2)->default(15)->after('price_annual');
            }
            
            // Remove old columns if they exist (cleanup)
            if (Schema::hasColumn('plans', 'available_durations')) {
                $table->dropColumn('available_durations');
            }
            if (Schema::hasColumn('plans', 'duration_discounts')) {
                $table->dropColumn('duration_discounts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'yearly_discount_percentage')) {
                $table->dropColumn('yearly_discount_percentage');
            }
        });
    }
};