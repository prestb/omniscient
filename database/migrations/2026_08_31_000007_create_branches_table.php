<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->boolean('is_primary')->default(false);
            
            // Geographic location
            $table->foreignId('country_id')->constrained();
            $table->foreignId('region_id')->constrained();
            $table->foreignId('city_id')->constrained();
            $table->foreignId('area_id')->constrained();
            $table->text('address')->nullable();
            
            // GPS Coordinates (for future use)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Contact
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            
            // Status
            $table->enum('status', [
                'active',
                'temporarily_unavailable',
                'unlisted'
            ])->default('active');
            
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('business_id');
            $table->index('is_primary');
            $table->index('country_id');
            $table->index('region_id');
            $table->index('city_id');
            $table->index('area_id');
            $table->index('status');
            $table->index('latitude');
            $table->index('longitude');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};