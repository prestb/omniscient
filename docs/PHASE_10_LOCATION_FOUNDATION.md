# PHASE 10 — Universal Location Foundation

Status: **implemented and green.**
Companion documents: `PHASE_8_LISTING_ARCHITECTURE_DECISION.md`,
`PHASE_9_LISTING_CORE_IMPLEMENTATION.md`.

This phase establishes **Location** as a *universal physical-place entity*
rather than a "branch of a business". It does **not** reopen the Listing
architecture from Phase 9.

---

## 1. Location definition — FACT

A **Location** is a physical place / address. It answers only:

> *Where is this physically?*

It is **not** the discoverable entity — the **Listing** is. A Location carries
**no** discoverable identity.

Canonical vocabulary:

```
ACCOUNT
  └── ORGANIZATION (optional)
        └── LISTING
              └── LOCATION (optional)
```

Not:

```
ACCOUNT → BUSINESS → BRANCH → LISTING
LISTING → BRANCH   (branch as a special kind of location)
```

---

## 2. Why Branch was replaced/demoted — DECISION

The legacy `branches` table and `Branch` model conflated two things:

1. a **discoverable entity** (it had a public `slug`, was searchable, and could
   be a discovery target), and
2. a **physical place** (`address`, `city`, `region`, `country`, coordinates,
   hours).

Phase 9 already made the **Listing** the canonical discoverable entity and
introduced `Listing.location_id`. Phase 10 finishes the job by replacing the
`branches` table with a genuine `locations` table.

> **FACT:** The development database contained no valuable production data.
> Per the Phase 10 brief, a clean schema was preferred over backward
> compatibility. The `branches` table no longer exists.

> **FACT:** `resources/js/Pages/Owner/Branches/*` and the `Branch` model are
> gone from source. (Some references remain in `public/build/` compiled
> artifacts and in historical Phase 6–7 documentation; these are not
> production code paths.)

---

## 3. Location schema — FACT

Table: **`locations`**
Migration: `database/migrations/2026_08_31_000007_create_locations_table.php`
Model: `app/Models/Location.php`

Columns:

| Column          | Type             | Notes                                             |
|-----------------|------------------|---------------------------------------------------|
| `id`            | bigint PK        |                                                   |
| `business_id`   | FK, **NULLABLE** | Optional organization link (`nullOnDelete`)       |
| `name`          | string, nullable | Optional human label, e.g. "Molyko Campus"        |
| `country_id`    | FK, nullable     |                                                   |
| `region_id`     | FK, nullable     |                                                   |
| `city_id`       | FK, nullable     |                                                   |
| `area_id`       | FK, nullable     |                                                   |
| `address`       | text, nullable   |                                                   |
| `landmark`      | string, nullable |                                                   |
| `postal_code`   | string, nullable |                                                   |
| `latitude`      | decimal(10,8)    |                                                   |
| `longitude`     | decimal(11,8)    |                                                   |
| `phone`         | string, nullable | Location-specific contact only                    |
| `whatsapp`      | string, nullable | Location-specific contact only                    |
| `status`        | enum             | `active` \| `temporarily_unavailable` \| `unlisted` |
| `is_primary`    | boolean          | Meaningful only when `business_id` is set          |
| `sort_order`    | int              |                                                   |
| `hidden_at`     | timestamp        | Visibility lock (used by plan-overflow hiding)     |
| `created_at`, `updated_at`, `deleted_at` | | Soft deletes                              |

Indexes: `business_id`, `(business_id, is_primary)`,
`(country_id, region_id, city_id, area_id)`, `status`, `latitude`, `longitude`.

> **DECISION:** No `business_id`-required constraint. No listing-identity
> columns. No GIS subsystem beyond coordinate columns + btree indexes.

---

## 4. Listing → Location relationship — FACT

- `listings.location_id → locations.id` (`nullable`, `nullOnDelete`).
- `Listing belongsTo Location`; `Location hasMany Listing`.

```php
// Listing
public function location() { return $this->belongsTo(Location::class, 'location_id'); }

// Location
public function listings() { return $this->hasMany(Listing::class, 'location_id'); }
```

---

## 5. The 0/1 Location invariant — FACT / DECISION

- A Listing has **zero or one** Location.
- Multiple physical presences are represented by **multiple Listings**, never
  one Listing with many Locations.

```
ABC School (Organization)
  ├── Listing — Buea   → Location — Buea
  ├── Listing — Limbe  → Location — Limbe
  └── Listing — Douala → Location — Douala
```

---

## 6. Location independence from Business — FACT

A Location does **not** require a Business. `business_id` is nullable, so all
of these are valid:

```
Store Listing         → Location            (business_id = NULL)
Event Listing         → Location            (business_id = NULL)   [future]
Business → Location                          (business_id = set)
```

This preserves the future **Event** model without redesigning Location.

---

## 7. Location vs Service Area — DEFERRED

**Location** = *where a listing physically is.*
**Service Area** = *where a listing operates* (e.g. a plumber covering
Buea + Limbe + Mutengene).

Service Area is **not** modelled in this phase. The architectural intent is
that it can be added later **without redefining Location**.

---

## 8. Field ownership decisions — DECISION

