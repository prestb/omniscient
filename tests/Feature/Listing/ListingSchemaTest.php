<?php

namespace Tests\Feature\Listing;

use App\Support\DataOwnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PHASE 9 — structural schema assertions.
 *
 * Proves the Listing core exists, the discoverable children carry a
 * `listing_id`, and the Phase 6/7 experiments have been removed.
 */
class ListingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_listings_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('listings'));

        foreach ([
            'id', 'owner_id', 'business_id', 'location_id', 'type',
            'name', 'slug', 'description', 'status', 'is_featured',
            'published_at', 'hidden_at', 'created_at', 'updated_at', 'deleted_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('listings', $column),
                "listings missing column: {$column}"
            );
        }
    }

    public function test_listing_children_carry_a_listing_id(): void
    {
        foreach (DataOwnership::listingScopedTables() as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'listing_id'),
                "{$table} must carry listing_id"
            );
        }
    }

    public function test_listing_categories_pivot_exists(): void
    {
        $this->assertTrue(Schema::hasTable('listing_categories'));
        $this->assertTrue(Schema::hasColumn('listing_categories', 'listing_id'));
        $this->assertTrue(Schema::hasColumn('listing_categories', 'category_id'));
    }

    public function test_phase7_branch_artifacts_are_removed(): void
    {
        // No competing branch pointer on discoverable children.
        foreach ([
            'business_services',
            'business_images',
            'business_contacts',
            'reviews',
            'business_analytics',
            'coupons',
            'leads',
        ] as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'branch_id'),
                "{$table}.branch_id should be removed in Phase 9"
            );
        }

        // The branch-as-listing pivot and public slug identity are gone.
        $this->assertFalse(Schema::hasTable('branch_category'));
        $this->assertFalse(Schema::hasColumn('branches', 'slug'));

        // Listing type has ONE source of truth: listings.type.
        $this->assertFalse(Schema::hasColumn('businesses', 'listing_type'));
    }
}
