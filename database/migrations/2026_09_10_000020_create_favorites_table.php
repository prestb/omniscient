<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            // ✅ One favorite per user per business
            $table->unique(['user_id', 'business_id']);
            
            // ✅ Fast lookups
            $table->index('user_id');
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};