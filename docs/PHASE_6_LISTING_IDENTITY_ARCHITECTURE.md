# Omniscient — Phase 6: Target Listing Identity & Coexistence Architecture

> Status: **Phase 6 complete** — ARCHITECTURE & COEXISTENCE ONLY.
> No migration executed. No production/staging data mutated. No duplicate
> Businesses. No new listings table. No `parent_business_id` column. No route
> or URL changes. No migration engine.
>
> Epistemic markers: **FACT** (verified in code/schema) · **DECISION** (chosen
> in Phase 6) · **ASSUMPTION** (to confirm) · **OPEN** (cannot yet decide).

---

## 1. Current Architecture (FACT)

```
users (Account)
  └─ businesses (owner_id → users.id)       ← concrete Listing today
       ├─ listing_type = 'business'
       ├─ slug (unique) → /business/{slug}
       ├─ branches (business_id → businesses.id)   ← legacy location child
       │    ├─ business_hours        (branch_id)
       │    └─ branch_hour_overrides (branch_id)
       ├─ business_categories / business_services / business_contacts
       ├─ business_images / business_analytics
       ├─ reviews (business_id) / leads (business_id, branch_id NULL)
       ├─ coupons (business_id) → coupon_redemptions (coupon_id)
       └─ favorites (user_id, business_id)
```

**Verified facts that matter for this phase (FACT):**
- `Branch` is **already `Searchable`** with its own `toSearchableArray()`.
- `Branch` is **already location-complete**: `name`, `country_id`, `region_id`,
  `city_id`, `area_id`, `address`, `latitude`, `longitude`, `phone`,
  `whatsapp`, `status`, `sort_order`, `hidden_at`.
- `Branch` already owns `hours()` and `hourOverrides()` (both `branch_id`-keyed).
- The public profile (`DirectoryController::show`) **already renders branches
  per-location** (hours, coordinates, open-now, override awareness).
- The directory already filters/searches **through branches** (`whereHas('branches', …)`).
- `businesses.slug` is unique; `generateUniqueSlug()` appends `-2`, `-3`…
- Authorization is `Business::canBeEditedBy()`; only a `UserPolicy` exists.

---

## 2. Target Architecture (DECISION)

```
ACCOUNT (User)
  └─ CANONICAL PARENT  (existing Business — aggregate + legacy identity)
       ├─ LISTING: Buea   (existing Branch — independent location)
       └─ LISTING: Limbe  (existing Branch — independent location)
```

Parent and listing **coexist**: the parent keeps the canonical URL, historical
records, and account ownership; each listing is an independently-manageable
location. **No new table, no new parent column.**

---

## 3. Options Considered (Phase 6 §2)

| Option | Summary | Selected |
|---|---|---|
| **A** | Business remains both parent and listing | No |
| **B** | Business rows become the independent listings | No |
| **C** | Self-referential `parent_business_id` on `businesses` | No |
| **D** | New polymorphic `listings` table | No |
| **E** | **Reuse the existing `Branch` as the listing anchor** | **Yes** |

### Option A — Business stays parent *and* listing
- **Strengths:** no schema change; Business already owns children.
- **Weaknesses:** semantic contradiction ("Business" meaning both a brand and a
  single location); parent + location both Businesses → *which is the parent?*;
  Branch becomes redundant (two location models); duplicate-identity risk.
- **Risks:** duplicate identity, ownership/subscription ambiguity, search duplication.

### Option B — Business rows become the listings
- **Strengths:** each listing is a first-class Business; all children work unchanged.
- **Weaknesses:** **duplicate identity** (legacy parent #50 vs location #101/#102
  describe the same brand); ownership/subscription/quota ambiguity (children
  consume business slots); review/favorite/URL/search competition.
- **Risks:** duplicate identity, quota/entitlement inflation, SEO competition.

### Option C — `parent_business_id` self-reference
- **Strengths:** single table; explicit relationship.
- **Weaknesses:** adds a column **not yet required** (forbidden in this phase);
  high query blast radius (every Business query must learn parent/child); children
  still consume slots; recursive delete/archive semantics; search de-duplication.
- **Risks:** query complexity, quota inflation, recursive-delete risk.

### Option D — New `listings` table
- **Strengths:** clean common identity; best long-term normalization.
- **Weaknesses:** **Phase 5 Model B does not change the justification** — Business
  survives as *both* parent *and* business listing, so a parallel `listings` table
  would duplicate identity; **dual-read/dual-write** risk; every FK (reviews,
  favorites, analytics, leads, coupons, contacts, media) must be re-pointed;
  the highest migration surface for the least present benefit.
- **Verdict:** **still unjustified.** What would justify it later is a *third and
  fourth* listing type with substantially different metadata **and** a demonstrated
  need for cross-type identity — neither exists today.

