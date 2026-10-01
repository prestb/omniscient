# Omniscient — Phase 3: Listing-Type Entitlements & Migration Readiness

> Status: **Phase 3 complete** — domain / enforcement / search infrastructure only.
> No production Branch → Listing migration performed. No data deleted, moved,
> copied, or reassigned. All migration tooling is **read-only**.
>
> Companion documents:
> - `docs/PHASE_1_ARCHITECTURE.md` (account / subscription / entitlement model)
> - `docs/PHASE_2_LISTING_DOMAIN.md` (Listing domain foundation, `listing_type`)

---

## A. Entitlement Architecture

Two **orthogonal** questions, answered by two independent mechanisms:

| Question | Concept | Mechanism |
|---|---|---|
| *How many listings may the account have?* | **Listing limit** (quota) | `plans.max_*` columns → `Plan::getLimit()` → `EntitlementService::limit()` |
| *Which KINDS of listing may the account create?* | **Allowed listing types** | `plans.features` JSON keyed by the listing-type entitlement → `Plan::hasFeature()` → `EntitlementService::canUse()` |

These are deliberately **not** conflated (Phase 3 §5). An account may have a
listing limit of 3 while only being allowed the `professional` type.

### Canonical API (single, no redundancy)

Added **one** method to `EntitlementService` (the existing central façade):

```php
// Does the plan allow this TYPE of listing at all?
$entitlements->allowsListingType($user, ListingType::BUSINESS);   // bool

// Is the account under its listing quota (regardless of type)?
$entitlements->canCreate($user, ListingType::BUSINESS);           // bool  (existing, Phase 2)

// Combined: type allowed AND quota available?
$entitlements->canCreateListingType($user, ListingType::STORE);   // bool  ← new, authoritative
```

- `canCreateListingType()` is the **single authoritative answer** to "may this
  account create a listing of this type right now?" It composes the two
  orthogonal rules; nothing else should re-derive it.
- `canCreate()` (Phase 2) remains as the *quota-only* check — it is not
  duplicated, it is the quota half.
- `allowsListingType()` is the *type-only* check — the type half.

### Type-allow flag lives in `plans.features`

No schema change is required: `plans.features` is already a JSON column. The
canonical keys are the `ListingType::creationEntitlement()` values:

| Listing type | `features` key | Notes |
|---|---|---|
| `BUSINESS` | `businesses` | **Present ∪ absent = allowed** for back-compat (see below) |
| `PROFESSIONAL` | `professionals` | absent ⇒ not allowed |
| `STORE` | `stores` | absent ⇒ not allowed |

**Back-compatibility rule (critical):** because `Business` predates this
mechanism, an **absent** `businesses` key means *allowed* (the historic
default). For the not-yet-shipped types, absent means *not allowed*. This is
implemented in `ListingType::allowedByDefault()` and documented so it can be
tightened deliberately later.

**No plan names appear in any of this logic.**

---

## B. Listing Quota Architecture

### What counts as "a listing" (Phase 3 §8)

```
ONE Business record (listing_type = business)  ==  ONE listing
ONE Branch record                              !=  a listing (until migrated)
```

Therefore, during the transitional Business/Branch period:

```
listing_count            == count(Business where listing_type = business)
listing_limit            == plan.max_businesses          (canonical)
remaining_listings       == max(0, listing_limit − listing_count)
```

`EntitlementService::listingQuota($user)` returns this as a single value object
so there is **one** calculation:

```php
[
  'type'       => 'business',
  'current'    => 2,
  'limit'      => 5,
  'remaining'  => 3,
  'unlimited'  => false,
  'over_quota' => false,
]
```

### Branches are explicitly NOT listings

Branches remain counted under the legacy `branches` quota only. This is
documented (Section C) and never silently merged into the listing count.

---

## C. Plan Enforcement

### `EntitlementService`
Gained (additive, no behaviour change to existing methods):
- `allowsListingType(User, ListingType): bool`
- `canCreateListingType(User, ListingType): bool` (authoritative composite)
- `listingQuota(User): array` (single listing-usage calculation)
- `quantifyOverQuota(User): array` (over-quota detection, no writes)
- `summary()` extended to also surface:
  `listing_quota`, `allowed_listing_types`, and per-type quotas — while
  **keeping** the existing numeric quota keys so no caller breaks.

