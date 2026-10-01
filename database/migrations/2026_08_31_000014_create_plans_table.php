<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            
            // ============== IDENTIFICATION ==============
            $table->string('name');                          // Free, Starter, Growth, Premium
            $table->string('slug')->unique();                // free, starter, growth, premium
            $table->string('tier')->default('free');         // free, starter, growth, premium
            $table->text('description')->nullable();         // Short description
            $table->string('tagline')->nullable();           // Marketing tagline
            $table->string('badge_text')->nullable();        // "MOST POPULAR", "BEST VALUE"
            $table->string('badge_color')->nullable();       // primary, purple, gold, etc.
            
            // ============== PRICING ==============
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->decimal('yearly_discount_percentage', 5, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->integer('trial_days')->default(0);
            
                        // ============== RESOURCE LIMITS ==============
            // -1 = unlimited, 0 = disabled, >0 = max count
            // PHASE 11 — canonical quota columns:
            //   max_listings  = max Listings owned by the account
            //   max_locations = max Locations (physical places)
            $table->integer('max_listings')->default(1);
            $table->integer('max_locations')->default(1);
            $table->integer('max_services')->default(3);
            $table->integer('max_images')->default(3);
            $table->integer('max_coupons')->default(0);
            $table->integer('max_staff')->default(0);
            
            // ============== FEATURES (JSON) ==============
            // Feature flags like whatsapp_button, verified_badge, etc.
            $table->json('features')->nullable();
            
            // Marketing feature list (what shows on pricing page)
            $table->json('feature_list')->nullable();
            
            // ============== DISPLAY ==============
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);  // "Best value" highlight
            $table->boolean('is_popular')->default(false);   // "Most popular" badge
            $table->integer('sort_order')->default(0);        // Order on pricing page
            
            // ============== METADATA ==============
            $table->json('metadata')->nullable();             // Extra config if needed
            
            // ============== TIMESTAMPS ==============
            $table->timestamps();
            $table->softDeletes();
            
            // ============== INDEXES ==============
            $table->index('slug');
            $table->index('tier');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};