### Option E — Reuse `Branch` as the listing anchor (SELECTED)
- **Strengths:** `Branch` is already location-complete and already `Searchable`;
  the public profile already renders per-location; **no new table, no new column**;
  parent/listing split is natural (Business = parent, Branch = listing); the
  smallest migration blast radius of all options; extends to Professional/Store
  (each future type is another *parent* listing type with the same Branch child).
- **Weaknesses:** Branch children today are limited to hours; services/contacts/
  media/reviews still hang off the parent and need explicit per-listing ownership
  in a LATER phase; Branch has no `slug` yet (URL strategy defined, not built).
- **Risks:** promoting Branch to public listing needs explicit visibility rules
  (it already has `status` + `hidden_at`, which helps); shared children are not
  location-scoped yet in the schema.

---

## 4. Canonical Parent — Precise Definition (DECISION)

The canonical parent is **definition B: a user-facing, discoverable aggregate**,
which **also** retains a **technical legacy-identity facet** (definition A) for
continuity. It is **not** definition C (not a temporary throwaway).

- **Entity:** `Business` (unchanged).
- It anchors `/business/{slug}`, aggregate search discovery, historical reviews,
  the analytics baseline, the parent slug (SEO/external links), and ownership.
- It stays user-visible: a listing's page can link "part of {parent}".

Its concrete consequences are encoded in `ListingIdentity::canonicalParent()`.

---

## 5. Independent Listing — Precise Definition (DECISION)

An independent listing is an **independently-manageable public location of a
parent**, anchored today by the **existing `Branch`**.

**Already independent (FACT, no schema change needed):**
public identity (`name`), location (country/region/city/area/address/lat/long),
contacts (`phone` + `whatsapp`), hours (`business_hours` +
`branch_hour_overrides`), status + visibility (`status`, `hidden_at`).

**Deferred (policy defined, NOT implemented):**
`slug`/URL, per-listing services, categories, gallery, post-migration reviews,
per-listing analytics, coupons.

Encoded in `ListingIdentity::independentListing()`.

---

## 6. Parent vs Listing Ownership Matrix (DECISION, from the actual schema)

| Property | Parent | Listing | Account | Note |
|---|---|---|---|---|
| Historical records | yes | no | no | Legacy rows stay on the parent. |
| Subscription | no | no | **yes** | Account-scoped (unchanged). |
| Ownership | yes | yes | **yes** | Account owns both. |
| Review (historical) | ✓ | — | — | No `branch_id`; never duplicated. |
| Review (new) | — | ✓ | — | Attaches to the listing. |
| Favorite (legacy) | ✓ | — | user | Never copied 1→N. |
| Favorite (new) | — | ✓ | user | References the listing. |
| Analytics (baseline) | ✓ | — | — | Never fabricated per-location. |
| Analytics (future) | — | ✓ | — | Starts at migration. |
| Services | ✓ (today) | deferred | — | Business-level. |
| Contacts | brand | operational | — | Branch phone/whatsapp are listing-level. |
| Hours | — | ✓ | — | Already branch-keyed. |
| Categories | ✓ (today) | deferred | — | Business-level. |
| Coupon | policy | policy | — | Policy-dependent (Phase 5). |
| Media | brand/legacy | operational | — | logo/cover = brand; gallery may be location. |
| Lead | historical | new | — | `branch_id` when present. |

Encoded in `ListingIdentity::ownershipMatrix()`.

---

## 7. URL Identity (DECISION)

- **Canonical owner:** the **parent** — `/business/{slug}` remains authoritative
  and **stays valid**.
- **Legacy URL** `/business/{slug}` resolves to the **parent aggregate**. It does
  **not** disappear. (Whether it 301s to a specific listing is **OPEN**.)
- **Listing URL (future, NOT implemented):** template
  `/business/{parent-slug}-{location-slug}` (e.g. `/business/sbkraft-buea`).
- **Collision:** generated listing slugs use the existing `-2`/`-3` strategy.
- **SEO:** the parent slug preserves indexed external links; listing slugs are
  **additive and new**.
- **No routes or redirects are changed in Phase 6.**

Encoded in `ListingIdentity::urlIdentity()`.

---

## 8. Favorite Identity (DECISION)

- **Legacy favorite** (`user_id, business_id`) → **stays a favorite of the
  canonical parent** and remains **user-visible** (the parent is user-facing §4).
- **New favorite** → references an **independent listing**.
- **With branch evidence** → may be associated with a specific listing.
- **Without branch evidence** → remains on the parent.
- **Never copied 1→N.** The parent stays user-visible, so legacy favorites keep
  their meaning without a silent rewrite.

Encoded in `ListingIdentity::favoriteIdentity()`.

---

## 9. Review Identity (DECISION)

- **Parent displays** the historical reviews it owns.
- **Listing displays only** reviews generated **after independence**.
- **Manual attribution** is permitted **only with explicit evidence**.
- **Totals are NOT inherited** and **reputation is NOT inherited** (no inflation):
  a listing cannot claim the parent's historical rating.

Encoded in `ListingIdentity::reviewIdentity()`.

---

