<?php

namespace App\Support;

/**
 * ListingIdentity
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 6 — TARGET LISTING IDENTITY & COEXISTENCE ARCHITECTURE
 * ─────────────────────────────────────────────────────────────────────────
 * Encodes the Phase 6 architectural analysis and the SELECTED coexistence
 * model as PURE, testable metadata. It performs no database access and no
 * migration.
 *
 * ── The selected architecture (DECISION) ──
 * Option E: a hybrid that keeps `Business` as the canonical parent identity
 * (Phase 5 Model B) and reuses the EXISTING `branches` table as the future
 * independent listing anchor — because `Branch` is already location-complete
 * and already `Searchable`. No new table, no new parent column.
 *
 * Epistemic markers: FACT · DECISION · ASSUMPTION · OPEN.
 */
final class ListingIdentity
{
    public const FACT = 'FACT';
    public const DECISION = 'DECISION';
    public const ASSUMPTION = 'ASSUMPTION';
    public const OPEN = 'OPEN';

    /** The architecture option selected in Phase 6. */
    public const SELECTED_OPTION = 'E';

    // ── Canonical parent definition (Phase 6 §4) ─────────────────────
    // A user-facing, discoverable aggregate (definition B), with a legacy
    // technical identity facet (A) during transition. NOT merely technical,
    // and NOT a temporary throwaway.
    public const PARENT_DEFINITION = 'B_user_facing_aggregate';

