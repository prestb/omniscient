<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingService;
use App\Models\LocationHour;
use App\Models\Lead;
use App\Models\Review;
use App\Models\User;
use App\Services\ListingMigrationAnalyzer;
use App\Services\MigrationReadinessService;
use App\Support\DataOwnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 4 — migration readiness verdict, ambiguity detection, no-mutation.
 */
class MigrationReadinessTest extends TestCase
{
    use RefreshDatabase;

    private MigrationReadinessService $readiness;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readiness = new MigrationReadinessService(new ListingMigrationAnalyzer());
    }

    public function test_single_location_business_is_ready(): void
    {
        $business = Business::factory()->create();
        Location::factory()->forBusiness($business)->primary()->create();

        $report = $this->readiness->report($business);

        $this->assertSame(MigrationReadinessService::READY, $report['readiness']);
        $this->assertEmpty($report['blocking_reasons']);
    }

    /**
     * Phase 5: shared business-level records NO LONGER block migration — each
     * now has a canonical destination policy. They are reported as RESOLVED
     * ambiguities instead of blocking reasons.
     */
    public function test_multi_location_with_shared_records_is_ready_after_phase_5_policies(): void
    {
        $business = Business::factory()->create();
        $buea = Location::factory()->forBusiness($business)->primary()->create(['name' => 'SBKRAFT Buea']);
        $limbe = Location::factory()->forBusiness($business)->create(['name' => 'SBKRAFT Limbe']);

        foreach ([1, 2, 3] as $day) {
            LocationHour::factory()->forDay($day)->create(['location_id' => $buea->id]);
            LocationHour::factory()->forDay($day)->create(['location_id' => $limbe->id]);
        }

        $listing = Listing::factory()->forBusiness($business)->create();
        ListingService::create(['listing_id' => $listing->id, 'name' => 'Embroidery']);

        foreach (range(1, 2) as $i) {
            Review::create([
                'business_id' => $business->id,
                'user_id' => User::factory()->create()->id,
                'rating' => 5,
                'content' => "Review {$i}",
                'status' => Review::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        }

        Lead::create([
            'business_id' => $business->id,
            'source' => 'contact_form',
            'name' => 'Anon',
            'message' => 'hi',
        ]);

        $report = $this->readiness->report($business->fresh());

        // Phase 5: every ambiguity has a defined policy → no blocking reasons.
        $this->assertSame(MigrationReadinessService::READY, $report['readiness']);
        $this->assertEmpty($report['blocking_reasons']);

        // …but the resolved ambiguities are still surfaced for transparency.
        $this->assertNotEmpty($report['resolved_ambiguities']);
        $joined = implode(' | ', $report['resolved_ambiguities']);
        $this->assertStringContainsString('review', $joined);
        $this->assertStringContainsString('service', $joined);
        $this->assertStringContainsString('lead', $joined);
    }

    public function test_tally_classifies_records_into_ownership_buckets(): void
    {
        $business = Business::factory()->create();
        $branch = Location::factory()->forBusiness($business)->primary()->create();
        foreach ([1, 2] as $day) {
            LocationHour::factory()->forDay($day)->create(['location_id' => $branch->id]);
        }

        $report = $this->readiness->report($business->fresh());

        // Location-owned hours present.
        $this->assertGreaterThanOrEqual(2, $report['tally'][DataOwnership::LOCATION_OWNED]);
        // Derived/system data present (slug, analytics, ratings, verification).
        $this->assertGreaterThan(0, $report['tally'][DataOwnership::DERIVED_SYSTEM]);
    }

    public function test_readiness_report_exposes_ownership_matrix(): void
    {
        $business = Business::factory()->create();

        $report = $this->readiness->report($business->fresh());

        $this->assertArrayHasKey('matrix', $report['ownership']);
        $this->assertContains('reviews', $report['ownership']['ambiguous_entities']);
        $this->assertContains('subscriptions', $report['ownership']['account_owned_entities']);
    }

    public function test_readiness_report_does_not_mutate_the_database(): void
    {
        $business = Business::factory()->create();
        $branch = Location::factory()->forBusiness($business)->primary()->create();
        // Deterministic distinct days (avoid random day_of_week collisions on
        // the branch_hours unique key).
        LocationHour::factory()->forDay(1)->create(['location_id' => $branch->id]);
        LocationHour::factory()->forDay(2)->create(['location_id' => $branch->id]);
        $listing = Listing::factory()->forBusiness($business)->create();
        ListingService::create(['listing_id' => $listing->id, 'name' => 'Service']);

        $snapshot = fn () => [
            'businesses' => DB::table('businesses')->count(),
            'locations' => DB::table('locations')->count(),
            'location_hours' => DB::table('location_hours')->count(),
            'listing_services' => DB::table('listing_services')->count(),
            'businesses_updated' => DB::table('businesses')->orderBy('id')->pluck('updated_at')->all(),
            'locations_updated' => DB::table('locations')->orderBy('id')->pluck('updated_at')->all(),
        ];

        $before = $snapshot();
        $this->readiness->report($business->fresh());
        $after = $snapshot();

        $this->assertSame($before, $after, 'Readiness report must not modify data.');
    }
}
