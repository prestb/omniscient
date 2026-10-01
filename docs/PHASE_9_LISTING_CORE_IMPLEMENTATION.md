# Omniscient — Phase 9: Listing Core Implementation

> Status: **IMPLEMENTED.**
> This document records the transitions that were **actually applied to the
> working tree** in Phase 9. It was written to match the code, not an
> assumed history: where a prior phase described machinery that was never
> present on disk, that is stated explicitly rather than claimed as a
> removal.
>
> Markers: **FACT** · **DECISION** · **OPEN** · **DEFERRED**.

---

## 0. Repository reality check (read this first)

**FACT.** This repository's tracked baseline (`HEAD`) contains **no** listing
domain at all:

- No `listings` table or `Listing` model.
- No `listing_type` column on `businesses`.
- No `branch_id` columns on the child tables, no `branch_category` pivot,
  no `branches.slug` — and **no migrations that ever added them**.
- No `ListingType` / `ListingState` / `ListingIdentity` / `ListingOwnership` /
  `ListingCapability` / `DataOwnership` support classes (the `app/Support`
  listing classes exist only as **untracked** working-tree files).

The Phase 6/7 documents (`docs/PHASE_6_*`, `docs/PHASE_7_*`) describe an
experimental "Branch-as-listing" design; that experiment **was never committed
to this repository's schema or models**. Some of its ideas existed only as
untracked code/doc drafts.

**Consequence for this document.** Phase 9 is therefore best described as a
**first implementation** of the Listing core on top of a Business/Branch
baseline — not as the removal of a previously-shipped Branch-as-listing
system. Section §5 and §9 call out which of the new migration's "drop"
statements are **defensive no-ops** against columns that do not exist in this
database.

---

## 1. What changed (summary)

| Area | Before (working-tree baseline) | After (Phase 9) |
|---|---|---|
| Discoverable entity | `Business` (no type axis) | **`Listing`** |
| Listing type | *did not exist* | **`listings.type`** |
| Organization | `Business` = the business entity | `Business` (**organization**) |
| Location | `Branch` (a place) | `Branch` (**pure Location**, `listings.location_id`) |
| Child ownership | `business_id` only | **`business_id` org pointer + `listing_id` (authoritative)** |
| Categories | `business_categories` only | `business_categories` + **`listing_categories`** |
| Quota metric | `User::businesses()->count()` | **`Listing::countFor()`** (Listing rows) |
| Retired support class | `ListingCapability`, `ListingIdentityKind` | **DELETED (unreferenced)** |

Everything in the "After" column is present in the working tree and exercised
by tests. Everything in the "Before" column reflects what was actually on disk
prior to this phase.

---

## 2. New entity: `Listing` (FACT)

`app/Models/Listing.php`, table `listings`.

Columns:

- `owner_id` → `users.id` — the canonical Account ownership edge.
- `business_id` → `businesses.id` (**nullable**) — optional organization.
- `location_id` → `branches.id` (**nullable**) — optional physical Location.
- `type` — canonical listing type (`App\Support\ListingType`).
- `name`, `slug` (globally unique), `description` — identity.
- `status`, `is_featured`, `published_at`, `hidden_at` — lifecycle/visibility.

### Invariants (DECISION)

1. **Ownership is the Account.** `owner_id` is the canonical ownership edge.
2. **A Listing has ZERO OR ONE Location.** Multiple physical locations are
   represented by **multiple Listings**, never one Listing with many locations.
3. **`business_id = NULL` is a standalone listing** (e.g. a locationless
   Professional). Not every Listing must belong to a Business.
4. **`type` is the single source of truth.** There is no `businesses.listing_type`.
5. **Entitlements are account-scoped** (subscription → plan → limits), never
   per-listing.

---

## 3. Business is an organization (FACT)

`app/Models/Business.php` — changed `+62 / −1` lines.

**Added:**

- `listings()` — `hasMany(Listing::class)`.
- `organizationsCountFor()` — an explicit, clearly-named ORGANIZATION count
  (NOT listing quota).
- A class-level docblock establishing Business as an organization.

**Not removed (because it never existed here):** `Business` did **not** have
`listing_type`, `scopeOfType()`, `getListingType()`, `isListingType()`,
`getListingDisplayName()`, `listingOwner()`, or `listingsCountFor()` in this
repository. The Phase 2 document (`docs/PHASE_2_LISTING_DOMAIN.md`) *proposed*
such an abstraction, but it was never committed to the model. The `−1` line in
the Business diff is unrelated cleanup, not a method deletion.

