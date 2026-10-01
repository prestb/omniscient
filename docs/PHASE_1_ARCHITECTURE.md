# Omniscient — Phase 1 Architecture

> Status: **Phase 1 complete** — canonical architecture established.
> This document is the authoritative reference for how the existing
> application (USER → BUSINESS → BRANCH) will safely evolve toward the
> target model (ACCOUNT → SUBSCRIPTION → ENTITLEMENTS → LISTINGS).
>
> Nothing in Phase 1 is a destructive change. Public URLs, routes,
> production data, and existing functionality are all preserved.

---

## 1. Canonical model (target)

```
ACCOUNT
   │
   ▼
SUBSCRIPTION  ──►  PLAN  ──►  ENTITLEMENTS (features + quotas)
   │
   ▼
LISTINGS
   ├── Professional   (future)
   ├── Business       (implemented today)
   └── Store          (future)
```

The **current** physical model is still:

```
USER → BUSINESS → BRANCH
```

This is acceptable. Phase 1 defines the **evolution path**, not the
destination implementation.

---

## 2. Canonical account model

- **One** identity entity: `User` (the ACCOUNT).
- There are **no** separate permanent account entities for
  Individual / Business / Professional / Store.
- A single account can browse, search, favorite, review, subscribe,
  and own/manage listings.
- The existing `user → owner` **capability** is preserved — it is a
  **role transition on the same account**, never a second account.

### Canonical state transition

```
VISITOR
  │ register
  ▼
USER ACCOUNT   (role = user)      ← can browse / favorite / review / subscribe
  │ becomes a listing owner (same row; role = owner, or creates a listing)
  ▼
LISTING OWNER  (role = owner)
  │
  ▼
ONE OR MORE LISTINGS
```

`role` values: `user`, `owner`, `admin`, `super_admin`
(see `App\Models\User::ROLE_*`).

---

## 3. Canonical subscription model

- The **canonical owner of a subscription is the ACCOUNT**:
  `Subscription.user_id`.
- `Subscription.business_id` is **legacy compatibility data** (recorded,
  not authoritative). Repository evidence confirms every limit/feature
  check resolves through `user_id` (see `User::active_subscription`,
  `HasPlanFeatures`, `EntitlementService`).
- There is **no** subscription-per-listing architecture. One account has
  one active subscription that governs all its listings.

### A. Canonical subscription access pattern

**Chosen canonical accessor:** `$user->active_subscription`
(the memoized attribute on `App\Models\User`).

It is preferred over the camelCase *relation* `$user->activeSubscription`
because it:
- includes `grace_period` in the "active" window,
- memoizes per model instance (avoids N+1),
- is exposed once via `App\Services\SubscriptionService::activeFor()`.

> Both accessors resolve to the same data today. New code must use
> `SubscriptionService::activeFor()` or `$user->active_subscription`.

### B. Legacy / compatibility paths

| Path | Status | Action |
|---|---|---|
| `app/Http/Kernel.php` | **Dead** — Laravel 11+ uses `bootstrap/app.php`; aliases are declared there. | Leave in place (harmless) / remove in a later cleanup. Documented only. |
| `App\Http\Middleware\CheckSubscriptionLimits` | **Not registered** in `bootstrap/app.php`; superseded by `plan.limit:*` + `CheckPlanLimit`. | Marked deprecated; do not use for new routes. |
| `Subscription.business_id` | **Legacy** — retained for historical rows + admin views. | Keep; never use for authorization/limits. |
| `Subscription::canCreateBusiness()` reading `$this->business->owner_id` | **Legacy** — bypassed by user-scoped checks. | Keep for compatibility; not the canonical path. |

No legacy path was deleted blindly. Each was inspected for dependents.

### C. SubscriptionService

`App\Services\SubscriptionService` now owns the subscription **domain rules**
extracted from `Owner\SubscriptionController`:

- canonical retrieval (`activeFor`)
- downgrade detection (`isDowngrade`)
- proration + credit carry-over (`prorationForChange`)
- grace windows (`downgradeGraceDate`, `graceActiveFor`)

