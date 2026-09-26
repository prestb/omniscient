<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('XAF');
            $table->enum('method', [
                'cash',
                'mobile_money',
                'bank_transfer',
                'card',
                'other'
            ])->default('mobile_money');
            $table->string('reference')->nullable();
            $table->enum('status', [
                'pending',
                'confirmed',
                'failed',
                'refunded'
            ])->default('pending');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('subscription_id');
            $table->index('business_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};