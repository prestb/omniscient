<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'phone', 
                'whatsapp', 
                'facebook', 
                'instagram', 
                'tiktok', 
                'twitter', 
                'youtube', 
                'linkedin',
                'other'
            ]);
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('business_id');
            $table->index('type');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_contacts');
    }
};