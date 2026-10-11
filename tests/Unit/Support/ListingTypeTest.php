<?php

namespace Tests\Unit\Support;

use App\Support\Entitlement;
use App\Support\ListingType;
use PHPUnit\Framework\TestCase;

/**
 * Phase 2 — coverage for the Listing type axis.
 */
class ListingTypeTest extends TestCase
{
    public function test_all_declared_types_are_implemented(): void
    {
        $this->assertTrue(ListingType::BUSINESS->isImplemented());
        $this->assertTrue(ListingType::PROFESSIONAL->isImplemented());
        $this->assertTrue(ListingType::STORE->isImplemented());

        $this->assertSame(
            [ListingType::BUSINESS, ListingType::PROFESSIONAL, ListingType::STORE],
            ListingType::implemented()
        );
    }

    public function test_creation_entitlement_mapping_is_stable(): void
    {
        $this->assertSame(
            Entitlement::CREATE_LISTING,
            ListingType::BUSINESS->creationEntitlement()
        );
        $this->assertSame(
            Entitlement::CREATE_PROFESSIONAL,
            ListingType::PROFESSIONAL->creationEntitlement()
        );
        $this->assertSame(
            Entitlement::CREATE_STORE,
            ListingType::STORE->creationEntitlement()
        );
    }

    public function test_labels_are_human_readable(): void
    {
        $this->assertSame('Business', ListingType::BUSINESS->label());
        $this->assertSame('Professional', ListingType::PROFESSIONAL->label());
        $this->assertSame('Store', ListingType::STORE->label());
    }

    public function test_from_stored_defaults_safely(): void
    {
        $this->assertSame(ListingType::BUSINESS, ListingType::fromStored(null));
        $this->assertSame(ListingType::BUSINESS, ListingType::fromStored(''));
        $this->assertSame(ListingType::BUSINESS, ListingType::fromStored('nonsense'));
        $this->assertSame(ListingType::STORE, ListingType::fromStored('store'));
        $this->assertSame(ListingType::BUSINESS, ListingType::fromStored(ListingType::BUSINESS));
    }

    public function test_values_are_db_and_url_safe(): void
    {
        foreach (ListingType::cases() as $type) {
            $this->assertMatchesRegularExpression('/^[a-z]+$/', $type->value);
        }
    }
}
