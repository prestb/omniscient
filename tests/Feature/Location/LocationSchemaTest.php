<?php

namespace Tests\Feature\Location;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PHASE 10 — structural schema assertions for the universal Location table.
 *
 * Proves `locations` is a physical-place entity with NO listing identity and
 * NO required Business, and that the legacy `branches` table is gone.
 */
class LocationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('locations'));

        foreach ([
            'id', 'business_id', 'name',
            'country_id', 'region_id', 'city_id', 'area_id',
            'address', 'landmark', 'postal_code',
            'latitude', 'longitude',
            'phone', 'whatsapp',
            'status', 'is_primary', 'sort_order',
            'created_at', 'updated_at', 'deleted_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('locations', $column),
                "locations missing column: {$column}"
            );
        }
    }

    public function test_locations_table_does_not_own_listing_identity(): void
    {
        foreach ([
            'slug', 'type', 'listing_type', 'description',
            'owner_id',
        ] as $forbidden) {
            $this->assertFalse(
                Schema::hasColumn('locations', $forbidden),
                "locations must NOT own listing identity: {$forbidden}"
            );
        }
    }

    public function test_business_id_is_nullable_so_location_is_independent(): void
    {
        // business_id exists but is optional: a standalone Location (or future
        // Event venue) must be possible with no organization.
        $this->assertTrue(Schema::hasColumn('locations', 'business_id'));

        $location = Location::factory()->create();
        $this->assertNull($location->fresh()->business_id);
    }

    public function test_legacy_branches_table_is_gone(): void
    {
        // The word "Branch" is no longer part of the physical-location
        // abstraction — the legacy table has been replaced by `locations`.
        $this->assertFalse(Schema::hasTable('branches'));
    }
}
