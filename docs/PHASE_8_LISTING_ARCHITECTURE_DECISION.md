# Omniscient — Phase 8: Listing Architecture Convergence

> Status: **DECISION + ARCHITECTURE.**
> No full rewrite. No data migration. No frontend/search/route redesign.
> This document establishes the *final* listing architecture for Omniscient.
>
> Markers: **FACT** · **DECISION** · **ASSUMPTION** · **OPEN** · **DEFERRED**.

---

## 0. Method & Governing Rule

Phase 8 is authorised to prefer the **cleanest final architecture** over preserving
experimental structure. There is **no valuable production data**, so backward
compatibility is **not** a primary constraint (FACT, per the phase brief).

However, complexity is still a cost. The winning architecture is the one that
gives Omniscient the **clearest domain model with the least unnecessary
complexity** — not the one that is most theoretically generic.

The central question is **not** "Branch or a listings table?" It is:

> **What is the actual thing a user discovers in Omniscient, and does that thing
> exist for Business, Professional, and Store alike?**

---

## 1. Current Architecture (FACT — verified from source)

### 1.1 Entities

| Concept | Current implementation | Notes |
|---|---|---|
| Account | **`User`** (`users`) — *there is no `Account` model* | `owner_id → users.id` |
| Organization / "Business" | **`Business`** (`businesses`) | owner_id, name, slug, listing_type, status, hidden_at |
| Location | **`Branch`** (`branches`) | business_id, geo, address, lat/long, phone, whatsapp, status, hidden_at, slug (nullable) |
| Listing | **DOES NOT EXIST as an entity** | `ListingType` enum exists; `Business.listing_type` column exists |
| Professional | Enum value only (`ListingType::PROFESSIONAL`) | not implemented |
| Store | Enum value only (`ListingType::STORE`) | not implemented |

### 1.2 What the user actually discovers (FACT — from controllers)

- `GET /search` → `SearchController@index` → **`Business::search()`** → returns
  `businesses` (paginated). Filters use the union of branch `city_ids`.
- `GET /directory` → `DirectoryController@index` → **`Business` query** → returns
  `businesses`.
- `GET /business/{slug}` → `DirectoryController@show` → **one `Business`**
  (branches folded into its profile).
- `GET /search/autocomplete` → returns **Business** suggestions + category
  suggestions.

**FACT: the canonical discoverable result is a `Business`.** A `Branch` is never
returned as a search result today, even though it is `Searchable`.

### 1.3 Children & their real owner

| Child | FK today | Scoped to |
|---|---|---|
| business_hours | `branch_id` | **Branch** |
| branch_hour_overrides | `branch_id` | **Branch** |
| business_categories (pivot) | `business_id` | Business |
| business_services | `business_id` (+ nullable `branch_id`, Phase 7) | Business |
| business_images | `business_id` (+ nullable `branch_id`) | Business |
| business_contacts | `business_id` (+ nullable `branch_id`) | Business |
| reviews | `business_id` (+ nullable `branch_id`) | Business |
| business_analytics | `business_id` (+ nullable `branch_id`) | Business |
| leads | `business_id` (+ nullable `branch_id`) | Business/Branch |
| coupons | `business_id` (+ nullable `branch_id`) | Business |
| favorites | `business_id` | Business |

### 1.4 Entitlements / ownership

- Account = `User`; subscription is `user_id`-scoped; quotas key on
  `max_businesses`, `max_branches`, `max_images`, etc.
- `User::listings()` and `User::businesses()` **both return `hasMany(Business)`**.
- **Listing count = count of `Business` rows** (`Business::listingsCountFor`).
- Authorization is `Business::canBeEditedBy()` (admin or owner). Branch has no
  policy (intentionally).

### 1.5 The Phase 6/7 legacy

- Phase 6 selected **Option E: reuse `Branch` as the future independent listing
  anchor**.
- Phase 7 added *optional, NULL-by-default* structure: `branch_id` on six child
  tables, a `branch_category` pivot, and `branches.slug`.

---

## 2. Problems With the Current Architecture (FACT-led)

1. **P1 — Branch-as-listing contradicts the discovery model.** The product
   already discovers `Business` (search, directory, profile). Promoting `Branch`
   to the listing anchor would make the discoverable entity *different* from the
   entity every controller is built around. FACT: Branch is `Searchable` but is
   *not* a search result.