The controller now *coordinates* (validation, persistence, payment redirect)
and delegates every computation. This is behavior-preserving — the full
existing test suite still passes.

---

## 4. Entitlement architecture

**Principle:** application code asks *questions*, never inspects plan names.

```
❌ if ($user->plan === 'premium')
✅ $entitlements->canUse($user, Entitlement::ADVANCED_ANALYTICS)
✅ $entitlements->canCreate($user, ListingType::BUSINESS)
✅ $entitlements->remaining($user, Entitlement::CREATE_BUSINESS)
```

### Responsibility model

| Layer | Responsibility | Implementation |
|---|---|---|
| **Entitlement** | *Is this account allowed to do X?* | `EntitlementService` (`canUse`, `canAdd`, `canCreate`) |
| **Usage** | *How much of X is used?* | `EntitlementService` (`usage`, `remaining`, `limit`, `summary`) |
| **Enforcement** | *What happens on exceed?* | `CheckPlanFeature` / `CheckPlanLimit` middleware + `PlanEnforcementService` |
| **Controller/UI** | *Communicate the result.* | Controllers / Inertia props |

The names live in `App\Support\Entitlement` (constants) so they can never
drift between layers. `EntitlementService` delegates to the existing
`HasPlanFeatures` trait, so **behaviour is unchanged**; it adds a stable,
injectable façade + a listing-type-aware entry point.

---

## 5. Plan overflow / downgrade behaviour (data safety)

**Invariant:** *subscription changes must NEVER destroy user data.*

```
Listing A  Listing B  Listing C   (3 listings, plan allows 3)
        │ downgrade to a plan allowing 1
        ▼
Listing A  Listing B  Listing C   (all 3 STILL EXIST)
```

- Surplus listings remain in the database.
- They become **over-quota** during the grace window, then **hidden**
  (via the existing `businesses.hidden_at` column) — never deleted.
- Hidden listings are excluded from the public directory but remain
  visible/deletable by the owner, and are restorable by upgrading.

### Two orthogonal axes

Phase 1 documents the target state model (see `App\Support\ListingState`):

1. **Lifecycle/moderation** (already implemented): `draft → submitted →
   approved → published → (rejected/suspended)` + owner `inactive`
   (`Business::STATUS_*`).
2. **Quota/entitlement** (partially implemented via `hidden_at`):
   `active · inactive · over_quota · hidden · pending_reactivation`.

A listing is publicly visible only when **both** axes allow it
(`ListingState::isPubliclyVisible()`).

> **Deferred to Phase 2:** promoting `hidden_at`-based quota enforcement to a
> first-class listing status column.

---

## 6. Listing domain design

A **Listing** = a public, discoverable presence owned by an account.

Initial types (see `App\Support\ListingType`):
`BUSINESS` (implemented), `PROFESSIONAL`, `STORE` (design-only).

### Common listing capabilities (target)

Name · Slug · Description · Listing type · Owner · Status · Visibility ·
Location · Coordinates · Phone · WhatsApp · Email · Website · Social links ·
Logo · Cover · Gallery · Categories · Services · Reviews · Ratings ·
Verification · Favorites · Leads · Analytics · Search indexing · SEO metadata.

**Field disposition rule (Phase 1):** do **not** blindly add columns. Each
field is classified as: *common listing data* · *type-specific data* ·
*related entity* · *reuse of existing data* · *future functionality*.
The existing `Business` already covers most common fields; the rest are
already related entities (`services`, `images`, `contacts`, `hours`,
`reviews`, `leads`, `coupons`, `favorites`).

### Critical design decision — Option C (recommended)

Four options were weighed against repository evidence:

