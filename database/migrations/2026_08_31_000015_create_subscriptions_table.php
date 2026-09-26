<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'pending',
                'active',
                'expiring_soon',
                'expired',
                'grace_period',
                'suspended',
                'cancelled'
            ])->default('pending');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('grace_period_end')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->date('trial_end_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('business_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index('end_date');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};