2. **P2 — "Professional has no location."** A Professional (freelancer) has no
   physical branch. Forcing them into `Branch` is a domain lie (Phase 8 §6).
3. **P3 — Conflated identity.** "ABC Restaurant" (brand) vs "ABC Restaurant —
   Buea" (location). `businesses` currently holds the brand; `branches` holds the
   location. Neither is a *listing* in the Professional sense.
4. **P4 — `listing_type` on `Business` is a category error.** `Business` is an
   *organization*; `listing_type` describes a *discoverable entity*. A Business is
   not a "Store" or a "Professional".
5. **P5 — Branch already carries two roles** (location + Phase-7 listing anchor)
   with mostly-empty optional columns — half-built abstraction, no consumer.
6. **P6 — No place for a locationless listing.** Nothing models "Jane, remote
   developer, worldwide".
7. **P7 — Account is `User` with a `role`.** `role` (`user`/`owner`/`admin`)
   conflates *who you are* with *what you can do*; the brief requires an account
   that can own *different kinds* of listings without being classified.

---

## 3. Candidate Architectures

### OPTION A — Branch as Business Listing (Phase 6/7 continuation)

`Account(User) → Business (parent) → Branch = Listing`

- Discoverable entity = **Branch**.
- **Fatal flaw (FACT):** the entire codebase discovers **Business** (§1.2). This
  option requires moving discovery *from* Business *to* Branch — i.e. inverting
  the system — while Professional still has nothing to live on.
- Non-location listings (Professional) are unrepresentable.

### OPTION B — First-class `Listing` entity

`Account(User) → Listing (type = business|professional|store)`
`Business → Locations (Branches)`

- Discoverable entity = **Listing**.
- Business becomes an **organization/aggregate** that *owns* one or more listings.
- Professional = a Listing with no location. Store = a Listing with commerce
  metadata. Business = a Listing (optionally with Locations/Branches).
- Requires migrating children from `business_id` → `listing_id` (or keeping
  Business as the listing for the Business type).

### OPTION C — Hybrid: `Listing` as the universal discoverable entity, `Business` retained as an optional organization, `Branch` demoted to a pure `Location` child of a Listing. **(RECOMMENDED)**

This is Option B *executed as convergence*, with explicit demotion rather than
parallel systems. Detailed in §4–§24.

---

## 4. Domain Analysis (the honest test)

The user asks "graphic designer in Buea", "restaurant in Limbe", "wedding
photographer". What *should* be returned?

| Query | Real-world answer | Entity it maps to |
|---|---|---|
| "graphic designer in Buea" | a person **or** a studio, in Buea | **Listing** (professional or business), located in Buea |
| "restaurant in Limbe" | a restaurant location in Limbe | **Listing** (business) at a **Location** in Limbe |
| "wedding photographer" | a professional, location optional | **Listing** (professional) |
| "iPhone 13 in Buea" | a shop selling it in Buea | **Listing** (store) at a **Location** |
| "plumber near me" | a tradesperson near you | **Listing** (professional) |

**DECISION (derived):** the thing a user discovers is always **a Listing**. The
*type* of listing varies (business / professional / store); the *location* is an
attribute that some listings have (via a Location) and some do not.

This is the single most important finding of Phase 8: **the discoverable entity
is not "Business" and not "Branch" — it is the Listing, and Business/Branch are
two *different real-world things* that a Listing can be attached to (an
organization, and a place).**

---

## 5. Listing Definition (DECISION)

> **A Listing is the discoverable entity presented to users. It is owned by an
> Account, has a `type` (business | professional | store), a public identity
> (name, slug, description), optional categories/services/media/contacts, an
> optional Location, a lifecycle status, and its own reviews/analytics/leads.**

A Listing:
- **always** belongs to exactly one Account;
- **may** belong to an Organization (`Business`) — for multi-location brands;
- **may** have a Location (physical) or not (remote);
- **owns** its reviews, analytics, leads, services, categories, media, contacts;
- **is the canonical search result** and **the canonical public URL target**.

---

## 6. Business Definition (DECISION)

> **A Business is an organization/aggregate: a potentially multi-location brand
> owned by an Account. It is NOT a discoverable entity by itself.**

- Keeps `owner_id`, `name`, `description`, brand media (logo/cover).
- Owns zero-or-more Listings.
- "ABC Restaurant" = one `Business`; its Buea/Limbe presences = two `Listing`s.
- A single-location business = one `Business` + one `Listing` (or, pragmatically,
  a `Listing` with `business_id` set and one Location).

