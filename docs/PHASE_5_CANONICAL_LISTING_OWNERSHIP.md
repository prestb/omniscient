# Omniscient — Phase 5: Canonical Listing Ownership & Product Decisions

> Status: **Phase 5 complete** — DECISION & ARCHITECTURE ONLY.
> No migration executed. No production/staging data mutated. No duplicate
> Businesses. No new listings table. No URL/route changes. No engine built.
>
> Epistemic markers used throughout: **FACT** (verified in code/schema),
> **DECISION** (chosen in Phase 5), **ASSUMPTION** (to be confirmed),
> **OPEN** (cannot yet be decided).

---

## 1. Current Domain Model (verified)

```
users (Account)
  └─ businesses (owner_id → users.id)            ← the concrete Listing today
       ├─ listing_type = 'business'              (default set in model boot)
       ├─ slug (unique) → /business/{slug}
       ├─ branches (business_id → businesses.id)
       │    ├─ business_hours        (branch_id)   [weekly hours]
       │    └─ branch_hour_overrides (branch_id)   [special/date hours]
       ├─ business_categories (business_id, category_id) [pivot, is_primary]
       ├─ business_services   (business_id)
       ├─ business_contacts   (business_id)        [type, value, is_primary]
       ├─ business_images     (business_id)        [type ∈ logo|cover|gallery]
       ├─ business_analytics  (business_id, date)  [unique per business+date]
       ├─ reviews             (business_id)        ← NO branch column
       ├─ leads               (business_id, branch_id NULLABLE)
       ├─ coupons             (business_id, user_id)
       │    └─ coupon_redemptions (coupon_id)
       └─ favorites           (user_id, business_id) [unique]
```

**Discrepancy check vs Phase 4:** None material. Confirmed additionally:

- **FACT** — `businesses` also has nullable `email` and `website` columns
  (`Business` fillable), *in addition to* `BusinessContact` rows. So contacts
  are represented **both** directly (email/website on Business, phone/whatsapp
  on Branch) **and** as flexible typed rows (`BusinessContact`).
- **FACT** — "Verified" is **not** stored data; it is `feature_flags.verified_badge`,
  derived from the account plan (`ownerCanUseFeature`). It is account-derived.
- **FACT** — `businesses.slug` is `unique`; `generateUniqueSlug()` appends `-2`,
  `-3`, … on collision.
- **FACT** — Authorization is `Business::canBeEditedBy(User)` (`admin|super_admin`
  OR `owner_id === user.id`). Only a `UserPolicy` exists; there is no BusinessPolicy.
- **FACT** — `Subscription` is account-scoped (`user_id`) with a legacy
  `business_id` link; `User::activeSubscription()` is the canonical accessor.

---

## 2. Target Domain Model

```
ACCOUNT (User)
  └─ LISTINGS
       ├── Listing: SBKRAFT Buea    (identity · location · hours · services
       │                             · categories · gallery · reviews
       │                             · analytics · leads · coupons · visibility)
       └── Listing: SBKRAFT Limbe   (same independent structure)
```

A future Brand/Organization may group listings — **out of scope**.

---

## 3. Parent Identity Decision (DECISION — Model B)

**DECISION — Model B.** The legacy Business becomes a **canonical parent/legacy
identity**; Listings become **independent public operational entities**. The
`businesses` row is **not** deleted and `businesses.id` / `businesses.slug`
remain stable.

**Alternatives considered:**
- **Model A** (Business disappears, each Branch becomes a listing) — *rejected*:
  it breaks id/slug/URL/favorite continuity and orphans historical records that
  FK on `business_id`.
- **Model C** (new listings table) — *rejected*: unjustified by the current
  architecture and explicitly out of scope.

**Consequences (DECISION):**

