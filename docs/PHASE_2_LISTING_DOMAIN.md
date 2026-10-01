# Omniscient — Phase 2: Listing Domain Foundation

> Status: **Phase 2 complete** — Listing domain foundation established.
> No destructive migration. Business and Branch remain fully functional.
> Professional and Store are NOT implemented (design-only).
>
> Companion documents:
> - `docs/PHASE_1_ARCHITECTURE.md` (canonical account/subscription/entitlement model)

---

## A. Architecture Decision

### The options

| Option | Description | Verdict |
|---|---|---|
| **A** | Business *becomes* the Listing (rename/refactor model + table). | ❌ Rejected — touches URLs, routes, tests, search index name, every FK and relationship, for no immediate functional gain. High regression risk. |
| **B** | Introduce a new `Listing` entity; gradually migrate Business into it. | ❌ Rejected for now — creates a parallel table + dual-write/dual-read complexity before a *second* listing type even exists. The new table would hold ~the same columns as `businesses`, so it buys architecture but costs correctness. |
| **C** | A Listing *abstraction* using Business as the first concrete implementation. | ✅ Base decision (see D below). |
| **D** | Justified variant of C: a **non-invasive Listing domain** — Business is the first concrete `ListingType`, declared via an additive `listing_type` axis, with an account→listings ownership contract and an explicit, documented branch→listing evolution path. **No production child-data migration is performed in Phase 2.** | ✅ **SELECTED** |

### Why Option D (and not a full C data migration)

The Phase 2 brief asks (§8) that independently-managed branches eventually stop
sharing operational data (services/hours/contacts/reviews/analytics). Repository
evidence shows that today **all** of those children hang off `business_id`, not
`branch_id`:

```
businesses
  ├── business_services   (business_id)
  ├── business_contacts   (business_id)
  ├── business_images     (business_id)
  ├── business_categories (business_id pivot)
  ├── reviews             (business_id)
  ├── leads               (business_id, optional branch_id)
  ├── coupons             (business_id)
  ├── business_analytics  (business_id)
  ├── favorites           (business_id)
  └── branches            (business_id)
        ├── business_hours        (branch_id)   ← branch-level
        └── branch_hour_overrides (branch_id)   ← branch-level
```