---

## 7. Branch/Location Definition (DECISION)

> **A Branch is a physical location. It is demoted to a pure `Location` concept
> attached to a Listing (and/or organization). It is NOT a discoverable entity.**

- Keeps geo/address/lat-long/hours/overrides/phone/whatsapp — all useful.
- Loses its Phase-7 "listing anchor" *role* (the columns may be repurposed or
  dropped; see §23).
- A Listing "happens at" a Location; a Listing *is not* a Location.

---

## 8. Professional Definition (DECISION)

> **A Professional is a Listing of type `professional`: an individual/service
> provider owned by an Account. It normally has NO Location.**

- May have: name, headline, bio, skills, services, portfolio media, contact.
- May belong to an Organization, or stand alone.
- "John Doe — Graphic Designer (Buea)" = a Professional Listing with an optional
  *service area* (not a Branch).

---

## 9. Store Definition (DECISION)

> **A Store is a Listing of type `store`: a commerce-oriented discoverable entity.
> It may have a Location and, in future, products/offers.**

- No transactions in this phase.
- Structurally identical to a Business Listing + commerce metadata later.

---

## 10. Ownership Model (DECISION)

```
ACCOUNT (User)
   │
   ├── owns ── Subscription ── Plan ── Entitlements     (account-scoped, unchanged)
   │
   ├── owns ── Listing*                                  (the discoverable unit)
   │              │
   │              ├── type: business | professional | store
   │              ├── business_id?  ───► Business (organization, optional)
   │              ├── location?     ───► Location (from branches, optional)
   │              ├── categories / services / media / contacts
   │              ├── reviews / analytics / leads
   │              └── visibility (status, hidden_at)
   │
   └── owns ── Business* (organizations)
                  └── has many ── Listing*
```

**DECISION:** Account → Listing is the canonical ownership edge. Business and
Location are *associations* of a Listing, not owners of one.

---

## 11. Data Ownership Matrix (DECISION)

| Capability | Account | Business (org) | Location (Branch) | **Listing** |
|---|---|---|---|---|
| name / slug / description | – | brand name | place name | **yes (canonical)** |
| location (geo/address) | – | – | **yes** | references one |
| contacts | – | brand contacts | place phone/whatsapp | **yes** |
| categories | – | default set | – | **yes** |
| services | – | org services | – | **yes** |
| media (logo/cover) | – | **brand** | – | **yes (listing)** |
| media (gallery/portfolio) | – | – | place photos | **yes** |
| reviews | – | – | – | **yes** |
| analytics | – | aggregate view | – | **yes (per listing)** |
| leads | – | – | place attribution | **yes** |
| coupons | – | business-wide | – | **yes (optional)** |
| hours | – | – | **yes** | inherits from location |
| visibility | – | org flags | – | **yes** |
| search identity | – | – | – | **yes (canonical)** |
| SEO identity | – | – | – | **yes (canonical)** |
| subscription | **yes** | – | – | – |
| quota | **yes** | – | – | counted |

Legend: **yes** = owned; *references* = pointer; dash = not owned.

---

## 12. Search Identity Model (DECISION)

- **The canonical search result entity is the `Listing`.**
- A `Business` aggregate is **not** independently indexed as a competing result;
  it is discoverable *through* its Listings. A brand with 3 locations yields **3
  Listing results** (or **1 grouped card of 3**, a UI choice) — never a stray
  parent + child pair.
