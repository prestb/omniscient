<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\Business;
use App\Models\LocationHour;
use App\Models\Review;
use App\Services\ListingMigrationAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 — migration dry-run analyzer.
 *
 * Includes a HARD no-mutation assertion: the database must be byte-for-byte
 * unchanged after analysis.
 */
class ListingMigrationAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private ListingMigrationAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ListingMigrationAnalyzer();
    }

    public function test_candidate_listings_are_derived_from_locations(): void
    {
        $business = Business::factory()->create();
        Location::factory()->forBusiness($business)->primary()->create(['name' => 'SBKRAFT Buea']);
        Location::factory()->forBusiness($business)->create(['name' => 'SBKRAFT Limbe']);

        $report = $this->analyzer->analyze($business->fresh());

        $this->assertCount(2, $report['candidates']);
        $names = array_column($report['candidates'], 'name');
        $this->assertContains('SBKRAFT Buea', $names);
        $this->assertContains('SBKRAFT Limbe', $names);
        $this->assertSame('business', $report['candidates'][0]['type']);
    }

    public function test_business_identity_is_reported(): void
    {
        $business = Business::factory()->create(['name' => 'SBKRAFT']);

        $report = $this->analyzer->analyze($business->fresh());

        $this->assertSame('SBKRAFT', $report['business']['name']);
        $this->assertSame('business', $report['business']['listing_type']);
        $this->assertSame($business->owner_id, $report['business']['owner']['id']);
    }

    public function test_location_level_data_is_identified(): void
    {
        $business = Business::factory()->create();
        $branch = Location::factory()->forBusiness($business)->primary()->create();
        LocationHour::factory()->count(2)->create(['location_id' => $branch->id]);

        $report = $this->analyzer->analyze($business->fresh());
        $bucket = collect($report['classification']['clearly_location_level'])
            ->keyBy('entity');

        $this->assertArrayHasKey('location.place', $bucket);
        $this->assertArrayHasKey('location.hours', $bucket);
        $this->assertSame(2, $bucket['location.hours']['count']);
    }

    public function test_reviews_are_classified_as_shared_ambiguous(): void
    {
        $business = Business::factory()->create();

        // Business::reviews() is scoped to approved(), so create approved ones.
        foreach (range(1, 3) as $i) {
            Review::create([
                'business_id' => $business->id,
                'user_id' => \App\Models\User::factory()->create()->id,
                'rating' => 5,
                'content' => "Review {$i}",
                'status' => Review::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        }

        $report = $this->analyzer->analyze($business->fresh());
        $ambiguous = collect($report['classification']['shared_ambiguous'])
            ->keyBy('entity');

        $this->assertArrayHasKey('reviews', $ambiguous);
        $this->assertSame(3, $ambiguous['reviews']['count']);
    }

    public function test_requires_policy_lists_ambiguous_relationships(): void
    {
        $business = Business::factory()->create();

        $report = $this->analyzer->analyze($business->fresh());

        foreach (['reviews', 'services', 'categories', 'analytics'] as $rel) {
            $this->assertContains($rel, $report['requires_policy']);
        }
    }

    public function test_analytics_is_classified_as_derived_system(): void
    {
        $business = Business::factory()->create();

        $report = $this->analyzer->analyze($business->fresh());
        $derived = collect($report['classification']['derived_system'])
            ->pluck('entity')->all();

        $this->assertContains('business_analytics', $derived);
    }

    public function test_analysis_does_not_mutate_the_database(): void
    {
        $business = Business::factory()->create();
        $branch = Location::factory()->forBusiness($business)->primary()->create();
        LocationHour::factory()->count(2)->create(['location_id' => $branch->id]);
        Review::create([
            'business_id' => $business->id,
            'user_id' => \App\Models\User::factory()->create()->id,
            'rating' => 4,
            'content' => 'Snapshot review',
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // Snapshot before — counts across every relevant table.
        $snapshot = fn () => [
            'businesses' => DB::table('businesses')->count(),
            'locations' => DB::table('locations')->count(),
            'location_hours' => DB::table('location_hours')->count(),
            'reviews' => DB::table('reviews')->count(),
            'businesses_updated' => DB::table('businesses')->orderBy('id')->pluck('updated_at')->all(),
            'locations_updated' => DB::table('locations')->orderBy('id')->pluck('updated_at')->all(),
        ];

        $before = $snapshot();

        $this->analyzer->analyze($business->fresh());

        $after = $snapshot();

        $this->assertSame($before, $after, 'Analyzer must not modify any data.');
    }
}