Promoting each branch to an independent listing **with its own** services/
reviews/contacts/analytics requires *moving* business-child rows down to the
branch level. That is:
- a **destructive/ambiguous** transformation (which branch owns a shared review?),
- explicitly forbidden to do blindly (§2, §16: "Do not migrate all production
  records blindly"), and
- not required to advance the architecture.

**Phase 2 therefore establishes the *shape* without the risky *data move*.**
The `listing_type` axis, the account→listings contract, and the documented
migration path are all real and implemented; the physical branch→listing data
migration is deferred to a later phase with its own safety gate.

---

## B. Database Changes

One **additive, reversible** migration:

`2026_09_26_000001_add_listing_type_to_businesses_table.php`

| Change | Detail |
|---|---|
| Column | `businesses.listing_type` — `string(32)`, `nullable()`, placed **after** `slug`. |
| Index | `businesses.listing_type`. |
| Backfill | In `up()`, after adding the column, an `UPDATE` sets `listing_type = 'business'` for **all existing rows** (including soft-deleted, via raw update — no model events, no side effects on `updated_at`). |
| Default | None at the DB level (nullable) so the backfill is explicit and the column stays future-proof; the **model** enforces the default (see Model Changes). |
| Reversal | `down()` drops the index and the column. No data loss for any other column. |

**Why this is safe:**
- Additive only. No existing column renamed, dropped, or altered.
- Nullable → no lock-heavy rewrite of the table on insert.
- Index added in the same statement batch; on MySQL an index add on a
  nullable string column is online-friendly.
- The backfill is a single `UPDATE ... WHERE listing_type IS NULL`, idempotent.

No other table is touched. **No data is moved or deleted.**

---

## C. Model Changes

### `App\Models\Business`
- Added `ListingType` import.
- Added `listing_type` to `$fillable`.
- Added `listing_type` to `$casts` → `App\Support\ListingType` (enum cast).
- Added boot hook: on `creating`, if `listing_type` is empty, default it to
  `ListingType::BUSINESS`. This makes the *model* the source of truth for the
  default while the DB column stays future-proof.
- Added `scopeOfType(ListingType $type)` for type-filtered queries.
- Added a **Listing abstraction surface** (non-breaking accessors):
  - `getListingType()` → returns the enum (falls back to BUSINESS),
  - `isListingType(ListingType $type): bool`,
  - `getListingDisplayName()` → type-aware display name,
  - `listingOwner()` → alias of `owner()` expressed in Listing terms.
- Documented the Business↔Listing relationship in class-level comments.

### `App\Models\User`
- Added `listings()` relationship — the canonical **Account → Listings** edge.
  Implemented as `hasMany(Business::class, 'owner_id')`. This is the
  architecture-level relationship Phase 2 §9 requires, expressed without a new
  table.
- `businesses()` is retained unchanged (back-compat / existing call sites).

### `App\Models\Branch`
- Added class-level documentation of the **branch→listing evolution path**
  (no behavioural change). Branch remains a location of a Business listing.

> No existing relationship, scope, accessor, or method was removed or renamed.

---

## D. Ownership Model

```
                    ┌─────────────────────────────┐
                    │        ACCOUNT (User)         │
                    │  role: user | owner | admin   │
                    └──────────────┬────────────────┘
                                   │
              account-scoped       │        one-to-many (owner_id)
            ┌──────────────────────┼──────────────────────────┐
            ▼                       ▼                          ▼
     ┌─────────────┐        ┌───────────────┐         ┌───────────────┐
     │ SUBSCRIPTION│        │  Listing A     │         │  Listing B     │
     │  (user_id)  │        │ (Business,     │         │ (Business,     │
     └──────┬──────┘        │  type=business)│         │  type=business)│
            │               └───────────────┘         └───────────────┘
            ▼
     ┌─────────────┐
     │ENTITLEMENTS │  ── consumed by ALL listings of the account ──►
     └─────────────┘
```

### Authorization (unchanged, preserved)
- **Ownership:** `Business.owner_id → User.id`. One account owns many listings.
- **Edit authority:** `Business::canBeEditedBy($user)` → `$user->isAdmin() || $user->id === $this->owner_id`.
  - Admin/super_admin bypass preserved.
  - A stranger owner is rejected — verified by tests.
- **Creation gate:** account-level entitlements via
  `EntitlementService::canCreate($user, ListingType::BUSINESS)` and the
  existing `plan.limit:businesses` middleware.

### Ownership ≠ Management
The model keeps ownership (`owner_id`) and management separable. `listingOwner()`
names the ownership edge in Listing terms; a future `ListingManager` entity
(not built in Phase 2) can attach to a listing without disturbing ownership.

---

## E. Listing Type Model

`App\Support\ListingType` is the type axis:

| Case | value | creation entitlement | implemented |
|---|---|---|---|
| `BUSINESS` | `business` | `businesses` | ✅ yes |
| `PROFESSIONAL` | `professional` | `professionals` | ⏳ no (design-only) |
| `STORE` | `store` | `stores` | ⏳ no (design-only) |

- `listing_type` is stored as a **string column**, cast to the enum on the
  model. Invalid values are tolerated at the DB layer but the cast returns the
  enum; the model defaults empties to `BUSINESS`.
- No business-only assumption is hard-coded: quotas, features and type
  resolution all route through `ListingType` + `Entitlement`.
- **No fake Professional/Store functionality** exists anywhere.

---

## F. Business / Branch Migration

### What happens in Phase 2
- Business keeps its table, columns, relationships, routes, and behaviour.
- Business gains a `listing_type` axis (backfilled to `business`).
- Account→listings ownership is expressed (`User::listings()`).
- The branch→listing evolution path is **documented**, not executed.

### The SBKRAFT example (conceptual target, NOT executed)

```
BEFORE
  Business: SBKRAFT
    ├── Branch: Buea   (primary)
    └── Branch: Limbe

AFTER (future phase)
  Account: SBKRAFT owner
    ├── Listing: SBKRAFT Buea   type=business   ← from Branch Buea
    └── Listing: SBKRAFT Limbe  type=business   ← from Branch Limbe
       (optionally grouped under a future Brand/Organization — NOT built)
```

### Exactly how (future, safe) — the documented path
1. Each `Branch` would gain an additive `listing_id` (nullable FK) OR be
   promoted via a new listing row that references the source branch for trace.
2. Business-level children that are genuinely location-specific
   (hours, overrides) already key on `branch_id` and move naturally.
3. Business-level children that are shared today (services, reviews, contacts,
   images, coupons, analytics) require a **copy-then-verify** step per branch —
   which is inherently a product decision (which branch "owns" a shared review)
   and therefore **out of scope** for Phase 2. No data will be moved without
   that explicit decision.

**No data loss path:** the migration is additive; the source Business and
Branches remain intact and queryable throughout.

---

## G. Data Mapping (existing → future)

| Existing entity / table | Relationship today | Future Listing meaning | Phase 2 action |
|---|---|---|---|
| `users` | Account | **Account** | unchanged |
| `businesses` | owned by user | **Listing (type=business)** | + `listing_type` |
| `branches` | under business | **Location** of a listing (→ future independent listing) | documented only |
| `business_services` | business_id | Listing-related (Service) | unchanged |
| `business_contacts` | business_id | Listing-related (Contact) | unchanged |
| `business_categories` | business_id | Listing-related (Category) | unchanged |
| `business_images` | business_id | Listing-related (Media) | unchanged |
| `business_hours` | **branch_id** | Location-related (Hours) | unchanged |
| `branch_hour_overrides` | **branch_id** | Location-related (Special hours) | unchanged |
| `reviews` / `review_replies` | business_id | Listing-related | unchanged |
| `leads` | business_id (+optional branch) | Listing-related | unchanged |
| `coupons` / redemptions | business_id | Listing-related | unchanged |
| `business_analytics` | business_id | Listing-related | unchanged |
| `favorites` | business_id | Listing-related | unchanged |
| `subscriptions` | **user_id** (account-scoped) | Account entitlement source | unchanged |
| `plans` | — | Account entitlement source | unchanged |

---

## H. Search Impact

- **No change** to `Business::toSearchableArray()` field set in Phase 2.
- **No change** to the Meilisearch index name (`businesses`), Scout config,
  autocomplete, or intent parsing.
- **Deferred (documented):** when Professional/Store ship, `toSearchableArray()`
  gains a `listing_type` field so results segment by type. The architecture is
  compatible because the type axis now exists.

---

## I. URL Compatibility

- **No URL changes.** The public routes are untouched:
  `/directory`, `/business/{slug}`, `/categories`, `/locations`,
  `/search`, `/search/autocomplete`, `/{category}-in-{city}`, `/explore`.
- `business.show` still resolves by `slug` on the Business (the listing).
- Owner routes (`/owner/businesses/...`, `.../branches/...`) are unchanged.
- **Future strategy:** when listings become first-class, keep
  `/business/{slug}` as a long-lived route (301 if ever renamed) and add
  type-specific routes additively. No SEO regression.

---

## J. Tests

Added/changed in Phase 2:

| File | Tests | Covers | Result |
|---|---|---|---|
| `tests/Feature/Listing/ListingDomainTest.php` | 15 | Ownership (account owns many listings), isolation (listing owner cannot edit another's), entitlements (creation respects account entitlements), state/type independence, business compatibility, branch compatibility, authorization, data preservation, `ofType` scope | PASS |
| `tests/Unit/Support/ListingTypeTest.php` | 5 | type axis: business implemented; professional/store design-only; creation-entitlement mapping; labels; safe `fromStored`; DB/URL-safe values | PASS |

**Full suite: 139 passed, 300 assertions, 1 pre-existing incomplete, 0 failures.**
(Net +20 from Phase 1's 119.) Existing suites remain green (Business,
Branch/Hours, Search, Auth, Subscription, Architecture from Phase 1).

**Migration reversibility verified:** `migrate:rollback --step=1` drops the
column cleanly and `migrate` re-adds it cleanly on the dev database.

---

## K. Migration Safety

- **Additive only.** One nullable column + one index added; nothing removed
  or renamed.
- **Reversible.** `down()` drops index then column.
- **Backfill is deterministic & idempotent** (`WHERE listing_type IS NULL`).
- **No production records migrated** beyond setting a default label on the
  existing rows, which is a no-op for behaviour (the model already defaulted
  to BUSINESS).
- **No destructive change** to `businesses` or `branches`.
- Rollback restores the exact prior schema; no data is lost because the column
  was newly added and nothing depended on it before Phase 2.

---

## L. Deferred Work (NOT implemented)

- Professional & Store: public profiles, dashboards, catalogs, routes.
- Product catalogue / e-commerce (cart, checkout, orders, inventory).
- Physical branch→listing data migration (the risky child-data move).
- Brand/Organization grouping entity.
- Full team management (`ListingManager`).
- Multi-type search indexing (`listing_type` in the search payload).
- New homepage / design system / pricing UI.
- Removal of dead `app/Http/Kernel.php` / `CheckSubscriptionLimits`.

---

## M. Recommended Phase 3

The single highest-value, lowest-risk next step revealed by Phase 2:

**Phase 3 — Listing-Type-Aware Entitlements & Enforcement, then the first
branch→listing pilot.**

1. Wire `listing_type` into `EntitlementService::summary()` and
   `PlanEnforcementService::summary()` (currently a stub) so quota reporting is
   listing-type aware.
2. Add `listing_type` to `toSearchableArray()` (deferred field) — small,
   additive, unlocks segmentation.
3. Design (not yet execute) the **branch→listing pilot** on a *single*
   opt-in production business using copy-then-verify, behind a feature flag.
4. Revisit the "unlimited → limited is not a downgrade" rule (Phase 1 §5.D).

This keeps every step reversible and continues the incremental, data-preserving
strategy.
