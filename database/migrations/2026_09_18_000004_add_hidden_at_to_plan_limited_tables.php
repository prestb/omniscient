<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds `hidden_at` to tables whose rows can be auto-hidden when an owner
     * downgrades to a plan with lower limits.
     *
     * - NULL  → row is visible (public + owner sees it normally)
     * - set   → row is hidden from public; owner sees it with reduced actions
     */
    public function up(): void
    {
        $tables = [
            'businesses',
            'branches',
            'business_services',
            'business_images',
            'coupons',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'hidden_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->timestamp('hidden_at')->nullable();
                    $blueprint->index('hidden_at');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'businesses',
            'branches',
            'business_services',
            'business_images',
            'coupons',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'hidden_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropIndex(['hidden_at']);
                    $blueprint->dropColumn('hidden_at');
                });
            }
        }
    }
};