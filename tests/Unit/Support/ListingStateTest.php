<?php

namespace Tests\Unit\Support;

use App\Models\Business;
use App\Support\ListingState;
use App\Support\ListingType;
use PHPUnit\Framework\TestCase;

/**
 * Phase 1 — pure-function coverage for the listing state machine semantics.
 * No database required.
 */
class ListingStateTest extends TestCase
{
    public function test_index_within_limit_is_active(): void
    {
        $this->assertSame(ListingState::QUOTA_ACTIVE, ListingState::quotaStateForIndex(0, 3));
        $this->assertSame(ListingState::QUOTA_ACTIVE, ListingState::quotaStateForIndex(2, 3));
    }

    public function test_index_beyond_limit_is_hidden_without_grace(): void
    {
        $this->assertSame(ListingState::QUOTA_HIDDEN, ListingState::quotaStateForIndex(3, 3));
        $this->assertSame(ListingState::QUOTA_HIDDEN, ListingState::quotaStateForIndex(10, 3));
    }

    public function test_index_beyond_limit_is_over_quota_during_grace(): void
    {
        $this->assertSame(
            ListingState::QUOTA_OVER,
            ListingState::quotaStateForIndex(5, 3, graceActive: true)
        );
    }

    public function test_unlimited_sentinels_never_hide(): void
    {
        $this->assertSame(ListingState::QUOTA_ACTIVE, ListingState::quotaStateForIndex(99, -1));
        $this->assertSame(ListingState::QUOTA_ACTIVE, ListingState::quotaStateForIndex(99, 999));
    }

    public function test_publicly_visible_requires_published_and_active(): void
    {
        $this->assertTrue(ListingState::isPubliclyVisible(
            Business::STATUS_PUBLISHED,
            ListingState::QUOTA_ACTIVE
        ));

        $this->assertFalse(ListingState::isPubliclyVisible(
            Business::STATUS_PUBLISHED,
            ListingState::QUOTA_HIDDEN
        ));

        $this->assertFalse(ListingState::isPubliclyVisible(
            Business::STATUS_DRAFT,
            ListingState::QUOTA_ACTIVE
        ));
    }

    public function test_labels_are_human_readable(): void
    {
        $this->assertSame('Active', ListingState::label(ListingState::QUOTA_ACTIVE));
        $this->assertSame('Hidden', ListingState::label(ListingState::QUOTA_HIDDEN));
        $this->assertSame('Over quota', ListingState::label(ListingState::QUOTA_OVER));
    }

    public function test_listing_type_creation_entitlement_mapping(): void
    {
        // PHASE 11 — the type→entitlement mapping is stable.
        $this->assertSame('listings', ListingType::BUSINESS->creationEntitlement());
        $this->assertSame('professionals', ListingType::PROFESSIONAL->creationEntitlement());
        $this->assertSame('stores', ListingType::STORE->creationEntitlement());
    }

    public function test_only_business_is_implemented_in_phase_1(): void
    {
        $this->assertTrue(ListingType::BUSINESS->isImplemented());
        $this->assertFalse(ListingType::PROFESSIONAL->isImplemented());
        $this->assertFalse(ListingType::STORE->isImplemented());
    }
}
