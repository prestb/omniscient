<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Add business_id if it doesn't exist
            if (!Schema::hasColumn('subscriptions', 'business_id')) {
                $table->foreignId('business_id')->constrained()->onDelete('cascade');
            }
            
            // Remove owner_id if it exists
            if (Schema::hasColumn('subscriptions', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            }
        });
    }
};