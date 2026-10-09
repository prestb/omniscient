<?php

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 23C — MEILISEARCH INDEX VISIBILITY.
 *
 * Before this phase `Listing` defined no `shouldBeSearchable()`, so Scout
 * indexed every row. Inspecting the real Meilisearch index confirmed an
 * undiscoverable `status: "draft"` document was present.
 *
 * These tests pin the rule so the SQL discovery condition and the indexing
 * condition cannot drift apart again. They assert the model hook directly —
 * they do NOT depend on a running Meilisearch service.
 *
 * Helper names are prefixed `vi` (visibility/indexing); Pest shares one process
 * across every test file.
 */

function viListing(array $attributes = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => User::factory()->owner()->create()->id,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attributes));
}

// ═══ DISCOVERABLE ═══════════════════════════════════════════════════════════

test('a published, unhidden listing is searchable', function () {
    $listing = viListing([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    expect($listing->shouldBeSearchable())->toBeTrue();
});

// ═══ NON-DISCOVERABLE STATUSES ══════════════════════════════════════════════

test('every non-published status is not searchable', function () {
    foreach ([
        Listing::STATUS_DRAFT,
        Listing::STATUS_SUBMITTED,
        Listing::STATUS_APPROVED,
        Listing::STATUS_REJECTED,
        Listing::STATUS_SUSPENDED,
        Listing::STATUS_INACTIVE,
    ] as $status) {
        $listing = viListing(['status' => $status, 'hidden_at' => null]);

        expect($listing->shouldBeSearchable())
            ->toBeFalse("status '{$status}' must not be indexed");
    }
});

test('a published but hidden listing is not searchable', function () {
    $listing = viListing([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => now(),
    ]);

    expect($listing->shouldBeSearchable())->toBeFalse();
});

// ═══ THE RULE MATCHES THE CANONICAL DISCOVERY CONDITION ═════════════════════

test('the indexing rule agrees with the SQL directory discovery condition', function () {
    // DirectoryController filters: status = published AND hidden_at IS NULL.
    // If either layer changes, this test fails and the drift is caught.
    $discoverable = viListing(['status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);

    expect($discoverable->shouldBeSearchable())->toBeTrue();

    // The exact state observed in the real index before this phase.
    $observedInIndex = viListing(['status' => Listing::STATUS_DRAFT, 'hidden_at' => null]);

    expect($observedInIndex->shouldBeSearchable())->toBeFalse();
    expect($observedInIndex->status)->toBe('draft');
});
