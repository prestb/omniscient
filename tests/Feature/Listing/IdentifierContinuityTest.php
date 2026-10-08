<?php

namespace Tests\Feature\Listing;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 4 — identifier / URL continuity analysis.
 *
 * Read-only assertions: the public URL contract is slug-based, slugs are
 * unique, and the review/admin routes key on stable ids. A future
 * non-destructive migration must preserve these.
 */
class IdentifierContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_business_route_is_slug_based(): void
    {
        $route = Route::getRoutes()->getByName('business.show');

        $this->assertNotNull($route);
        $this->assertSame('business/{slug}', $route->uri());
        $this->assertContains('slug', $route->parameterNames());
    }

    public function test_business_slug_is_unique_and_url_safe(): void
    {
        $business = Business::factory()->create(['name' => 'SBKRAFT Buea']);

        $this->assertNotEmpty($business->slug);
        $this->assertSame(\Illuminate\Support\Str::slug($business->slug), $business->slug);
        $this->assertSame(1, Business::where('slug', $business->slug)->count());
    }

    public function test_business_identifier_and_slug_remain_stable_across_reads(): void
    {
        $business = Business::factory()->create();

        $first = Business::find($business->id);
        $second = Business::find($business->id);

        // The identifier continuity contract: id and slug do not drift.
        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->slug, $second->slug);
    }

    public function test_review_routes_key_on_stable_ids(): void
    {
        // PHASE 21C-R1 - a Review belongs to a LISTING, so the canonical
        // public review routes are Listing-scoped. The old Business-scoped
        // routes are deliberately NOT preserved for compatibility.
        $index = Route::getRoutes()->getByName('listing.reviews.index');
        $store = Route::getRoutes()->getByName('listing.reviews.store');

        $this->assertNotNull($index);
        $this->assertNotNull($store);
        $this->assertContains('listing', $index->parameterNames());
        $this->assertContains('listing', $store->parameterNames());

        $this->assertNull(Route::getRoutes()->getByName('business.reviews.store'));
    }

    public function test_admin_review_route_keys_on_stable_review_id(): void
    {
        $route = Route::getRoutes()->getByName('admin.reviews.show');

        $this->assertNotNull($route);
        $this->assertContains('review', $route->parameterNames());
    }

    public function test_two_businesses_cannot_share_a_slug(): void
    {
        Business::factory()->create(['slug' => 'unique-slug-abc']);

        $duplicate = Business::factory()->make(['slug' => 'unique-slug-abc']);

        // The DB enforces uniqueness; we assert the schema index exists.
        $indexes = collect(\Schema::getIndexes('businesses'))
            ->first(fn ($i) => in_array('slug', $i['columns'], true) && $i['unique']);

        $this->assertNotNull($indexes, 'businesses.slug must be indexed as unique');
    }
}