    /**
     * The selected architecture: options analysis with strengths, weaknesses,
     * risks, and migration/compatibility consequences.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function options(): array
    {
        return [
            'A' => [
                'name' => 'Business remains both parent and listing',
                'selected' => false,
                'marker' => self::DECISION,
                'summary' => 'Future independent Buea/Limbe listings continue to be Business rows; Branch stays a location detail.',
                'strengths' => [
                    'No schema change.',
                    'Business already owns reviews/favorites/analytics/coupons.',
                ],
                'weaknesses' => [
                    'Semantic contradiction: a "Business" that is also a single location.',
                    'A parent Business would coexist with location Businesses — which is the parent?',
                    'Branch becomes redundant once a location is a Business; two location models.',
                    'Risk of duplicate identity between the parent Business and its location Businesses.',
                ],
                'risks' => ['Duplicate identity', 'Ownership ambiguity', 'Search duplication', 'Subscription ambiguity'],
                'migration_consequences' => 'High — requires re-pointing shared children away from the parent.',
                'compatibility_consequences' => 'Poor — mixes "business" and "location" meaning in one table.',
            ],
            'B' => [
                'name' => 'Business records become the independent Listings',
                'selected' => false,
                'marker' => self::DECISION,
                'summary' => 'Account owns Business #50 (legacy parent) + Business #101 (Buea) + Business #102 (Limbe).',
                'strengths' => [
                    'Each listing is a first-class Business with all existing children working unchanged.',
                    'Listing type axis is reusable as-is.',
                ],
                'weaknesses' => [
                    'Duplicate identity: the legacy parent #50 and the location listings describe parts of the same real-world brand.',
                    'Ownership ambiguity: are #101/#102 additional Account "businesses" or the same one?',
                    'Subscription/quota ambiguity: they consume business slots.',
                    'Review/favorite/URL/search ambiguity: parent vs child competition for the same entity.',
                ],
                'risks' => ['Duplicate identity', 'Quota/entitlement inflation', 'SEO competition'],
                'migration_consequences' => 'Medium — but produces semantically duplicate parents.',
                'compatibility_consequences' => 'Mediocre — technically works, semantically confusing.',
            ],
            'C' => [
                'name' => 'Self-referential Business parent relationship (parent_business_id)',
                'selected' => false,
                'marker' => self::DECISION,
                'summary' => 'A nullable `parent_business_id` self-FK on `businesses` represents parent → child listings.',
                'strengths' => [
                    'Single table; no new listings table.',
                    'Relationship is explicit and queryable.',
                ],
                'weaknesses' => [
                    'Adds a schema column that is NOT yet required (Phase 6 forbids schema-for-abstraction-only).',
                    'Every existing Business query must learn to exclude/include children — high blast radius.',
                    'Children still occupy business slots → quota/entitlement ambiguity.',
                    'Deletion/archive semantics become recursive and error-prone.',
                    'Search must de-duplicate parent vs children.',
                ],
                'risks' => ['Query complexity', 'Quota inflation', 'Recursive delete risk', 'Search duplication'],
                'migration_consequences' => 'Medium-high — new column + broad query changes.',
                'compatibility_consequences' => 'Mediocre — every consumer of `businesses` is affected.',
            ],
            'D' => [
                'name' => 'New Listing table',
                'selected' => false,
                'marker' => self::DECISION,
                'summary' => 'A polymorphic/common `listings` table shared by Business/Professional/Store.',
                'strengths' => [
                    'Clean common identity + listing_type.',
                    'Best long-term normalization for many listing types.',
                ],
                'weaknesses' => [
                    'Phase 5 Model B does NOT change the justification: Business continues to exist as parent AND as the business listing.',
                    'Dual-read/dual-write risk between `businesses` and `listings`.',
                    'Every existing FK (reviews, favorites, analytics, leads, coupons, contacts, media) must be re-pointed or polymorphically migrated.',
                    'Highest migration complexity of all options with the least present benefit.',
                    'Explicitly out of scope / forbidden in Phase 6.',
                ],
                'risks' => ['Dual-read/dual-write', 'Largest migration surface', 'Premature abstraction'],
                'migration_consequences' => 'Very high.',
                'compatibility_consequences' => 'Poor in the short/medium term; theoretical long-term gain.',
            ],
            'E' => [
                'name' => 'Reuse the existing Branch as the independent listing anchor',
                'selected' => true,
                'marker' => self::DECISION,
                'summary' => 'Canonical parent = existing `Business`. Independent listing = the existing `Branch`, '
                    . 'which is already location-complete (name, geo, address, lat/long, phone, whatsapp, '
                    . 'status, hidden_at) and already `Searchable`. No new table, no new parent column.',
                'strengths' => [
                    'FACT: Branch already models a location in full (geo, address, phone, whatsapp, hours, overrides, status).',
                    'FACT: Branch is already `Searchable` with a `toSearchableArray()`.',
                    'FACT: The public profile already renders branches per-location (hours, coordinates, open-now).',
                    'No new table; no new `parent_*` column; no schema-for-abstraction-only.',
                    'Parent/listing coexistence is natural: Business = parent, Branch = listing.',
                    'Lower migration blast radius than A/B/C/D — shared children stay on the parent, '
                        . 'location data is ALREADY on the branch.',
                    'Extends to Professional/Store: those become additional parent listing types, '
                        . 'each with the same branch-as-location child.',
                ],
                'weaknesses' => [
                    'Branch children today are limited to hours; services/contacts/media/reviews etc. still '
                        . 'hang off the parent and would need explicit per-listing ownership in a LATER phase.',
                    'Branch has no slug yet; listing URLs need a slug strategy (no route change in Phase 6).',
                ],
                'risks' => [
                    'Branch is currently a location child — promoting it to public listing needs explicit '
                        . 'visibility rules (already has hidden_at/status, which helps).',
                    'Per-listing child data (services/reviews) is not location-scoped yet in the schema.',
                ],
                'migration_consequences' => 'Lowest of all options — no table/column change required to REPRESENT the model; '
                    . 'only later data-attribution work for shared children.',
                'compatibility_consequences' => 'Best — existing Business, Branch, search, and public profile all remain valid.',
            ],
        ];
    }

    /**
     * The precise definition of the canonical parent (Phase 6 §4).
     *
     * @return array<string, mixed>
     */
    public static function canonicalParent(): array
    {
        return [
            'definition' => self::PARENT_DEFINITION,
            'marker' => self::DECISION,
            'entity' => 'Business (unchanged)',
            'summary' => 'A user-facing, discoverable aggregate representing the overall organization, '
                . 'which ALSO carries a legacy technical-identity facet for continuity. It is not '
                . 'merely technical, and it is not a temporary throwaway.',
            'is_technical_legacy' => true,
            'is_user_facing_aggregate' => true,
            'is_temporary' => false,
            'affects' => [
                'url' => '/business/{slug} resolves to the parent (authoritative canonical URL).',
                'search' => 'Parent is indexed for aggregate discovery.',
                'favorites' => 'Legacy favorites resolve to the parent.',
                'reviews' => 'Historical reviews live on the parent.',
                'analytics' => 'Historical analytics baseline lives on the parent.',
                'seo' => 'Parent slug preserves indexed external links.',
                'ownership' => 'Parent is owned by the Account.',
                'external_links' => 'Third-party /business/{slug} links keep working.',
            ],
        ];
    }