| Area | Consequence |
|---|---|
| Reviews | Stay on the parent unless branch evidence exists; never duplicated. |
| Favorites | Keep resolving via stable parent id/slug. |
| URLs | `/business/{slug}` keeps resolving to the parent; new listings get new slugs. |
| Analytics | Historical metrics stay a parent baseline; listings start fresh. |
| Search | Parent + listings both indexable; no forced merge. |
| Historical records | Legacy `business_id` FKs remain valid permanently. |
| Ownership | Parent + listings stay on the same Account. |
| SEO | Existing indexed URLs stay valid; no destructive rewrite. |
| External links | Third-party `/business/{slug}` links keep working. |

---

## 4. Review Ownership Policy

**FACT:** `reviews.business_id` only; **no `branch_id`** exists. The system
cannot reliably attribute an old review to a branch.

**DECISION:**
- **Historical review, no branch evidence** → remains attached to the
  **parent/legacy identity**. Never auto-split.
- **Historical review, branch evidence exists** (e.g. a future populated
  `branch_id`) → *may* be attributed to that listing. Absent evidence, no.
- **New reviews after migration** → attach to the listing whose profile
  generated them.
- **Legacy URLs** → review routes bind by stable ids (`business/{business}/reviews`,
  `admin/reviews/{id}`); they keep working.
- **Reputation** → each listing's rating is computed **only from reviews actually
  attributed to it**; no review is copied, so no inflation.

**HARD RULE:** one historical review must never become multiple active listing
reviews.

**Rationale:** duplicating a review inflates reputation and violates the meaning
of a review.

**OPEN:** whether a `reviews.branch_id` will ever be added and backfilled.

---

## 5. Service Ownership Policy

**FACT:** `business_services.business_id` only — services are business-wide.

**DECISION:**
- **Business-wide service** → **not** auto-available on every listing.
- **Branch-specific service** → only if explicit future evidence exists.
- **Ambiguous service** → stays parent-level pending explicit assignment.
- **Future creation** → services belong directly to Listings
  (`Listing → Services`), without Branch semantics.
- Never duplicated merely because there are multiple branches.

**OPEN:** do services ever become per-location, and by what UI?

---

## 6. Category Policy

**FACT:** `business_categories` is a `business_id↔category_id` pivot
(`is_primary`, unique pair). Categories describe business identity/discovery.

**DECISION:**
- **Existing category** → retained on the parent; **not** blindly duplicated.
- **Different branch specializations** → the target model allows categories
  **per Listing**, so Buea and Limbe may legitimately differ.
- **Future assignment** → categories belong directly to Listings.
- The policy must **not** make every location identical.

---

## 7. Contact Policy

**FACT (verified):** contacts exist in **two** forms:
1. Direct columns — `businesses.email`, `businesses.website`; and
   `branches.phone`, `branches.whatsapp`.
2. Flexible typed rows — `business_contacts(business_id, type, value, is_primary)`.

**DECISION — target contact model is listing-owned:**
```
Listing
  ├── primary phone
  ├── WhatsApp
  ├── email
  ├── website
  └── social links
```
Business-level contacts stay on the parent; per-location phone/whatsapp already
live on the branch and move with it. **A contact belonging to Buea must never
silently become the contact for Limbe.**

**OPEN:** whether shared social handles are duplicated or kept parent-level.

---

## 8. Media Policy

**FACT:** `business_images(business_id, type ∈ {logo, cover, gallery})`; paths
are public storage URLs.

**DECISION:**
- **logo / cover** → **brand assets** (parent identity).
- **gallery / portfolio** → may be **location-specific**.
- **branch-specific photographs** → move with the branch listing.
- Target model allows per-listing `logo + cover + gallery` **without**
  duplicating physical files (a listing may reference the same file).
- **Do not move files; do not change public URLs in this phase.**

**OPEN:** which gallery images belong to which location.

---

## 9. Lead Policy

**FACT:** `leads(business_id, branch_id NULLABLE)`.

**DECISION:**
- **Lead with `branch_id`** → becomes a historical lead of that listing.
- **Lead without `branch_id`** → stays historical/ambiguous on the parent,
  unless another reliable attribution mechanism exists.