- Professional/Store listings are indexed the same way.
- **Owner/listing is annotated with `listing_type` for filtering** ("show me
  professionals", "restaurants").
- No Meilisearch redesign in this phase; the decision is *which entity is
  indexed*. The Branch `identity_kind='listing'` shim from Phase 7 is replaced by
  a real `Listing` index.

---

## 13. URL Identity Model (DECISION)

- Canonical: **`/listing/{slug}`** (or a typed variant `/l/{slug}` if preferred).
- The `Listing.slug` is globally unique and is the SEO identity.
- Optional hierarchical alias for location listings (nice-to-have, DEFERRED):
  `/business/{business-slug}/{location-slug}` → 301 → `/listing/{listing-slug}`.
- `/business/{slug}` is **repurposed** to the organization profile (Business with
  its listings listed), or retired. Given no production data, **DECISION:**
  `/listing/{slug}` is canonical; `/business/{slug}` becomes the organization
  page.
- **The object that deserves a canonical URL is the Listing** (it is what users
  discover and share).

---

## 14. Review Model (DECISION)

> **Reviews belong to a Listing.**

- "ABC Restaurant — Buea" and "ABC Restaurant — Limbe" have **separate** reviews.
- A Business aggregate shows an **aggregate view** (sum/mean) that is **computed**,
  never stored, and **never inherits** a location's reputation.
- No automatic inheritance, no duplication, no inflation (upholds Phase 5 policy).
- Historical reviews (pre-Listing) migrate to the single resulting Listing when
  one exists; otherwise they attach to the Business and are **displayed on the
  organization page only**.

---

## 15. Service Model (DECISION)

> **Services belong to a Listing.** A shared catalog concept unifies them.

- Model: `services` (reusable concept: name, slug, category) — BYO: a global
  catalog is OPTIONAL (DEFERRED). Minimum: Listing owns service rows.
- Fixes Phase 8 §13: ABC Printing Buea offers "Graphic Design, Large Format
  Printing"; Limbe offers "T-Shirt Printing, Business Cards" — **each Listing
  owns its own services**, no duplication.
- "John Doe — Logo Design, Brand Identity" = a Professional Listing's services.
  No Business/Branch required.
- The Phase-7 `branch_id` on `business_services` is superseded by `listing_id`.

---

## 16. Category Model (DECISION)

> **Categories attach to Listings, not Businesses.** One unified category taxonomy.

- A Listing has categories (`listing_id` ↔ `category_id`), exactly like today's
  `business_categories` but re-pointed.
- The Phase-7 `branch_category` pivot is **deleted** (superseded by Listing
  categories).
- Business does not need its own category system; if an org-level default is
  desired later, it is a convenience, not a second taxonomy.

---

## 17. Analytics Model (DECISION)

Two levels, explicitly defined:
- **Listing analytics** (primary): views/clicks/etc. per `listing_id`, per day.
- **Account/Organization aggregate** (derived): computed by summing listings;
  **not stored separately** (avoids duplication).
- The Phase-7 nullable `branch_id` on `business_analytics` is **superseded** by
  `listing_id`; historical baseline rows migrate to the single Listing.

---

## 18. Subscription / Entitlement Model (DECISION — unchanged principle)

```
ACCOUNT (User) → SUBSCRIPTION → PLAN → ENTITLEMENTS
```

- Subscriptions stay **account-scoped**. **Never** attach to Listing/Business.
- One account can own many listings of different types — gated by entitlements
  (`CREATE_BUSINESS`, `CREATE_PROFESSIONAL`, `CREATE_STORE`) which already exist
  in `ListingType::creationEntitlement()`.

---

## 19. Quota Model (DECISION)

Plans eventually count (semantics defined; numbering deferred):
- **listings** (primary) — replaces the "max_businesses means listings" confusion.
- **locations** — physical locations (formerly `max_branches`).
- **team members**, **storage**, **featured placements**, **analytics level**.
- **DECISION:** `max_businesses` is re-interpreted/renamed to **`max_listings`**.
  `max_branches` becomes **`max_locations`**. Listing count = `Listing` rows, not
  `Business` rows.

---

## 20. Recommended Architecture (DECISION)

**OPTION C — `Listing` is the universal discoverable entity; `Business` becomes an optional Organization; `Branch` is demoted to a pure `Location`.**

Named: **"Listing-centric convergence."**

```
Account (users)
  ├── Subscription (account-scoped)
  └── Listings (owned, discoverable)
        ├── type ∈ {business, professional, store}
        ├── business_id? ──► Businesses (organizations, multi-location brands)
        ├── location_id? ──► Locations (from branches: geo, hours, phone)
        ├── categories, services, media, contacts
        ├── reviews, analytics, leads
        └── visibility (status, hidden_at)
```

- **Business** = organization (a brand that groups listings). Optional.
- **Location** = physical place (formerly Branch). Attached to a Listing (and/or
  organization). Optional.
- **Professional / Store / Business** = listing *types*.

---

## 21. Why This Architecture Wins

- **Matches the product vision exactly** (§1: Account → {Professional, Business →
  Locations, Store}).
- **Matches what users discover** (a Listing, of any type) — resolving P1.
- **Represents Professionals** with no location — resolving P2, P6.
- **Separates the three conflated concepts** (Account / Organization / Listing /
  Location / Service / Category / Professional / Store) per brief §5 — resolving
  P3, P4.
- **One review/analytics/service/category system**, owned by Listing — no dual
  systems, no duplication.
- **Search returns the right thing** (the Listing), not a stray parent+child.
- **Account owns subscriptions** — unchanged; quotas become honest
  (`max_listings`).
- **Deep simplification vs Phase 6/7:** deletes the half-built Branch-as-listing
  abstraction (`branch_id` columns, `branch_category`, `branches.slug` misuse,
  `identity_kind` shim), replacing it with one coherent entity.

*Why not Option A (Branch-as-listing):* invalid for Professionals; inverts the
existing discovery model; Branch is a *place*, not a *provider*.

*Why not Option B "as-is" (generic Listing with no demotion):* leaves `Business`
as both parent *and* listing — the exact ambiguity Phase 6 tried to avoid. Option
C demotes Business to organization, which is the clean resolution.

---

## 22. What We Should Keep (DECISION)

- The `User`-as-Account model and the **subscription/plan/entitlement** spine
  (`Subscription`, `Plan`, `EntitlementService`).
- The **ListingType** enum + `creationEntitlement()` mapping (extend it, don't
  rebuild it).
- The **lifecycle** concept (draft→submitted→approved→published, hidden_at) —
  moved to `Listing`.
- `Category` taxonomy, the image pipeline, hours/override logic, hours accessors.
- The **public profile UI patterns**, business directory resource shapes (adapted
  to Listings).
- The `SearchIntentParser`, location chips, favorites concept.

## 23. What We Should Delete / Replace (DECISION)

Given **no production data**, DELETE/REPLACE:
- `branches.slug` in its "listing identity" role → **Location has no public URL**
  (may keep a nullable slug only if a location page is ever wanted; default:
  **remove**).
- `branch_category` pivot → **replace** with `listing_categories`.
- `branch_id` on `business_services`, `business_images`, `business_contacts`,
  `reviews`, `business_analytics`, `coupons` → **replace** with `listing_id`.
- `Branch`'s Phase-7 listing relationships/scopes → **remove**; Branch becomes
  `Location`.
- `identity_kind='listing'` search shim → **remove**.
- `businesses.listing_type` column → **migrate** type onto `Listing`.
- `Business::listingsCountFor` counting Businesses → **replace** with Listing
  count.
- `ListingIdentity` / `ListingCapability` "coexistence" support classes → **retire**
  (their decision is superseded here) or convert to the new Listing model docs.
- Experimental `Branch` model cruft (large commented-out blocks) → clean up.

---

## 24. Proposed Database Model (DECISION)

```
accounts (== users; rename optional, DEFERRED)
  id, name, email, ... , role (role is authorization, NOT classification)

subscriptions            (account-scoped — UNCHANGED)
  id, user_id → accounts, plan_id, status, ...

plans                    (quota keys renamed later)
  id, max_listings, max_locations, ...

businesses               (ORGANIZATION — optional aggregate)
  id, owner_id → accounts, name, slug, description,
  logo, cover_image, status, hidden_at, ...

listings                 (NEW — the discoverable entity)
  id, owner_id → accounts
  business_id → businesses  (nullable: standalone professional)
  location_id → locations   (nullable: locationless professional)
  type        enum(business|professional|store)
  name, slug (unique), description
  phone, whatsapp, email, website
  status, hidden_at, is_featured, published_at
  ... (reviews/analytics are separate tables keyed by listing_id)

locations                (was `branches` — pure PLACE)
  id, owner_id → accounts, business_id → businesses (nullable)
  name, country_id, region_id, city_id, area_id,
  address, latitude, longitude, phone, whatsapp, status, hidden_at

listing_categories  (pivot)  listing_id ↔ category_id
listing_services             id, listing_id, name, description, sort_order
listing_media                id, listing_id, type(logo|cover|gallery), path, ...
listing_contacts             id, listing_id, type, value, ...
reviews                      id, listing_id → listings, user_id, rating, ...
business_analytics           id, listing_id → listings, date, views, ...  (per listing)
leads                        id, listing_id → listings, source, ...
coupons                      id, listing_id → listings (nullable → business-wide)
favorites                    id, user_id, listing_id
```

### ASCII ER diagram

```
                         ┌────────────┐
                         │  accounts  │  (== users)
                         │  (User)    │
                         └─────┬──────┘
             owner_id │        │ user_id            │ owner_id
        ┌─────────────┘        │                    └──────────────┐
        ▼                      ▼                                   ▼
 ┌──────────────┐      ┌────────────────┐                  ┌────────────────┐
 │  businesses  │      │ subscriptions  │                  │    listings    │
 │ (ORG/brand)  │◄────►│   plan_id →    │                  │  (DISCOVERABLE)│
 └──────┬───────┘      │     plans      │                  │  type ∈        │
        │ 1:N          └────────────────┘                  │  {business,    │
        │                                                  │   professional,│
        │                                                  │   store}       │
        │        business_id? (nullable) ─────────────────►│                │
        │                                                  │  location_id? ─┼──┐
        ▼                                                  └───────┬────────┘  │
 ┌──────────────┐                                                  │ 1:N       │
 │  locations   │◄────────────── 1:N ─────────────────────────────┘           │
 │ (was Branch) │◄───────────────────────────────────────────────────────────┘
 │  geo/hours   │
 └──────────────┘
        │
        ├── business_hours (location_id)
        └── location_hour_overrides (location_id)

 listings 1:N ──► listing_services
             ├── listing_media
             ├── listing_contacts
             ├── reviews
             ├── business_analytics   (per listing)
             ├── leads
             ├── coupons (nullable → business-wide)
             └── favorites (user ↔ listing)
 listings N:M ──► categories (via listing_categories)
```

---

## 25. Proposed Implementation Sequence (DEFERRED — future phases)

**Phase 9 — Introduce `Listing` core.**
Create `listings` from `businesses` (one Listing per Business initially,
`type=business`). Add `listing_id` to reviews/analytics/leads/services/coupons/
categories/media/contacts. Move lifecycle columns to `listings`. Keep Business as
organization. Frontend `/listing/{slug}` added beside `/business/{slug}`.

**Phase 10 — Demote `Branch` → `Location`.**
Rename/migrate `branches` → `locations`; attach to `listings.location_id`. Move
hours/overrides to `location_id`. Remove `branches.slug`, `branch_category`, and
the six Phase-7 `branch_id` columns (superseded by `listing_id`).

**Phase 11 — Professional & Store listings.**
Enable `ListingType::PROFESSIONAL` / `STORE`; add type-specific metadata
(professional headline/skills; store commerce fields later). Locationless
listings become first-class.

**Phase 12 — Search & URL convergence.**
Index `listings` as the canonical discoverable entity (drop parent-as-result).
Adopt `/listing/{slug}` canonical URLs. Review-policy visible aggregate on org
page.

**Phase 13 — Quota/entitlement rename.**
`max_businesses → max_listings`, `max_branches → max_locations`. Update
`EntitlementService`, plans, and UI copy.

**Phase 14 — Cleanup.**
Remove retired support classes (`ListingIdentity`, `ListingCapability`,
`DataOwnership` coexistence matrices), dead `Branch` code, and superseded
migrations. Reset the development database.

---

## 26. Files Changed (this phase)

**Documentation only.** No code, schema, route, or test changes were required to
establish this decision.

- `docs/PHASE_8_LISTING_ARCHITECTURE_DECISION.md` (NEW)

---

## Appendix A — Brief deliverables index

| Brief item | Section |
|---|---|
| 1 Current Architecture | §1 |
| 2 Problems | §2 |
| 3 Candidate Architectures | §3 |
| 4 Domain Analysis | §4 |
| 5 Listing Definition | §5 |
| 6 Business Definition | §6 |
| 7 Branch/Location Definition | §7 |
| 8 Professional Definition | §8 |
| 9 Store Definition | §9 |
| 10 Ownership Model | §10 |
| 11 Data Ownership Matrix | §11 |
| 12 Search Identity Model | §12 |
| 13 URL Identity Model | §13 |
| 14 Review Model | §14 |
| 15 Service Model | §15 |
| 16 Category Model | §16 |
| 17 Analytics Model | §17 |
| 18 Subscription/Entitlement | §18 |
| 19 Quota Model | §19 |
| 20 Recommended Architecture | §20 |
| 21 Why This Wins | §21 |
| 22 Keep | §22 |
| 23 Delete/Replace | §23 |
| 24 Proposed Database Model (+ ER) | §24 |
| 25 Implementation Sequence | §25 |
