<?php
// database/migrations/xxxx_xx_xx_add_action_fields_to_payment_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('action_type')->nullable()->after('duration_months'); // new, renew, upgrade
            $table->string('billing_type')->nullable()->after('action_type'); // monthly, yearly
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('action_type');
            $table->dropColumn('billing_type');
        });
    }
};