| Option | Verdict |
|---|---|
| **A. Rename/refactor `Business` → `Listing`** | Rejected. High risk: touches URLs, routes, tests, search, relationships for no immediate gain. |
| **B. Introduce `Listing` as a new entity, migrate `Business` in** | Rejected for now. Creates a parallel table + dual-write complexity before a second type even exists. |
| **C. `Business` is the first implementation of an extensible Listing architecture; add Professional/Store via a type system** | ✅ **Recommended.** Lowest risk, backward-compatible, preserves all URLs/data/tests, and the type/entitlement scaffolding (`ListingType`, `Entitlement`, `ListingState`) is already in place. |
| **D. Other** | No repository evidence justified a different path. |

**Option C** keeps `businesses` as-is, treats it as the `BUSINESS` listing
type, and will add Professional/Store as additional types backed by
**specialized metadata + related tables** — not three independent systems.

---

## 7. Business + Branch migration strategy (design only — not executed)

- **No migration is performed in Phase 1.**
- `BUSINESS → BRANCH` is retained as-is.
- The future model is `ACCOUNT → LISTINGS`, optionally grouped by an
  optional future **Brand/Organization** (not built).

### What maps where

| Current | Future Listing meaning |
|---|---|
| `Business` | The common Listing core (type = `business`). |
| `Business` name/desc/logo/cover/email/website/status | Common Listing fields (unchanged). |
| `Branch` | A **location** of a Business listing. Remain as related entities; may later become `locations` shared across listing types. |
| `BusinessService` | Related entity — already shared shape (supports the Skill/Service distinction, §9). |
| `Category` (pivot `business_categories`) | Related entity — reusable taxonomy across all listing types. |
| `BusinessImage` / `BusinessContact` / `BusinessHour`(+overrides) | Related entities — reusable media/contact/hours layer. |
| `Review`, `Lead`, `Coupon`, `Favorite`, `BusinessAnalytics` | Related entities — already business-scoped; will become listing-scoped via a polymorphic or FK swap. |
| `Subscription` | **Account-scoped**, not listing-scoped (canonical decision). |

### Referencing models/tables (complete audit)

Every model referencing `Business`/`Branch`: `Branch`, `BusinessService`,
`BusinessImage`, `BusinessContact`, `BusinessHour`(+`HourOverride`),
`Review`(+`ReviewReply`), `Lead`, `Coupon`(+redemption/token),
`Favorite`, `BusinessAnalytics`, `Subscription` (legacy `business_id`),
`Notification` (payload `business_id`), `Invitation` (if business-scoped).
The future FK strategy (polymorphic `listable` vs. renamed FK) is a Phase 2
decision; Phase 1 only enumerates the surface.

---

## 8. Professional architecture (design only)

A Professional listing will eventually need: profile photo, professional
title, headline, biography, location, service area, **skills**, **services**,
portfolio, experience, certifications, education, availability, reviews,
verification, contact, social links.

### Skill vs. Service (important distinction)

| Concept | Meaning | Example |
|---|---|---|
| **Skill** | Something the person *can do*. | Photoshop, Laravel, Photography |
| **Service** | Something the person *offers to customers*. | Logo Design, Website Development, Wedding Photography |

**Decision:** the existing `BusinessService` model already models
*offerings*; it can serve as the canonical **Service** entity for both
Business and Professional listings. **Skills** are a *different* concept and
will be a new related entity (e.g. `skills` + pivot), **not** a duplicate of
the service architecture.

---

## 9. Business architecture (design only)

Maps almost entirely to what already exists: identity, logo, cover,
category, description, services, location, opening hours, special hours,
contacts, website, gallery, promotions/coupons, reviews, leads, analytics,
verification, team.

**Already implemented:** everything except *team* (future management layer,
§11) and *promotions* beyond coupons.

---

## 10. Store architecture (design only — no e-commerce)

A Store will eventually need: store identity, category, location, hours,
contacts, gallery, **products**, product categories, prices, availability,
offers, reviews, inquiries.

**Explicitly deferred (future capabilities, NOT built):** cart, checkout,
payments, inventory, orders, delivery.

---

## 11. Ownership & management (authorization model)

Two distinct concepts, to be kept separate:

- **Ownership** — who the account/listing relationship belongs to.
- **Management** — who is permitted to manage a listing.

```
ACCOUNT OWNER
   ├── Listing A
   │     └── Manager
   └── Listing B
         └── Manager
```

