<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'grace_period_ends_at')) {
                $table->date('grace_period_ends_at')->nullable()->after('end_date');
                $table->index('grace_period_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'grace_period_ends_at')) {
                $table->dropIndex(['grace_period_ends_at']);
                $table->dropColumn('grace_period_ends_at');
            }
        });
    }
};