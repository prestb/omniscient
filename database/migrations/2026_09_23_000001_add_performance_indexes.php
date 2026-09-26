<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reviews — owner dashboard/list sorts by created_at within business+status
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(
                ['status', 'business_id', 'created_at'],
                'reviews_status_business_created_index'
            );
        });

        // 2. Businesses — directory + home queries filter status, hidden_at,
        //    and sort by is_featured desc, published_at desc
        Schema::table('businesses', function (Blueprint $table) {
            $table->index(
                ['status', 'hidden_at', 'is_featured', 'published_at'],
                'businesses_directory_sort_index'
            );
        });

        // 3. Notifications — unread count + list queries
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_unread_index'
            );
        });

        // 4. Push subscriptions — dedup by (user_id, endpoint)
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'endpoint'],
                'push_subscriptions_user_endpoint_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_status_business_created_index');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex('businesses_directory_sort_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_unread_index');
        });

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropIndex('push_subscriptions_user_endpoint_index');
        });
    }
};