    /**
     * The precise definition of an independent listing (Phase 6 §5).
     *
     * @return array<string, mixed>
     */
    public static function independentListing(): array
    {
        return [
            'marker' => self::DECISION,
            'anchor_entity' => 'Branch (existing, reused)',
            'summary' => 'An independently-manageable public location of a parent. Today its operational '
                . 'surface is the Branch row + its hours/overrides; it becomes publicly addressable '
                . 'in a later phase without a route change in Phase 6.',
            'independent_properties' => [
                'public_identity' => 'Branch.name (already present)',
                'location' => 'country/region/city/area/address/lat/long (already present)',
                'contacts' => 'phone + whatsapp (already present on the branch)',
                'hours' => 'business_hours + branch_hour_overrides (already keyed by branch_id)',
                'status_and_visibility' => 'status + hidden_at (already present)',
            ],
            'deferred_properties' => [
                'slug' => 'Not present on Branch — listing URL slug is defined by policy, not implemented.',
                'services' => 'Still business-level in the schema (Phase 5: not auto-duplicated).',
                'categories' => 'Still business-level in the schema.',
                'gallery' => 'Still business-level in the schema.',
                'reviews' => 'Post-migration reviews would scope to the listing; schema not yet location-scoped.',
                'leads' => 'Already branch-attributable via the nullable branch_id.',
                'analytics' => 'Future per-listing metrics start fresh; no branch column yet.',
                'coupons' => 'Policy-dependent; still business-level.',
            ],
        ];
    }

    /**
     * Parent vs Listing conceptual ownership matrix (Phase 6 §6).
     * Populated from the ACTUAL architecture, not the example.
     *
     * @return array<string, array<string, string>>
     */
    public static function ownershipMatrix(): array
    {
        return [
            // property => [parent, listing, account, note]
            'historical' => ['parent' => 'yes', 'listing' => 'no', 'account' => 'no', 'note' => 'Legacy records remain on the parent.'],
            'subscription' => ['parent' => 'no', 'listing' => 'no', 'account' => 'yes', 'note' => 'Account-scoped (unchanged).'],
            'ownership' => ['parent' => 'yes', 'listing' => 'yes', 'account' => 'yes', 'note' => 'Account owns parent and listings.'],
            'review' => ['parent' => 'historical', 'listing' => 'new', 'account' => 'no', 'note' => 'No branch column; never duplicated.'],
            'favorite' => ['parent' => 'legacy', 'listing' => 'new', 'account' => 'user', 'note' => 'Never copied 1→N.'],
            'analytics' => ['parent' => 'baseline', 'listing' => 'future', 'account' => 'no', 'note' => 'Never fabricated per-location.'],
            'services' => ['parent' => 'yes', 'listing' => 'no', 'account' => 'no', 'note' => 'Business-level today.'],
            'contacts' => ['parent' => 'brand', 'listing' => 'operational', 'account' => 'no', 'note' => 'Branch phone/whatsapp are listing-level.'],
            'hours' => ['parent' => 'no', 'listing' => 'yes', 'account' => 'no', 'note' => 'Already branch-keyed.'],
            'categories' => ['parent' => 'yes', 'listing' => 'no', 'account' => 'no', 'note' => 'Business-level today.'],
            'coupon' => ['parent' => 'policy', 'listing' => 'policy', 'account' => 'no', 'note' => 'Policy-dependent (Phase 5).'],
            'media' => ['parent' => 'brand', 'listing' => 'operational', 'account' => 'no', 'note' => 'logo/cover = brand; gallery may be location.'],
            'lead' => ['parent' => 'historical', 'listing' => 'new', 'account' => 'no', 'note' => 'branch_id when present.'],
        ];
    }