### `PlanEnforcementService::summary()`
Was a **stub returning `[]`**. Now delegates to `EntitlementService` so there is
**one authoritative calculation** (Phase 3 §7) rather than a second code path:

```php
public function summary(Subscription $subscription): array
{
    $user = $subscription->user;               // account-scoped
    return $this->entitlements->summary($user);
}
```

The **enforcement write path** (`enforce()`, `applyVisibility()`, hiding via
`hidden_at`) is **unchanged** — Phase 3 does not alter production enforcement
behaviour, only the reporting/stub surface.

### Counting helper
`Plan::getLimit()` and `HasPlanFeatures::getCurrentUsage()` are unchanged; the
listing-specific count routes through `Business::listingsCountFor($userOrId)`,
which counts only `listing_type = business` rows (excluding
`deleted`/`rejected`), matching the historic `max_businesses` semantics.

---

## D. Downgrade Semantics (unlimited → limited)

### The preserved Phase 1 quirk
`SubscriptionService::isDowngrade()` skipped any axis where either side is
unlimited (`-1`/`999`). So `unlimited → limited` was **not** flagged as a
downgrade — meaning **no grace window and no enforcement** was armed.

### Phase 3 decision
Leaving unlimited capacity IS a reduction in capacity and SHOULD arm the
protection machinery — but **without deleting anything** (Phase 3 §10).

Implemented **additively and safely**:

- `SubscriptionService::isDowngrade()` is **left unchanged** (so all existing
  tests and the proration path keep working).
- A **new, explicitly-named** method captures the corrected semantics:

```php
SubscriptionService::leavesUnlimited(?Plan $old, ?Plan $new): bool
```

True iff the old plan was unlimited on any enforced axis and the new plan is
finite on that axis. This is wired into `SubscriptionController::selectPlan()`
so that leaving unlimited now **starts the same grace window** as a classic
downgrade (existing `downgradeGraceDate()` + `downgrade_grace_ends_at` column,
already honoured by `PlanEnforcementService`).

### Resulting behaviour (no data loss)

```
Account on unlimited plan creates 12 businesses.
Downgrades to a plan with max_businesses = 3.
  → grace window opens (7 days, configurable)
  → all 12 businesses remain, visible during grace  (QUOTA_OVER)
  → after grace, 9 become hidden (hidden_at), NOT deleted (QUOTA_HIDDEN)
  → owner is informed via existing dashboard "over limit" surface
  → upgrading restores them automatically (existing restore path)
```

Nothing is deleted; hiding is reversible; the owner may also delete voluntarily.
This is fully protected by tests (Section H).

---

## E. Search Changes

**One field added** to `Business::toSearchableArray()`:

```php
'listing_type' => $this->getListingType()->value,   // 'business'
```

- The existing index name (`businesses`), field set, ranking, filters, Scout
  config, autocomplete, and intent parsing are **unchanged**.
- No separate Professional/Store index is created (deferred).
- The field is filterable metadata for future segmentation; it does not affect
  current results because nothing queries it yet.
- Existing search tests remain green; new regression tests assert the field is
  present and equals `business`.

---

## F. Migration Dry Run (read-only)

New service: `App\Services\ListingMigrationAnalyzer`.

**It performs only reads.** No `save()`, `update()`, `create()`, `delete()`,
or raw write statements exist in the class.

```php
$report = $analyzer->analyze($business);   // returns an array, mutates nothing
```

It produces:

```
Business: SBKRAFT   Owner: User #123   listing_type: business
Candidate Listings:
  - SBKRAFT Buea   type=business   location=Buea
  - SBKRAFT Limbe  type=business   location=Limbe
Classification:
  A clearly_business_level:  [business identity, ...]
  B clearly_branch_level:    [branch.location, branch.hours, branch.special_hours]
  C shared_ambiguous:        [reviews, services, categories, images, contacts, leads, coupons]
  D derived_system:          [analytics, favorites-derived, slug, aggregates]
```

A thin Artisan command `listing:migration-dry-run {business}` renders the same
report for humans. Both are read-only and testable.

### Classification rationale (Phase 3 §14/§15)

