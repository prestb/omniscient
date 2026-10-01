<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 11 — REVIEW ATTRIBUTION INTEGRITY, Step 2.
 *
 * Reviews are BUSINESS-OWNED. `reviews.listing_id` was obsolete scaffolding that
 * no application writer ever populated, and `Listing::reviews()` — the relation
 * that resolved on it — was permanently empty. Step 1 moved every Listing-facing
 * metric onto Listing::businessReviews() (the owning Business's aggregate).
 *
 * This removes the column and its constraint.
 *
 * The FK is inspected via information_schema rather than assumed: `SHOW INDEX`
 * reports `reviews_listing_id_foreign` as a plain non-unique index, which is
 * misleading — KEY_COLUMN_USAGE confirms a real foreign key constraint.
 *
 * Nothing else about reviews changes. `business_id`, `user_id`, content and
 * status fields, and all other indexes are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reviews', 'listing_id')) {
            return;
        }

        // Never destroy data. No writer exists, but refuse to guess.
        $attributed = DB::table('reviews')->whereNotNull('listing_id')->count();

        if ($attributed > 0) {
            throw new RuntimeException(
                "Cannot remove reviews.listing_id: {$attributed} review row(s) carry a non-null listing_id. "
                . 'Reviews are Business-owned; resolve these rows before dropping the column.'
            );
        }

        // Drop the real FK constraint by name, if one exists. This must happen
        // BEFORE the column: MySQL removes the FK's backing index with it.
        $fks = DB::select(
            "select CONSTRAINT_NAME from information_schema.KEY_COLUMN_USAGE
             where TABLE_SCHEMA = database() and TABLE_NAME = 'reviews'
               and COLUMN_NAME = 'listing_id' and REFERENCED_TABLE_NAME is not null"
        );

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `reviews` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Any leftover plain index on the column.
        foreach (DB::select('SHOW INDEX FROM reviews') as $i) {
            if ($i->Column_name === 'listing_id') {
                DB::statement("ALTER TABLE `reviews` DROP INDEX `{$i->Key_name}`");
            }
        }

        Schema::table('reviews', function ($table) {
            $table->dropColumn('listing_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'listing_id')) {
            return;
        }

        Schema::table('reviews', function ($table) {
            $table->foreignId('listing_id')
                ->nullable()
                ->after('business_id')
                ->constrained('listings')
                ->cascadeOnDelete();
        });
    }
};