Target roles: `Owner`, `Manager`, `Collaborator`, `Admin`.

**Today:** ownership is enforced by `Business::canBeEditedBy()` and
`owner_id` checks (admin/super_admin bypass). Phase 1 does **not** build
team-management UI, but the entitlement `Entitlement::ADD_STAFF` is declared
so the authorization model can expand to managers/collaborators later.

---

## 12. Listing type extensibility

```
          COMMON LISTING CORE
       ┌──────────┼──────────┐
       ▼          ▼          ▼
   PROFESSIONAL BUSINESS   STORE
       │          │          │
       └──────────┼──────────┘
                  ▼
      Shared: services · reviews · locations · media
              analytics · ownership · search · verification
```

Implemented via the `ListingType` enum (`value` is DB/URL-safe). The
implementation pattern (type field vs. polymorphic) is decided **per
repository evidence**; Phase 1 chooses **type field + specialized metadata**
(C), avoiding premature polymorphism.

---

## 13. Search architecture (impact — not rebuilt in Phase 1)

Current: Laravel Scout + Meilisearch; `Business::toSearchableArray()` with
primary-branch (singular) and all-branch (plural) location fields; filters
by category/service/city/region; `SearchIntentParser`; autocomplete.

When Professional/Store ship, the search index must include a
`listing_type` field so results can be filtered/segmented by type, and the
common listing fields must be indexed from the shared core. **No change is
made in Phase 1.**

---

## 14. Routes & URL compatibility

**No public URL changes in Phase 1.**

| Route | Name | Status |
|---|---|---|
| `/directory` | `directory` | unchanged |
| `/business/{slug}` | `business.show` | unchanged |
| `/categories`, `/locations` | `categories`, `locations` | unchanged |
| `/{category}-in-{city}` | `collection.show` | unchanged |
| `/search`, `/search/autocomplete` | `search.*` | unchanged |
| `/explore` | `explore` | unchanged |

**Future strategy:** when Listing types become first-class, keep
`/business/{slug}` working (301/long-lived) and introduce type-specific
routes (e.g. `/professional/{slug}`) without breaking existing links.
Branch URLs, if ever introduced, remain nested/derived. Collections and
SEO pages keep their current `{category}-in-{city}` shape.

---

## 15. Data migration risks (remaining)

- FK strategy for polymorphic listings (Phase 2 decision) — medium.
- `business_id` legacy on `Subscription` — low (recorded only).
- `hidden_at` as quota state vs. a real status column — medium (Phase 2).
- `toSearchableArray` must gain `listing_type` before multi-type search.
- `Plan` tier hard-coding in `HasPlanFeatures::isAtLeast()` (free/starter/
  growth/premium) — low, but a candidate for config-driven tiers later.

---

## 16. Technical debt deferred (explicitly postponed)

- New homepage / design system / dashboard UI.
- Professional & Store frontends; product catalogue; e-commerce.
- Brand/Organization entity; full team management.
- `hidden_at` → listing-status column promotion.
- Removal of dead `app/Http/Kernel.php` + `CheckSubscriptionLimits`.
- Sitemap overhaul / SEO redesign / new marketing & pricing pages.
- Config-driven plan tiers (removing `isAtLeast()` literal map).

---

## 17. Recommended Phase 2

Based on what was discovered, **Phase 2 should promote quota enforcement to
a first-class, listing-type-aware state** — i.e.:

1. Add `listing_type` to `businesses` (default `business`) and backfill,
   **non-destructively**.
2. Index `listing_type` in `toSearchableArray()`.
3. Replace `hidden_at`-only enforcement with the `ListingState` model
   (introduce `quota_state` + keep `hidden_at` for compatibility).
4. Extend `PlanEnforcementService::summary()` (currently a stub) to use
   `EntitlementService` + `ListingState`.
5. Revisit the "unlimited → limited is not a downgrade" rule (§5.D) as an
   explicit product decision.

This keeps the change incremental, reversible, and backward-compatible.
