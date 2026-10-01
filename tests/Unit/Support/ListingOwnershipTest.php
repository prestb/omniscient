<?php

namespace Tests\Unit\Support;

use App\Support\ListingOwnership;
use PHPUnit\Framework\TestCase;

/**
 * Phase 5 — canonical listing ownership decisions (pure, no DB).
 */
class ListingOwnershipTest extends TestCase
{
    public function test_parent_identity_model_is_B(): void
    {
        $parent = ListingOwnership::parentIdentity();

        $this->assertSame('B', ListingOwnership::PARENT_MODEL);
        $this->assertSame('B', $parent['model']);
        $this->assertSame(ListingOwnership::DECISION, $parent['marker']);
    }

    public function test_parent_identity_documents_consequences_for_each_area(): void
    {
        $consequences = ListingOwnership::parentIdentity()['consequences'];

        foreach ([
            'reviews', 'favorites', 'urls', 'analytics', 'search',
            'historical_records', 'ownership', 'seo', 'external_links',
        ] as $area) {
            $this->assertArrayHasKey($area, $consequences, "Missing consequence: {$area}");
        }
    }

    public function test_every_policy_entity_has_a_marker_and_rule(): void
    {
        foreach (ListingOwnership::entityKeys() as $entity) {
            $policy = ListingOwnership::policies()[$entity];
            $this->assertArrayHasKey('marker', $policy, "{$entity} missing marker");
            $this->assertContains(
                $policy['marker'],
                [ListingOwnership::FACT, ListingOwnership::DECISION, ListingOwnership::ASSUMPTION, ListingOwnership::OPEN]
            );
            $this->assertNotEmpty($policy['rule'] ?? null, "{$entity} missing rule");
        }
    }

    public function test_review_policy_forbids_duplication(): void
    {
        $this->assertTrue(ListingOwnership::hasNoDuplicationRule('review'));
        $this->assertSame(ListingOwnership::DECISION, ListingOwnership::markerOf('review'));
    }

    public function test_no_duplication_required_for_review_service_category_contact_media_lead_analytics_favorite_coupon(): void
    {
        foreach ([
            'review', 'service', 'category', 'contact', 'media',
            'lead', 'analytics', 'favorite', 'coupon',
        ] as $entity) {
            $this->assertTrue(
                ListingOwnership::hasNoDuplicationRule($entity),
                "{$entity} must have a no-duplication rule"
            );
        }
    }

    public function test_hours_and_url_do_not_require_no_duplication(): void
    {
        $this->assertFalse(ListingOwnership::hasNoDuplicationRule('hours'));
        $this->assertFalse(ListingOwnership::hasNoDuplicationRule('url'));
    }

    public function test_unknown_policy_entity_returns_null_marker(): void
    {
        $this->assertNull(ListingOwnership::markerOf('nope'));
        $this->assertFalse(ListingOwnership::hasNoDuplicationRule('nope'));
    }

    public function test_review_policy_requires_branch_evidence_for_attribution(): void
    {
        $rule = ListingOwnership::policies()['review']['rule'];

        $this->assertStringContainsString('never become multiple', $rule);
        $this->assertStringContainsString('branch', $rule);
    }

    public function test_url_policy_preserves_legacy_slug(): void
    {
        $rule = ListingOwnership::policies()['url']['rule'];

        $this->assertStringContainsString('business/{slug}', $rule);
    }

    public function test_favorite_policy_keeps_parent_identity_for_legacy(): void
    {
        $rule = ListingOwnership::policies()['favorite']['rule'];

        $this->assertStringContainsString('parent', $rule);
        $this->assertStringContainsString('never copied', $rule);
    }
}