    /**
     * URL identity model (Phase 6 §7).
     *
     * @return array<string, mixed>
     */
    public static function urlIdentity(): array
    {
        return [
            'marker' => self::DECISION,
            'canonical_owner' => 'parent (Business)',
            'legacy_url' => '/business/{slug}',
            'legacy_still_valid' => true,
            'legacy_resolves_to' => 'parent (aggregate)',
            'listing_url_owner' => 'listing (Branch, future)',
            'listing_url_template' => '/business/{parent-slug}-{location-slug}',
            'listing_url_implemented' => false,
            'collision' => 'Generated listing slugs use the existing -2/-3 suffix strategy.',
            'seo' => 'Parent slug preserves indexed external links; listing slugs are new and additive.',
            'note' => 'No routes or redirects are changed in Phase 6.',
        ];
    }

    /**
     * Favorite identity model (Phase 6 §8).
     *
     * @return array<string, mixed>
     */
    public static function favoriteIdentity(): array
    {
        return [
            'marker' => self::DECISION,
            'legacy_favorite' => 'stays a favorite of the canonical parent (user-visible).',
            'new_favorite' => 'references an independent listing.',
            'with_branch_evidence' => 'may be associated with a specific listing.',
            'without_branch_evidence' => 'remains attached to the parent.',
            'parent_user_visible' => true,
            'no_duplication' => true,
        ];
    }

    /**
     * Review identity model (Phase 6 §9).
     *
     * @return array<string, mixed>
     */
    public static function reviewIdentity(): array
    {
        return [
            'marker' => self::DECISION,
            'parent_displays_historical' => true,
            'listing_displays' => 'only reviews generated after independence.',
            'manual_attribution' => 'permitted only with explicit evidence.',
            'totals_inherited' => false,
            'reputation_inherited' => false,
            'no_inflation' => true,
        ];
    }

    /**
     * Search identity model (Phase 6 §10).
     *
     * @return array<string, mixed>
     */
    public static function searchIdentity(): array
    {
        return [
            'marker' => self::DECISION,
            'parent_indexed' => true,
            'listing_indexed' => true,
            'both_indexed' => true,
            'parent_index_purpose' => 'aggregate discovery.',
            'duplicate_real_world_entity' => false,
            'note' => 'Branch is already Searchable; the future index will distinguish parent vs listing '
                . 'by an identity discriminator. No search redesign in Phase 6.',
        ];
    }

    /**
     * Readiness terminology (Phase 6 §15).
     *
     * @return array<string, string>
     */
    public static function readinessVocabulary(): array
    {
        return [
            'POLICY_READY' => 'The domain rules are defined (Phase 5 complete).',
            'STRUCTURE_READY' => 'The database/domain representation exists to hold the target model.',
            'MIGRATION_READY' => 'A specific Business can safely undergo migration.',
            'MIGRATION_VERIFIED' => 'A migrated result has passed verification.',
        ];
    }

    /**
     * PHASE 7 — the additive foundation that makes the coexistence model
     * structurally realisable. Structural support does NOT imply readiness:
     * a Business may be STRUCTURE_READY while still NOT MIGRATION_READY.
     *
     * @return array<string, mixed>
     */
    public static function capabilityFoundation(): array
    {
        return [
            'marker' => self::DECISION,
            'summary' => 'Branch gains the optional structure required to EVENTUALLY own '
                . 'listing-scoped data — without any data being migrated or re-attributed.',
            'existing' => [
                'location' => 'Branch already owns location (geo/address/lat-long).',
                'contacts' => 'Branch already owns phone + whatsapp.',
                'hours' => 'Branch already owns weekly hours + date overrides.',
                'visibility' => 'Branch already owns status + hidden_at.',
                'search' => 'Branch is already Searchable.',
            ],
            'added_optional_structure' => [
                'branch.slug' => 'nullable, unique — future listing public identity.',
                'branch_category' => 'separate pivot — future listing categories.',
                'branch_id (services/images/contacts/reviews/analytics/coupons)' => 'nullable — future listing-scoped records.',
            ],
            'guarantees' => [
                'No existing row is changed (all new columns are NULL by default).',
                'No slug is generated for existing branches.',
                'No branch consumes listing quota.',
                'No public route or URL changes.',
            ],
        ];
    }
}
