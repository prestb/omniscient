<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('duration_months')->default(12)->after('plan_id');
            $table->decimal('total_price', 10, 2)->nullable()->after('duration_months');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('total_price');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('monthly_price');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('duration_months');
            $table->dropColumn('total_price');
            $table->dropColumn('monthly_price');
            $table->dropColumn('discount_percentage');
        });
    }
};