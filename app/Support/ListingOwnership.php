<?php

namespace App\Support;

/**
 * ListingOwnership
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 5 — CANONICAL LISTING OWNERSHIP (decisions, not migration)
 * ─────────────────────────────────────────────────────────────────────────
 * This class is the single, machine-readable source of truth for the product
 * decisions made in Phase 5. It encodes POLICY ONLY — it performs no database
 * access and no migration. Every decision is tagged with an epistemic marker:
 *
 *   FACT       — verified directly from the existing schema/codebase.
 *   DECISION   — a product/domain rule chosen in Phase 5.
 *   ASSUMPTION — a working assumption that a later phase must confirm.
 *   OPEN       — genuinely undecidable until new data/fields exist.
 *
 * Companion document: docs/PHASE_5_CANONICAL_LISTING_OWNERSHIP.md
 */
final class ListingOwnership
{
    // ── Epistemic markers ────────────────────────────────────────────
    public const FACT = 'FACT';
    public const DECISION = 'DECISION';
    public const ASSUMPTION = 'ASSUMPTION';
    public const OPEN = 'OPEN';

    // ── The canonical parent-identity model (Phase 5 §2) ─────────────
    // Model B: the legacy Business becomes a canonical parent/legacy
    // identity; listings become independent public operational entities.
    // This preserves id/slug/URL continuity.
    public const PARENT_MODEL = 'B';

    /**
     * The canonical parent-identity decision, with consequences.
     *
     * @return array<string, mixed>
     */
    public static function parentIdentity(): array
    {
        return [
            'model' => self::PARENT_MODEL,
            'marker' => self::DECISION,
            'summary' => 'Legacy Business becomes a canonical parent/legacy identity; '
                . 'Listings become independent public operational entities.',
            'why' => 'It preserves businesses.id and businesses.slug for URL, SEO, '
                . 'favorite, review and external-link continuity, while still allowing '
                . 'each location to become an independently-managed listing.',
            'consequences' => [
                'reviews' => 'Remain attached to the legacy/parent identity (no branch evidence exists). Never duplicated.',
                'favorites' => 'Keep resolving via the stable parent id/slug; user meaning preserved.',
                'urls' => '/business/{slug} keeps resolving to the parent; new listings get new slugs.',
                'analytics' => 'Historical metrics stay on the parent as a baseline; listings start fresh.',
                'search' => 'Parent and listings are both indexable; no forced merge.',
                'historical_records' => 'Legacy FKs (business_id) remain valid forever.',
                'ownership' => 'Parent and listings stay on the same Account.',
                'seo' => 'Existing indexed URLs stay valid; no destructive rewrite.',
                'external_links' => 'Third-party links to /business/{slug} continue to work.',
            ],
            'alternatives' => [
                'A' => 'Old Business disappears, each Branch becomes a listing. Rejected: breaks id/slug/URL/favorite continuity and orphans historical records.',
                'C' => 'A separate listings table. Rejected: no justification in the existing architecture and forbidden in Phase 5 scope.',
            ],
        ];
    }