> Note: a class comment in `Business.php` still refers to "the former Phase 2
> listing abstraction methods". In light of §0 this is aspirational wording;
> those methods were never present here. (Documentation nit, not a behaviour.)

An organization owns zero-or-more Listings. "ABC Restaurant" = one `Business`;
its Buea/Limbe presences = two `Listing`s.

---

## 4. Branch is a Location (FACT)

`app/Models/Branch.php` — changed `+68 / −…` lines.

**Added:**

- `listings()` — `hasMany(Listing::class, 'location_id')`.
- `isPubliclyVisible()` — status `active` AND `hidden_at === null`.
- A class-level docblock establishing Branch as a place.

**Removed:** the trailing **commented-out duplicate blocks**
(`toSearchableArray`, `getIsOpenNowAttribute`, `getHoursSummaryAttribute`) that
were dead code at the end of the file at `HEAD`.

**Not removed (because it never existed here):** `slug`, `identity_kind` shim,
and Phase-7 listing relationships were **never** on this `Branch` model.

Kept: geo/address/lat-long/hours/overrides/phone/whatsapp + `is_primary`.

A Branch is a **place**, not a discoverable entity. Its search document is
location metadata used to enrich listing results — never a standalone result.

---

## 5. Child ownership (DECISION)

The migration
`database/migrations/2026_10_01_000002_repoint_children_to_listings_and_remove_phase7.php`
adds a nullable, listing-scoped `listing_id` FK (cascade-on-delete) to every
discoverable-child table:

| Table | New FK | Organization pointer kept |
|---|---|---|
| `business_services` | `listing_id` | `business_id` |
| `business_images` | `listing_id` | `business_id` |
| `business_contacts` | `listing_id` | `business_id` |
| `reviews` | `listing_id` | `business_id` |
| `business_analytics` | `listing_id` | `business_id` |
| `leads` | `listing_id` | `business_id` |
| `coupons` | `listing_id` | `business_id` |
| `favorites` | `listing_id` | `business_id` |

`listing_id` is the **authoritative listing scope**; `business_id` is the
**organization pointer** (transitional dual-FK, see §10).

The corresponding models gained a `listing()` relation (and `listing_id` in
`$fillable`): `BusinessService`, `BusinessImage`, `BusinessContact`, `Review`,
`BusinessAnalytics`, `Lead`, `Coupon`, `Favorite`.

### Defensive "drop" statements — most are no-ops here

The migration also contains **guarded** drops for the Phase-6/7 artifacts it
would remove *if they existed*:

- `branch_id` on `business_services`, `business_images`, `business_contacts`,
  `reviews`, `business_analytics`, `coupons`.
- `leads.branch_id`.
- `branch_category` pivot.
- `branches.slug`.
- `businesses.listing_type`.

**FACT:** in this repository's schema, **none of these columns/tables
existed**, so each drop is a guarded no-op (the migration checks
`Schema::hasColumn` / `hasTable` first). They are retained as a safety net for
databases that *did* apply the (never-committed) Phase 6/7 drafts. They are
**not** evidence that the artifacts were shipped here.

---

## 6. Categories (DECISION)

- `listing_categories` (pivot): `listing_id` ↔ `category_id`, with
  `is_primary` + `sort_order`. Created by migration 000002.
- `Listing::categories()` and `Category::listings()` are the canonical path.
- `business_categories` is retained for organization-level defaults only.
- `branch_category` — the migration drops it **if present**; it is absent here
  (see §5), so this too is a no-op.

---

## 7. Analytics (DECISION)

- Analytics rows are owned by the Listing (`listing_id`), scoped per day.
- Organization-level analytics are **derived** by summing their listings — never
  stored as duplicate history.
- `business_analytics` originally had a UNIQUE on `(business_id, date)`. The
  migration **drops that unique** (dropping and recreating the `business_id`
  FK as needed, because MySQL will not drop an index an FK depends on) and
  adds a non-unique `(listing_id, date)` index instead. `listing_id = NULL`
  rows are organization-level aggregate rows and may repeat across dates.
- `BusinessAnalytics` gained `listing()`, `scopeForListing()`, a `scopeKey()`
  helper, and optional `?int $listingId` parameters on `track*()` / `getStats()`.

---

## 8. Quota / entitlement (DECISION)

- `HasPlanFeatures::getCurrentUsage('businesses')` now returns
  **`Listing::countFor($account)`** — the canonical listing-quota metric.
- `EntitlementService::listingQuota()` measures the `businesses` entitlement
  against Listings; `type` reports `business` for continuity.
- `max_branches` continues to count physical Locations.
- Service/image quota now counts rows whose `listing_id` belongs to the
  account's Listings.