| Concern                          | Owner      | Rationale                                             |
|----------------------------------|------------|-------------------------------------------------------|
| Name, slug, type, description    | Listing    | Discoverable identity                                 |
| Categories, services, reviews    | Listing    | Discovery / engagement                                |
| Media, analytics, leads, coupons | Listing    | Listing-scoped children (`listing_id`)                |
| General / discoverable contact   | Listing    | `business_contacts.listing_id`                        |
| `address`, `city`, `region`, `country`, `area`, `landmark`, `postal_code` | Location | Describes the physical place |
| `latitude`, `longitude`          | Location   | Describes the physical place                          |
| Location-specific `phone`/`whatsapp` | Location | Only when the contact genuinely belongs to the *place* |
| Weekly hours                     | Location   | When the *physical place* operates → `location_hours` |
| Date overrides                   | Location   | `location_hour_overrides`                             |

> **DECISION:** A Location's `name` is an optional human label for the place
> (e.g. "Molyko Campus"), **not** listing identity. It is never a Listing
> name/slug source.

---

## 9. Hours ownership — DECISION

Normal hours and special/holiday overrides describe **when the physical place
operates**, so they are **Location-owned**:

- `location_hours` — weekly schedule (`location_id` FK).
- `location_hour_overrides` — date-specific closed days / special hours.

> **FACT:** There is exactly **one** authoritative normal-hours system
> (`location_hours`) and **one** override mechanism
> (`location_hour_overrides`). No duplicate hour systems exist.

`Location` exposes override-aware accessors: `is_open_now`, `today_override`,
`today_special_hours`, `hours_summary`, plus an override-aware weekly summary.

---

## 10. Contacts ownership — DECISION

- **Listing** → discoverable/general contact information
  (`business_contacts`, scoped by `listing_id`).
- **Location** → physical-location-specific `phone` / `whatsapp`.

> **DECISION:** The Location `phone`/`whatsapp` columns are retained **only**
> for genuinely place-specific contacts. General listing contact does not live
> on Location. No duplication is introduced.

---

## 11. Migration decisions — FACT / DECISION

> **FACT:** `branches` was replaced by `locations`. There is no `branches`
> migration and no `Branch` model.
>
> **DECISION:** Destructive migration was acceptable; the dev database is
> disposable. No nullable "just in case" foreign keys were added. No
> deprecated Branch columns were retained.

> **FACT:** `listings.location_id` points at `locations.id` from the Phase 9
> listings migration.

---

## 12. Search — FACT

Search remains **Listing-centric** (`Listing::searchableAs() === 'listings'`).
`Listing::toSearchableArray()` resolves Location data cleanly (city / region /
country names, `location_id`, `city_id`) via the `location` relation.

> **DECISION:** Location is **not** a standalone search result and is **not**
> indexed as its own entity. No "near me" / radius / geospatial ranking / map
> clustering was implemented. These are deferred to a dedicated geospatial
> phase.

---

## 13. Future Event compatibility — FACT

`Listing.location_id` is nullable and Location does not require a Business, so
a future `Event` Listing type can attach a Location with **no redesign**:

```
Commonwealth Business Summit
  type: event
  Listing → Location: PTSMI Auditorium, Buea
```

> **DEFERRED:** The `Event` listing type and any event-specific behaviour are
> **not** implemented in this phase.

---

## 14. Tests — FACT

Added (all green):

- `tests/Feature/Location/LocationFoundationTest.php`
  - Location created without a Business
  - Creating a Location does not require a Business
  - Location optionally belongs to a Business
  - Listing with no Location (`location_id = NULL`) is valid
  - Listing can reference a Location
  - Listing A → Location A, Listing B → Location B
  - Multiple Listings under one Business, each with its own Location
  - Standalone Listing (`business_id = NULL`) + Location
  - Location carries no Listing identity columns
  - `locations.business_id` is nullable
  - Location is not a searchable/discoverable entity
- `tests/Feature/Location/LocationSchemaTest.php`
  - `locations` table + expected columns
  - `locations` does not own Listing identity columns
  - `business_id` nullable on `locations`
  - legacy `branches` table is gone

Existing Phase 9 suites (`tests/Feature/Listing/*`) remain green and cover the
0/1 Location invariant and child ownership.

---

## 15. Remaining OPEN / DEFERRED items

- **DEFERRED — Service Area:** Where a listing operates (vs. where it is).
  Must be addable later with no redefinition of Location.
- **DEFERRED — Event domain:** `event` listing type + event-specific fields.
- **DEFERRED — Geospatial search:** "near me", radius search, geospatial
  ranking, map clustering.
- **OPEN — `branches` entitlement key:** The entitlement/quota key is still
  named `branches` (`App\Support\Entitlement::CREATE_BRANCH = 'branches'`),
  `plans.max_branches` still exists, and the route middleware is
  `plan.limit:branches`. These are **internal quota identifiers** (Subscription
  and entitlement ownership remains Account/User-scoped per the Phase 10
  constraints) and were intentionally **not** renamed in this phase. User-facing
  labels already read "Locations". A future phase may rename the key to
  `locations` as a purely internal refactor.
- **OPEN — Back-compat prop aliases:** `Owner\LocationController` passes both
  `locations` and a back-compat `branches` prop; `BusinessDirectoryResource`
  exposes a `branches` alias. These are additive and safe but could be pruned.
- **OPEN — Compiled assets:** `public/build/` artifacts still reference the old
  `Owner/Branches/*` entry paths; a frontend rebuild will clear them.

---

## 16. Out of scope (explicitly NOT done) — FACT

- Listing architecture was **not** redesigned.
- No Event functionality.
- No Service Area functionality.
- No search redesign.
- No GIS / radius-search subsystem.
- One Listing was **not** given multi-location support.
- Location was **not** made a Listing.
- Location was **not** made obligatorily owned by a Business.
- No duplicate Location/Branch systems.
- Subscription architecture, Listing quotas, and child ownership were **not**
  changed.
