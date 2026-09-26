<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_24h')->default(false);
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('branch_id');
            $table->index('day_of_week');
            $table->unique(['branch_id', 'day_of_week', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};