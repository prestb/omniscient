<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'credit_balance')) {
                $table->decimal('credit_balance', 10, 2)
                    ->default(0)
                    ->after('discount_percentage')
                    ->comment('Unused proration credit carried forward');
            }
            if (!Schema::hasColumn('subscriptions', 'upgraded_from_subscription_id')) {
                $table->foreignId('upgraded_from_subscription_id')
                    ->nullable()
                    ->after('credit_balance')
                    ->constrained('subscriptions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'upgraded_from_subscription_id')) {
                $table->dropForeign(['upgraded_from_subscription_id']);
                $table->dropColumn('upgraded_from_subscription_id');
            }
            if (Schema::hasColumn('subscriptions', 'credit_balance')) {
                $table->dropColumn('credit_balance');
            }
        });
    }
};