| Relationship | Class | Why |
|---|---|---|
| Business identity/owner/categories/description | **A** | Parent identity |
| Branch row (geo, address, phone, whatsapp) | **B** | Genuinely per-location |
| Branch hours / special hours | **B** | Keyed by `branch_id` today |
| Reviews | **C** | Attached to `business_id` — cannot be auto-attributed per location |
| Services | **C** | Currently business-wide; per-location editability unknown |
| Categories | **C** | Business identity *and* discovery metadata |
| Images (logo/cover/gallery) | **C** | Brand assets vs. location gallery |
| Contacts | **C** | Can be business-wide or location-specific |
| Leads | **C** | `business_id` (+ optional `branch_id`) — assignable? |
| Coupons | **C** | Business-level today; branch applicability unknown |
| Analytics | **D** | Historic aggregates — regenerate, do not copy blindly |
| Favorites | **D** | Derived user→listing relation |

---

## G. Data Ambiguities (needs a product decision)

These are surfaced by the dry-run and are **not** resolved in Phase 3:

1. **Reviews** — attribution to a specific future listing (never copy 1×N).
2. **Services** — business-wide vs branch-specific vs shared-but-editable.
3. **Categories** — identity vs per-location discovery.
4. **Images** — which are brand (logo/cover) vs location gallery.
5. **Contacts** — shared vs per-location.
6. **Leads** — post-migration assignment.
7. **Coupons** — business-level vs branch-level ownership.
8. **Analytics** — retention at business level vs per-listing regeneration.
9. **Favorites** — referring to business identity or a specific location.
10. **`max_branches`** — retain as transitional, or fold into listing limit.

---

## H. Tests

| File | Tests | Focus | Result |
|---|---|---|---|
| `tests/Unit/Services/ListingEntitlementTest.php` | 11 | allowed/disallowed type, limit, remaining, over-quota, unlimited, composite `canCreateListingType` | PASS |
| `tests/Unit/Services/DowngradeSemanticsTest.php` | 9 | `leavesUnlimited`, `isDowngrade` regression, hide-not-delete, grace protection | PASS |
| `tests/Feature/Listing/ListingSearchPayloadTest.php` | 4 | `listing_type` in searchable array; field-set regression; index name unchanged | PASS |
| `tests/Unit/Services/ListingMigrationAnalyzerTest.php` | 7 | candidates, business identity, branch data, ambiguity, derived data, **no DB mutation** | PASS |
| `tests/Feature/Listing/ListingAuthorizationTest.php` | 6 | owner access, non-owner denial, admin/super-admin, per-account scoping | PASS |

**Full suite: 176 passed, 386 assertions, 1 pre-existing incomplete, 0 failures.**
(Net +37 from Phase 2's 139.) All existing suites remain green, including the
subscription, entitlement, plan-downgrade-safety and search suites.

---

## I. Data Safety

Phase 3 confirmed **no** production data was touched:

- No Branch or Business records deleted.
- No reviews / leads / analytics reassigned or copied.
- No production data copied anywhere.
- No public URL changed.
- Subscription ownership unchanged (still account-scoped).
- The migration analyzer is **read-only** (asserted by a test that snapshots
  row counts + a checksum before/after analysis).

---

## J. Deferred Work

- Professional & Store: models, profiles, dashboards, catalogs, routes.
- The actual Branch → Listing data migration (needs the Section G decisions).
- Per-listing management/team (`ListingManager`).
- Brand/Organization grouping.
- Multi-type search ranking & indexes.
- E-commerce / product catalogue.
- New homepage / design system / dashboards.
- Removing `max_branches` once Branch → Listing is complete.

---

## K. Recommended Phase 4

**Phase 4 — Product decisions + single-business migration pilot (feature-flagged).**

1. Resolve the Section G ambiguities into a written migration policy.
2. Implement the policy on **one opt-in business** in a staging clone using a
   copy-then-verify command (never in-place on production).
3. Add `ListingManager` (ownership ≠ management) behind a flag.
4. Introduce `listing_type`-aware search filtering in the UI (still no ranking
   redesign).
5. Only after the pilot verifies byte-for-byte data fidelity, schedule the
   general migration.

Every step remains reversible and data-preserving.
