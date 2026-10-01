<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', ['role', 'status'])) {
                $table->index(['role', 'status']);
            }
            if (!Schema::hasIndex('users', ['email'])) {
                $table->index('email');
            }
            if (!Schema::hasIndex('users', ['created_at'])) {
                $table->index('created_at');
            }
        });

        // Businesses table indexes
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasIndex('businesses', ['status', 'is_featured'])) {
                $table->index(['status', 'is_featured']);
            }
            if (!Schema::hasIndex('businesses', ['owner_id'])) {
                $table->index('owner_id');
            }
            if (!Schema::hasIndex('businesses', ['slug'])) {
                $table->index('slug');
            }
            if (!Schema::hasIndex('businesses', ['published_at'])) {
                $table->index('published_at');
            }
            if (!Schema::hasIndex('businesses', ['created_at'])) {
                $table->index('created_at');
            }
        });

        // Locations table indexes
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasIndex('locations', ['business_id', 'is_primary'])) {
                $table->index(['business_id', 'is_primary']);
            }
            if (!Schema::hasIndex('locations', ['country_id', 'region_id', 'city_id', 'area_id'])) {
                $table->index(['country_id', 'region_id', 'city_id', 'area_id']);
            }
            if (!Schema::hasIndex('locations', ['status'])) {
                $table->index('status');
            }
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasIndex('categories', ['parent_id', 'is_active'])) {
                $table->index(['parent_id', 'is_active']);
            }
            if (!Schema::hasIndex('categories', ['slug'])) {
                $table->index('slug');
            }
            if (!Schema::hasIndex('categories', ['sort_order'])) {
                $table->index('sort_order');
            }
        });

        // Subscriptions table indexes
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasIndex('subscriptions', ['business_id', 'status'])) {
                $table->index(['business_id', 'status']);
            }
            if (!Schema::hasIndex('subscriptions', ['status', 'end_date'])) {
                $table->index(['status', 'end_date']);
            }
            if (!Schema::hasIndex('subscriptions', ['start_date'])) {
                $table->index('start_date');
            }
            if (!Schema::hasIndex('subscriptions', ['end_date'])) {
                $table->index('end_date');
            }
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasIndex('payments', ['business_id', 'status'])) {
                $table->index(['business_id', 'status']);
            }
            if (!Schema::hasIndex('payments', ['subscription_id', 'status'])) {
                $table->index(['subscription_id', 'status']);
            }
            if (!Schema::hasIndex('payments', ['paid_at'])) {
                $table->index('paid_at');
            }
            if (!Schema::hasIndex('payments', ['created_at'])) {
                $table->index('created_at');
            }
        });

        // Business categories pivot table indexes
        Schema::table('business_categories', function (Blueprint $table) {
            if (!Schema::hasIndex('business_categories', ['business_id', 'category_id'])) {
                $table->index(['business_id', 'category_id']);
            }
            if (!Schema::hasIndex('business_categories', ['is_primary'])) {
                $table->index('is_primary');
            }
        });

        // Location hours table indexes
        Schema::table('location_hours', function (Blueprint $table) {
            if (!Schema::hasIndex('location_hours', ['location_id', 'day_of_week'])) {
                $table->index(['location_id', 'day_of_week']);
            }
        });

        // Business analytics table indexes
        Schema::table('business_analytics', function (Blueprint $table) {
            if (!Schema::hasIndex('business_analytics', ['business_id', 'date'])) {
                $table->index(['business_id', 'date']);
            }
            if (!Schema::hasIndex('business_analytics', ['date'])) {
                $table->index('date');
            }
        });
    }

    public function down(): void
    {
        // Remove indexes (Laravel will handle this automatically on rollback)
        // But if you want to manually drop them:
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['email']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_featured']);
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['created_at']);
        });

        // ... drop other indexes similarly
    }
};