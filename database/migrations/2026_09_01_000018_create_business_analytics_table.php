<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('phone_clicks')->default(0);
            $table->integer('whatsapp_clicks')->default(0);
            $table->integer('website_clicks')->default(0);
            $table->integer('direction_clicks')->default(0);
            $table->integer('social_clicks')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_analytics');
    }
};