## 10. Search Identity (DECISION)

- **Both parent and listing are indexed.**
- Parent is indexed **for aggregate discovery**; the listing for local discovery.
- A future index distinguishes them with an **identity discriminator**
  (`parent` vs `listing`), so the same real-world entity is **not** returned as
  two unrelated results unless intentionally designed.
- **FACT:** `Branch` is already `Searchable`; **no search redesign occurs in
  Phase 6.**

Encoded in `ListingIdentity::searchIdentity()`.

---

## 11. Ownership (DECISION)

```
Account
  └─ Parent (Business, owner_id)
       ├─ Listing A (Branch, inherits parent's account)
       └─ Listing B (Branch, inherits parent's account)
```

- **Ownership is Account-scoped** (`User.owner_id → Business`); a listing is owned
  **through** its parent, not independently. This is **owner inheritance** — the
  listing has no separate `owner_id` today and needs none.
- **MANAGER / COLLABORATOR remain conceptual only.** No UI, no role tables.
- **Current authorization is unchanged** (`Business::canBeEditedBy`).

## 12. Subscription & Quota (DECISION)

```
Account → Subscription → Entitlements → Listings
```

- Subscription is **Account-scoped** and stays so.
- `max_businesses` remains the **transitional** listing-quota source; `max_branches`
  is unchanged.
- The architecture supports, without new subscription systems:
  ```
  Account
    ├── Business listing
    ├── Professional listing
    └── Store listing
  ```
- **No pricing change, no plan rename, no removal of `max_businesses`.**

## 13. Professional / Store Compatibility (DECISION)

Because Phase 6 chooses **listing types as parent types** with a shared
Branch-as-location child, introducing Professional and Store later does **NOT**
require another ownership model:

```
Account
  ├─ Business listing      (parent Business + Branch locations)
  ├─ Professional listing  (parent of type 'professional' + same Branch child)
  └─ Store listing         (parent of type 'store' + same Branch child)
```

- A Professional with **one** location is simply a parent with a single Branch.
- The `ListingType` axis already exists; no new ownership system is needed.
- **No Professional/Store UI is built in Phase 6.**

## 14. Migration Implications (DECISION — not implemented)

A future migration would mean:

```
LEGACY BUSINESS  →  CANONICAL PARENT  →  INDEPENDENT LISTINGS
```

| Aspect | Definition |
|---|---|
| **Source identity** | the legacy `Business` (its id + slug + shared children). |
| **Destination identity** | the same Business as parent; each `Branch` becomes an independently-addressable listing. |
| **Relationship** | parent `Business` 1—* `Branch` (already exists — no new column). |
| **Records that remain historical** | reviews, the analytics baseline, legacy favorites, historical leads, legacy contacts/categories/services, digital-asset structure. |
| **Records that become listing-owned** | location, contacts (phone/whatsapp already on Branch), hours + overrides (already branch-keyed), leads with `branch_id`. |
| **Records that are regenerated** | per-listing slugs/URLs, per-listing analytics (fresh), post-migration reviews. |
| **Rollback requirements** | no destructive change is required to *represent* the model, so rollback is trivial in the representation phase; a later data-attribution step must be copy-then-verify and reversible. |

**No migration, no staging mutation, no engine is built in Phase 6.**

## 15. Readiness Terminology (DECISION)

Phase 5 used `READY`. Phase 6 refines it into four precise stages:

| Stage | Meaning |
|---|---|
| **POLICY_READY** | The domain rules are defined. **Phase 5 completed this.** |
| **STRUCTURE_READY** | The database/domain representation exists to hold the target model. |
| **MIGRATION_READY** | A specific Business can safely undergo migration. |
| **MIGRATION_VERIFIED** | A migrated result has passed verification. |

**No state machinery is built.** `MigrationReadinessService` maps the existing
`READY`/`NOT_READY` verdict onto this vocabulary: `READY → MIGRATION_READY`,
`NOT_READY → STRUCTURE_READY` (structure/policy work outstanding).
`MIGRATION_VERIFIED` can only be reached *after* a migration — so a read-only
service never returns it.

## 16. Risks

- **Promoting Branch to a public listing** needs explicit visibility rules;
  mitigated by the existing `status` + `hidden_at` columns.
- **Shared children not yet location-scoped** (services/reviews/media): a later
  phase must apply the Phase 5 policies with copy-then-verify.
- **Search duplication** of parent vs listing: mitigated by the planned identity
  discriminator; not implemented yet.
- **Slug/URL work** deferred: Branch has no slug; adding one is a later, additive
  step with no route change required to *represent* the model.

## 17. Deferred Implementation

Actual Branch → Listing migration · staging mutation · production migration ·
migration engine · new listings table · `parent_business_id` column · new public
listing routes · Professional UI · Store UI · product catalogue · e-commerce ·
Brand/Organization · ListingManager UI · search redesign · homepage redesign ·
design system · pricing changes · plan renaming · removal of `max_branches`.

