<?php

namespace Tests\Unit\Support;

use App\Support\DataOwnership;
use PHPUnit\Framework\TestCase;

/**
 * Phase 4 — data ownership matrix classification (pure, no DB).
 */
class DataOwnershipTest extends TestCase
{
    public function test_business_identity_is_listing_owned(): void
    {
        $this->assertSame(
            DataOwnership::LISTING_OWNED,
            DataOwnership::classOf('business.identity')
        );
    }

    public function test_location_place_and_hours_are_location_owned(): void
    {
        foreach (['location.place', 'location.hours', 'location.special_hours'] as $entity) {
            $this->assertSame(
                DataOwnership::LOCATION_OWNED,
                DataOwnership::classOf($entity),
                "{$entity} should be location-owned"
            );
        }
    }

    public function test_subscription_and_owner_are_account_owned(): void
    {
        $this->assertSame(DataOwnership::ACCOUNT_OWNED, DataOwnership::classOf('subscriptions'));
        $this->assertSame(DataOwnership::ACCOUNT_OWNED, DataOwnership::classOf('owner'));
    }

    public function test_reviews_leads_services_contacts_favorites_are_ambiguous(): void
    {
        foreach (['reviews', 'leads', 'services', 'contacts', 'favorites'] as $entity) {
            $this->assertSame(
                DataOwnership::SHARED_AMBIGUOUS,
                DataOwnership::classOf($entity),
                "{$entity} should be shared/ambiguous"
            );
        }
    }

    public function test_analytics_and_verification_are_derived(): void
    {
        $this->assertSame(DataOwnership::DERIVED_SYSTEM, DataOwnership::classOf('listing_analytics'));
        $this->assertSame(DataOwnership::DERIVED_SYSTEM, DataOwnership::classOf('verification_badge'));
    }

    public function test_coupon_redemptions_are_legacy_audit(): void
    {
        $this->assertSame(DataOwnership::LEGACY_ADMIN, DataOwnership::classOf('coupon_redemptions'));
    }

    public function test_ambiguous_entities_helper_lists_all_shared_ambiguous(): void
    {
        $ambiguous = DataOwnership::ambiguousEntities();

        foreach (['reviews', 'services', 'categories', 'images', 'contacts', 'leads', 'coupons', 'favorites'] as $entity) {
            $this->assertContains($entity, $ambiguous);
        }

        // Account-owned data must NEVER appear as ambiguous.
        $this->assertNotContains('subscriptions', $ambiguous);
    }

    public function test_location_owned_helper_is_exactly_the_move_with_location_set(): void
    {
        $this->assertEqualsCanonicalizing(
            ['location.place', 'location.hours', 'location.special_hours'],
            DataOwnership::locationOwnedEntities()
        );

        // Back-compat alias resolves to the same set.
        $this->assertEqualsCanonicalizing(
            ['location.place', 'location.hours', 'location.special_hours'],
            DataOwnership::branchOwnedEntities()
        );
    }

    public function test_account_owned_helper_never_includes_listing_children(): void
    {
        $accountOwned = DataOwnership::accountOwnedEntities();

        $this->assertContains('subscriptions', $accountOwned);
        $this->assertContains('owner', $accountOwned);
        $this->assertNotContains('reviews', $accountOwned);
        $this->assertNotContains('location.place', $accountOwned);
    }

    public function test_reviews_are_marked_high_ambiguity(): void
    {
        $this->assertSame('high', DataOwnership::ambiguityOf('reviews'));
        $this->assertSame('high', DataOwnership::ambiguityOf('leads'));
        $this->assertSame('high', DataOwnership::ambiguityOf('listing_analytics'));
    }

    public function test_unknown_entity_returns_null(): void
    {
        $this->assertNull(DataOwnership::classOf('does_not_exist'));
        $this->assertNull(DataOwnership::ambiguityOf('does_not_exist'));
    }
}