    /**
     * Per-entity canonical policies. Each entry:
     *   marker, owner_after, rule, rationale, evidence, no_duplication.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function policies(): array
    {
        return [
            'review' => [
                'marker' => self::DECISION,
                'owner_after' => 'parent/legacy identity (unless branch evidence exists)',
                'no_duplication' => true,
                'rule' => 'Historical reviews with no branch evidence stay on the parent identity. '
                    . 'A review is attributed to a specific listing ONLY when explicit branch '
                    . 'evidence exists. New reviews attach to the listing whose profile generated '
                    . 'them. One historical review must never become multiple active listing reviews.',
                'rationale' => 'Reputation integrity: duplicating a review inflates reputation '
                    . 'and violates the meaning of a review.',
                'evidence' => 'reviews table has business_id only — no branch_id column.',
            ],
            'service' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing (future) / parent until policy applied',
                'no_duplication' => true,
                'rule' => 'Services are business-wide today and are NOT auto-duplicated to every '
                    . 'listing. Future services belong directly to Listings; historically ambiguous '
                    . 'services stay parent-level pending explicit per-listing assignment.',
                'rationale' => 'Not every branch necessarily provides every service.',
                'evidence' => 'listing_services is listing_id-scoped; business_id was dropped in Phase 11 Wave 1B.',
            ],
            'category' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing (future) / parent for legacy',
                'no_duplication' => true,
                'rule' => 'Categories describe overall business identity/discovery today. The '
                    . 'target model allows categories per Listing so Buea and Limbe may differ. '
                    . 'Legacy business-level categories stay on the parent and are not blindly '
                    . 'duplicated to every listing.',
                'rationale' => 'The model must not artificially make every location identical.',
                'evidence' => 'business_categories is a business_id<->category_id pivot with is_primary.',
            ],
            'contact' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing (future)',
                'no_duplication' => true,
                'rule' => 'Business-level contacts stay on the parent. Per-location phone/whatsapp '
                    . 'already live on the branch and move with it. A contact belonging to one '
                    . 'location must never silently become another location\'s contact.',
                'rationale' => 'A Buea phone must not become the Limbe phone.',
                'evidence' => 'listing_contacts is listing_id-scoped; place-specific phone/whatsapp live on the Location.',
            ],
            'media' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing (future) / parent brand assets',
                'no_duplication' => true,
                'rule' => 'logo and cover are brand assets (parent identity); gallery may be '
                    . 'location-specific. Each listing may hold its own logo/cover/gallery. '
                    . 'Physical files are not moved and public URLs are not changed in this phase.',
                'rationale' => 'Avoid unnecessary file duplication while allowing per-listing galleries.',
                'evidence' => 'listing_images is listing_id-scoped with type in {logo, cover, gallery}; paths are public URLs.',
            ],
            'lead' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing when branch_id exists; parent/historical otherwise',
                'no_duplication' => true,
                'rule' => 'A lead with branch_id becomes a historical lead of that listing. A lead '
                    . 'without branch_id stays historical/ambiguous on the parent. Leads are never '
                    . 'duplicated across listings. Future leads attach directly to a Listing.',
                'rationale' => 'Leads are historical activity; duplication would falsify sources.',
                'evidence' => 'leads has business_id + nullable branch_id (nullOnDelete).',
            ],
            'analytics' => [
                'marker' => self::DECISION,
                'owner_after' => 'parent baseline (legacy); listing (future)',
                'no_duplication' => true,
                'rule' => 'Historical analytics remain a parent baseline. Per-listing analytics start '
                    . 'at migration time. No historical per-location metrics are fabricated. Any '
                    . 'combined view must be labelled an aggregate, not known per-location data.',
                'rationale' => 'We cannot truthfully attribute historical traffic to a location.',
                'evidence' => 'listing_analytics is keyed by (listing_id, date).',
            ],
            'favorite' => [
                'marker' => self::DECISION,
                'owner_after' => 'parent (legacy favorites); listing (new favorites)',
                'no_duplication' => true,
                'rule' => 'Existing favorites (user_id, business_id) keep pointing at the stable '
                    . 'parent id/slug and remain visible as a legacy/parent favorite. New favorites '
                    . 'reference the independent Listing identity. One favorite is never copied into '
                    . 'multiple listings. A user wanting a specific location explicitly chooses it.',
                'rationale' => 'A stable business id does not reveal which listing the user intended.',
                'evidence' => 'favorites is (user_id, business_id) unique.',
            ],
            'coupon' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing (future) / parent for legacy',
                'no_duplication' => true,
                'rule' => 'Business-level coupons stay parent-level unless the business explicitly '
                    . 'intends separate listing offers. A branch-specific coupon is attributed only '
                    . 'with explicit evidence. Expired coupons are retained. Redemption history is '
                    . 'an immutable audit trail and is never duplicated or reassigned.',
                'rationale' => 'Avoid duplicate active offers while keeping redemption auditability.',
                'evidence' => 'coupons has business_id; coupon_redemptions has coupon_id only.',
            ],
            'hours' => [
                'marker' => self::DECISION,
                'owner_after' => 'listing',
                'no_duplication' => false,
                'rule' => 'Weekly hours and special/date overrides already key on branch_id and move '
                    . 'with the location into the listing. Handles no-branch, single-branch and '
                    . 'multi-branch businesses; incomplete hours are carried as-is (never invented).',
                'rationale' => 'Hours are genuinely per-location and unambiguous.',
                'evidence' => 'business_hours + branch_hour_overrides both key on branch_id.',
            ],
            'url' => [
                'marker' => self::DECISION,
                'owner_after' => 'parent (legacy URL); listing (new URL)',
                'no_duplication' => false,
                'rule' => 'Legacy /business/{slug} keeps resolving to the parent (or 301s to a '
                    . 'chosen canonical listing). New listings get unique generated slugs. Slug '
                    . 'collisions are resolved with the existing -2/-3 suffix strategy. No routes '
                    . 'or redirects are changed in this phase.',
                'rationale' => 'Preserve external links and SEO; no destructive URL change.',
                'evidence' => 'business.show route is business/{slug}; slug unique; generateUniqueSlug() appends -N.',
            ],
            'ownership' => [
                'marker' => self::DECISION,
                'owner_after' => 'account (unchanged)',
                'no_duplication' => false,
                'rule' => 'Account owns listings. OWNER (account/business owner) is defined now. '
                    . 'MANAGER and COLLABORATOR are defined conceptually but NOT implemented. '
                    . 'Existing owner authorization (canBeEditedBy) is unchanged.',
                'rationale' => 'Keep current authorization intact until a later implementation phase.',
                'evidence' => 'Business::canBeEditedBy(); User role constants; owner_id -> User.',
            ],
        ];
    }

    /**
     * The entity keys that have a canonical Phase 5 policy.
     *
     * @return array<int, string>
     */
    public static function entityKeys(): array
    {
        return array_keys(static::policies());
    }

    /**
     * The epistemic marker for a given policy entity, or null if unknown.
     */
    public static function markerOf(string $entity): ?string
    {
        $p = static::policies();

        return $p[$entity]['marker'] ?? null;
    }

    /**
     * Whether the named entity carries a hard no-duplication rule.
     */
    public static function hasNoDuplicationRule(string $entity): bool
    {
        $p = static::policies();

        return (bool) ($p[$entity]['no_duplication'] ?? false);
    }
}
