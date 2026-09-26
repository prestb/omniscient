<?php
// database/migrations/xxxx_xx_xx_add_payment_fields_to_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('action_type')->nullable()->after('discount_percentage'); // new, renew, upgrade
            $table->string('failure_reason')->nullable()->after('action_type');
            $table->timestamp('payment_confirmed_at')->nullable()->after('failure_reason');
            $table->string('transaction_id')->nullable()->after('payment_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('action_type');
            $table->dropColumn('failure_reason');
            $table->dropColumn('payment_confirmed_at');
            $table->dropColumn('transaction_id');
        });
    }
};