- **Never duplicated** across listings.
- **Future leads** → attach directly to a Listing (`Lead → Listing`).

**OPEN:** disposition of NULL-branch historical leads on a multi-branch business.

---

## 10. Analytics Policy

**FACT:** `business_analytics` keyed by `(business_id, date)`, unique.

**DECISION:**
- **Historical parent analytics** = metrics accumulated under the old Business
  identity → **preserved as a parent baseline**.
- **Future listing analytics** = metrics generated after independence → **start
  at migration time**.
- **Do not fabricate** historical per-location metrics.
- A **combined historical view** may be exposed **only** labelled as an
  aggregate — never implying per-location history is known.

**OPEN:** whether the baseline is shown on the parent only or aggregated.

---

## 11. Favorite Policy (CRITICAL)

**FACT:** `favorites(user_id, business_id)` unique.

**Problem:** a stable Business id does **not** tell us whether the user intended
Buea, Limbe, or the whole brand.

**DECISION:**
- **Existing favorite, no location evidence** → keeps pointing at the **stable
  parent id/slug**, remains meaningful as a **legacy/parent favorite**.
- **Existing favorite, reliable location evidence** → *may* follow a specific
  listing (only with evidence; none exists today).
- **New favorites** → reference the **independent Listing identity**.
- **Legacy parent favorites** → remain visible as an aggregate/parent favorite.

**HARD RULE:** never copy one favorite into multiple listings merely to preserve
a count. The UX must stay semantically honest.

**OPEN:** whether to *offer* users a choice of a specific location (never a
silent rewrite).

---

## 12. Coupon Policy

**FACT:** `coupons(business_id, user_id)`; `coupon_redemptions(coupon_id)`.

**DECISION:**
- **Business-level coupon** → stays parent-level unless the business explicitly
  intends separate listing offers.
- **Branch-specific coupon** → attributed only with explicit evidence.
- **Expired coupon** → retained (status derives from `is_active`/dates).
- **Redemption history** → **immutable audit trail**; never duplicated/reassigned.
- **Future coupons** → may belong directly to Listings.
- Avoid duplicate active coupons.

**OPEN:** whether branch-limited coupons become a first-class concept.

---

## 13. Hours Policy

**FACT:** `business_hours` and `branch_hour_overrides` both key on `branch_id`;
`Branch` exposes `hours()` / `hourOverrides()`.

**DECISION — target model:**
```
Listing
  ├── Hours (weekly)
  └── Special hours / overrides (dated)
```
- **No branches** → a Business with no branch keeps its own listing with no hours.
- **One branch** → that branch's hours become the listing's hours.
- **Multiple branches** → each branch's hours move with its listing.
- **Incomplete hours** → carried as-is; **never invented**.

This is the clearest, lowest-ambiguity migration candidate — but is **not**
implemented in Phase 5.

---

## 14. URL & Slug Policy

**FACT:** public route `business.show` = `business/{slug}`, resolved by
`slug`; `slug` is unique; `generateUniqueSlug()` appends `-2`, `-3`, ….

**DECISION (no routes/redirects changed in this phase):**
- **Legacy Business URL** → `/business/{legacy-slug}` keeps resolving to the
  parent (or, later, 301s to a chosen canonical listing).
- **New Listing URL** → future structure to be defined; listings are independent.
- **Existing slug** → remains on the parent, stable.
- **New listing slug** → generated from the listing name (+ location qualifier),
  unique via the existing collision strategy.
- **Redirect** → appropriate when a legacy slug should point at a specific
  listing; never applied to the parent by default.
- **Collision** → Buea/Limbe slugs must differ; the existing `-N` suffix
  guarantees uniqueness.

**OPEN:** whether the legacy slug 301s to the primary listing or renders a
multi-location page.

---

## 15. Ownership & Management Policy

**FACT:** `Account → businesses` via `owner_id`; authorization is
`Business::canBeEditedBy(User)` (admin/super_admin OR owner).

