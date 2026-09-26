<?php
// database/migrations/xxxx_xx_xx_create_payment_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained();
            $table->foreignId('plan_id')->constrained();
            $table->string('transaction_id')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('XAF');
            $table->string('status')->default('created'); // created, pending, successful, failed, expired
            $table->string('payment_method')->default('fapshi');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer('duration_months')->default(12);
            $table->json('payment_data')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};