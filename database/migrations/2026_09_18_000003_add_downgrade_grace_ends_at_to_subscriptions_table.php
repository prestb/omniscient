<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'downgrade_grace_ends_at')) {
                $table->date('downgrade_grace_ends_at')->nullable()->after('grace_period_ends_at');
                $table->index('downgrade_grace_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'downgrade_grace_ends_at')) {
                $table->dropIndex(['downgrade_grace_ends_at']);
                $table->dropColumn('downgrade_grace_ends_at');
            }
        });
    }
};