**DECISION — roles:**
- **OWNER** — the Account that owns the listing (`owner_id`). Defined now.
- **MANAGER** — may manage one listing **without** owning the Account.
  *Conceptual only — NOT implemented.*
- **COLLABORATOR** — future narrower scope (e.g. content/edits).
  *Conceptual only — NOT implemented.*
- **Current owner authorization remains unchanged** until a later phase.

**DEFERRED:** ListingManager UI, invitations-to-listing, role tables.

---

## 16. Quota Policy (transitional)

**FACT:** `Business` is the only concrete listing today; `max_businesses` acts
as the effective listing quota. `listing_limit` and allowed listing types are
**separate** (Phase 3).

**DECISION:**
- `max_businesses` **remains the current compatibility source** of listing quota.
- The architecture must remain capable of representing
  `5 listings = 2 Business + 2 Professional + 1 Store` **without** redesigning
  subscriptions.
- **Do not** rename DB fields; **do not** change pricing in this phase.

**OPEN:** when/if `max_businesses` is replaced by a type-agnostic listing limit.

---

## 17. Migration Readiness Criteria

**READY** now means (all must hold):

1. Every source record has a **defined destination policy** (no unresolved
   ambiguity — see §18).
2. No ambiguity can **silently create incorrect data**.
3. **Identifier continuity** is understood (stable parent id/slug).
4. **URL continuity** is understood (legacy URLs resolve/redirect per policy).
5. **Ownership** is preserved (listings stay on the correct Account).
6. **Reviews** cannot be duplicated.
7. **Leads** cannot be duplicated.
8. **Analytics** are not fabricated.
9. **Favorites** have a defined policy.
10. **Coupons** remain auditable (redemptions immutable).
11. **Rollback/reversibility** is defined.

A business with **unresolved policy questions** remains **NOT_READY**.

**Phase 5 change:** entities that were *ambiguous* in Phase 4 but now have a
canonical policy (reviews, services, categories, images, contacts, leads,
coupons, favorites, analytics) are **resolved ambiguities** — they no longer
block. Only entities **lacking a policy** block. This is enforced in
`MigrationReadinessService` (resolved ambiguities are reported separately).

---

## 18. Remaining Open Questions

| # | Open question | Marker |
|---|---|---|
| 1 | Will `reviews.branch_id` be added and backfilled? | OPEN |
| 2 | Do services ever become per-location, and via what UI? | OPEN |
| 3 | Shared social handles: duplicate or keep parent-level? | OPEN |
| 4 | Disposition of NULL-branch historical leads? | OPEN |
| 5 | Analytics baseline: parent-only or labelled aggregate? | OPEN |
| 6 | Offer users a location choice for legacy favorites? | OPEN |
| 7 | Branch-limited coupons as a first-class concept? | OPEN |
| 8 | Legacy slug → 301 to primary listing, or multi-location page? | OPEN |
| 9 | When is `max_branches` retired / folded into listing limit? | OPEN |
| 10 | Final new-listing URL structure? | OPEN |

These are **product** decisions, not engineering blockers. Each is isolated so
it can be decided without touching the others.

---

## 19. Assumptions (to confirm)

- **ASSUMPTION** — a future migration will keep `businesses` rows (additive,
  non-destructive), so legacy FKs stay valid.
- **ASSUMPTION** — new listings will be stored in a way that does not require
  changing the existing `businesses` columns (design deferred).
- **ASSUMPTION** — no historical data carries hidden location metadata beyond
  `leads.branch_id`; if it does, the relevant policies can be enriched.

---

## 20. Deferred Implementation Work

Actual Branch → Listing migration · staging mutation · production migration ·
Professional UI · Store UI · product catalogue · e-commerce ·
Brand/Organization · ListingManager implementation · new public listing routes ·
major search redesign · homepage redesign · design system · pricing changes ·
plan renaming · removal of `max_branches`.


