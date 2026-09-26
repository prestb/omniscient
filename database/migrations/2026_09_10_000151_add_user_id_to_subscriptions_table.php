<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add user_id column WITHOUT foreign key first (nullable)
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('business_id');
        });

        // 2. Data migration: only update rows where owner exists
        // Use LEFT JOIN to safely skip missing users
        DB::statement("
            UPDATE subscriptions s
            INNER JOIN businesses b ON s.business_id = b.id
            INNER JOIN users u ON b.owner_id = u.id
            SET s.user_id = b.owner_id
            WHERE s.user_id IS NULL 
              AND s.business_id IS NOT NULL
              AND b.owner_id IS NOT NULL
        ");

        // 3. Make business_id nullable
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->change();
        });

        // 4. Add foreign key constraint ONLY if all user_id values are valid
        $orphanCount = DB::table('subscriptions')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', function ($query) {
                $query->select('id')->from('users');
            })
            ->count();

        if ($orphanCount === 0) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        } else {
            \Log::warning("Skipped user_id foreign key - {$orphanCount} orphan records found");
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Drop FK if it exists
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }
            $table->dropColumn('user_id');
        });
    }
};