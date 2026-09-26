<?php
// database/migrations/xxxx_xx_xx_add_missing_columns_to_push_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            // ✅ Check if columns exist before adding
            
            // Add user_id if it doesn't exist
            if (!Schema::hasColumn('push_subscriptions', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade')->after('id');
            }
            
            // Add keys if it doesn't exist (rename from auth_keys or similar)
            if (!Schema::hasColumn('push_subscriptions', 'keys')) {
                $table->json('keys')->nullable()->after('endpoint');
            }
            
            // Add user_agent if it doesn't exist
            if (!Schema::hasColumn('push_subscriptions', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('keys');
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $columns = ['user_id', 'keys', 'user_agent'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('push_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};