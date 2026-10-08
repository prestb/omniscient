<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 21C-R1 — REVIEW OWNERSHIP: BUSINESS → LISTING.
 *
 * A Review describes the Listing actually being reviewed. Business reputation
 * becomes a DERIVED aggregate over its Listings rather than a second ownership
 * path.
 *
 * `reviews` is empty, so there is NO backfill: assigning a historical
 * Business-level review to one of several sibling Listings would invent
 * ownership. `business_id` is dropped rather than kept as a compatibility
 * column, because two competing ownership paths is the ambiguity this removes.
 *
 * INDEX ORDERING MATTERS. `reviews_user_id_business_id_index` is the index
 * MariaDB uses for the `user_id` foreign key, so it cannot be dropped until a
 * standalone `user_id` index exists (error 1553). The same applies to the
 * composite status/created index, which still carries `business_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. The new canonical owner.
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('listing_id')
                ->after('id')
                ->constrained('listings')
                ->cascadeOnDelete();

            $table->index(['listing_id', 'status'], 'reviews_listing_status_index');
            // One authenticated user may review a Listing once.
            $table->unique(['listing_id', 'user_id'], 'reviews_listing_user_unique');
        });

        // 2. Give `user_id` its own index BEFORE the composite that currently
        //    serves its foreign key is removed.
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('user_id', 'reviews_user_id_index');
        });

        // 3. Retire every Business-shaped constraint and index.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_user_id_business_id_index');
            $table->dropIndex('reviews_status_business_created_index');
            // status/created is still a useful shape without the Business.
            $table->index(['status', 'created_at'], 'reviews_status_created_index');
        });

        // 4. Remove the obsolete ownership column entirely.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('business_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('business_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_listing_user_unique');
            $table->dropIndex('reviews_listing_status_index');
            $table->dropForeign(['listing_id']);
            $table->dropColumn('listing_id');
            $table->dropIndex('reviews_user_id_index');
            $table->dropIndex('reviews_status_created_index');
        });
    }
};