- **DEFERRED:** the plan-column rename `max_businesses → max_listings`,
  `max_branches → max_locations` (Phase 13 in the Phase 8 sequence).

---

## 9. Migrations

**Added (this phase):**

- `2026_10_01_000001_create_listings_table.php` — the `listings` table.
- `2026_10_01_000002_repoint_children_to_listings_and_remove_phase7.php` —
  adds `listing_id` everywhere, creates `listing_categories`, resets the
  analytics uniqueness onto the listing axis, and defensively drops Phase-6/7
  artifacts if they exist (§5).

**Removed:** **none.** No migration files were deleted, because the Phase 6/7
migrations the Phase 8 decision asked to remove were never committed to this
repository (see §0). `git status` shows only the two new untracked migrations.

---

## 10. Transitional dual-FK (DECISION, with a documented end)

This phase keeps `business_id` on children as an **organization pointer** while
`listing_id` becomes authoritative. This is a deliberate transition, NOT a
permanent dual-ownership model:

- New code reasons in Listing terms (`$listing->reviews()`, etc.).
- Organization pages may still aggregate via `business_id`.
- **OPEN / DEFERRED:** a later phase (Phase 14 in the Phase 8 sequence) may drop
  `business_id` from children once every organization-level view derives through
  Listings.

The brief's prohibition is on **nullable `branch_id` for backward
compatibility** — that machinery has no place in the forward model here (and,
per §0, was never present in this repository's schema). The `listing_id` axis
is the forward model; `business_id` remains a meaningful organization
association.

---

## 11. Tests

All test files under `tests/Feature/Listing/`, `tests/Feature/Architecture/`,
and the listing-related `tests/Unit/*` are **untracked** (never committed), so
"removed/added" here means within the working tree.

**Removed from the working tree (asserted the abandoned
Business/Branch-as-listing narrative):**
`ListingDomainTest`, `ListingPolicyTest`, `ListingAuthorizationTest`,
`ListingSearchPayloadTest`, `ListingsAnalyticsBaselineTest`,
`MigrationInvariantsTest`, `BranchListingFoundationTest`,
`ListingIdentityCoexistenceTest`, `ListingIdentityTest`,
`ListingCapabilityTest`.

**Added (`tests/Feature/Listing/`):**

- `ListingCoreTest` — the Listing entity: ownership, type, standalone listings,
  0/1 location, lifecycle, slug uniqueness.
- `ListingChildOwnershipTest` — children are listing-scoped, no duplication.
- `ListingQuotaTest` — quota counts Listings, not organizations/locations.
- `ListingSchemaTest` — the new columns exist and no `branch_id`-for-compat
  columns remain.

**Updated:** `tests/Unit/Services/EntitlementServiceTest.php` and
`tests/Unit/Services/ListingEntitlementTest.php` now create `Listing` rows
(instead of `Business` rows) to assert quota.

**Result:** `php artisan test` → **211 passed, 567 assertions** (1
pre-existing incomplete).

---

## 12. Files changed (this phase)

**Models (modified):** `Business`, `Branch`, `User`, `Category`, `Review`,
`BusinessService`, `BusinessImage`, `BusinessContact`, `Coupon`, `Lead`,
`Favorite`, `BusinessAnalytics`.
**Models (new):** `Listing`.
**Factories (new):** `ListingFactory`.

**Support/Services (modified):** `DataOwnership` (now exposes
`listingScopedTables()` enumerating the eight tables that carry `listing_id`,
and a `listing.type` entry), `ListingMigrationAnalyzer` (no longer calls the
non-existent `Business::getListingType()`), `HasPlanFeatures`,
`EntitlementService`.
**Support (deleted):** `ListingCapability` (unreferenced),
`ListingIdentityKind` (unreferenced, contradicted the model).

**Migrations (new):** two (§9). **Migrations (deleted):** none.

**Tests:** nine removed, four added, two updated (§11).

**Docs:** this document.

> Unrelated working-tree changes present in `git status` (e.g.
> `SubscriptionController.php`, `DirectoryController.php`,
> `PlanEnforcementService.php`, `BusinessHourFactory.php`) belong to other
> phases and are **not** part of Phase 9.

---

## Appendix — Brief deliverable index

| Brief item | Where |
|---|---|
| Repository reality check | §0 |
| Listing entity | §2, `app/Models/Listing.php` |
| Business demoted | §3, `Business.php` |
| Branch demoted | §4, `Branch.php` |
| Child ownership | §5, migration 000002 |
| Categories | §6 |
| Analytics | §7 |
| Quota | §8 |
| Migrations | §9 |
| Tests | §11 |
