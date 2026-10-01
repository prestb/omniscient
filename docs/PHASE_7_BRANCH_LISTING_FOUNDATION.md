# Omniscient — Phase 7: Branch as Listing Capability Foundation

> Status: **Phase 7 complete** — ADDITIVE DOMAIN FOUNDATION ONLY.
> No Business → Branch migration. No historical data copied or re-attributed.
> No slug generated for existing branches. No public route/URL change.
> No branch counted as an independent listing. No Professional/Store.
>
> Markers: **FACT** · **DECISION** · **IMPLEMENTATION** · **DEFERRED**.

---

## 1. Existing Branch Capabilities (FACT)

A `Branch` today already models a physical location in full:

| Capability | Column(s) | Present |
|---|---|---|
| Public identity | `name` | yes |
| Location | `country_id`, `region_id`, `city_id`, `area_id`, `address`, `latitude`, `longitude` | yes |
| Contacts | `phone`, `whatsapp` | yes |
| Status / visibility | `status` (`active`/`temporarily_unavailable`/`unlisted`), `hidden_at` | yes |
| Ordering | `sort_order`, `is_primary` | yes |
| Hours | `business_hours` (branch_id) | yes |
| Date overrides | `branch_hour_overrides` (branch_id) | yes |
| Search | `Searchable` + `toSearchableArray()` | yes |

The public profile already renders branches per-location (hours, coordinates,
open-now, override awareness), and the directory already filters through
branches.

## 2. Listing Capability Matrix (FACT + IMPLEMENTATION)

| Capability | Current owner | Branch support today | Phase 7 action |
|---|---|---|---|
| Location | Branch | yes | retain |
| Phone | Branch | yes | retain |
| WhatsApp | Branch | yes | retain |
| Hours | Branch | yes | retain |
| Services | Business | no (indirect) | **add nullable `branch_id`** |
| Categories | Business | no (indirect) | **add `branch_category` pivot** |
| Images | Business | no (indirect) | **add nullable `branch_id`** |
| Reviews | Business | no | **add nullable `branch_id`** (association only) |
| Analytics | Business | no | **add nullable `branch_id` + relax unique** |
| Leads | Business + Branch | partial (`branch_id`) | retain (already listing-ready) |
| Coupons | Business | no | **add nullable `branch_id`** |
| Contacts | Business | partial (branch phone/whatsapp) | **add nullable `branch_id` to typed contacts** |
| Public slug | Business | no | **add nullable unique `slug` to branches** |
| Search | Branch | yes | retain/extend discriminator |

Encoded in `ListingCapability::matrix()`.

## 3. Services Architecture (§4) — IMPLEMENTATION

- FACT: `business_services` keys only on `business_id`.
- DECISION: the explicit requirement is that **a Buea listing can offer a service
  Limbe does not**. The minimum enabling structure is an **optional nullable
  `branch_id`** on `business_services`.
- `NULL` = a parent-level service (every existing row). Non-null = offered at
  that listing.
- **No inheritance logic** is implemented (not justified yet). No service is
  duplicated. `scopeParentLevel()` reads parent-level services.

## 4. Categories Architecture (§5) — IMPLEMENTATION

- FACT: `business_categories` is a `business_id`↔`category_id` pivot with a
  composite unique — it cannot be re-pointed without being destructive.
- DECISION: per-location category differences are legitimate, so a **separate
  additive pivot `branch_category`** (`branch_id`, `category_id`, `is_primary`,
  `sort_order`) is introduced. The parent pivot is **untouched**.
- Many-to-many is the correct structure (a listing has many categories).

## 5. Media Architecture (§6) — IMPLEMENTATION

- FACT: `business_images` keys on `business_id`, with `type` in
  `{logo, cover, gallery}`, and paths are public URLs.
- DECISION: `logo`/`cover` remain brand assets (parent); a gallery may be
  location-specific. An **optional nullable `branch_id`** is added.
- **No file is moved, no path/URL is changed, no file is duplicated.**

## 6. Contacts Architecture (§7) — IMPLEMENTATION

- FACT: `branches` already hold `phone` + `whatsapp`; `business_contacts` holds
  typed rows (`type`, `value`).
- DECISION: per-location dial numbers already live on the branch and never
  bleed between locations. To let a **typed** contact be location-specific, an
  **optional nullable `branch_id`** is added to `business_contacts`.
- **No existing contact is deleted or rewritten.**

## 7. Review Architecture (§8) — IMPLEMENTATION

- FACT: `reviews` keys only on `business_id` (no `branch_id`).
- DECISION: **do NOT migrate reviews.** Add an **optional nullable `branch_id`**
  so a future review can reference its listing. ALL existing reviews keep
  `branch_id = NULL` (historical/parent).
- `scopeParentLevel()` / `scopeForListing()` make the split queryable. No
  duplication, no inflation.

## 8. Lead Architecture (§9) — FACT / retain

- `leads` already has `business_id` + nullable `branch_id` (Phase 5) — it is
  already listing-ready. **Untouched.**
- branch-specific lead → `branch_id` set; parent-level lead → `branch_id` NULL;
  ambiguous historical lead → NULL.

## 9. Analytics Architecture (§10) — IMPLEMENTATION

- FACT: `business_analytics` keys `(business_id, date)` with a unique index.
- DECISION: add an **optional nullable `branch_id`** for future per-listing
  analytics, and **relax the unique** to `(business_id, branch_id, date)` so a
  future listing row is representable. Existing `(business_id, date)` rows are
  unchanged. `track*()` helpers gained an optional `$branchId` (default null =
  historical behaviour). **No historical per-location metric is fabricated.

## 10. Coupon Architecture (§11) — IMPLEMENTATION

- FACT: `coupons` keys `business_id`; `coupon_redemptions` keys `coupon_id`.
- DECISION: add an **optional nullable `branch_id`** to distinguish a
  business-wide coupon (`NULL`) from a branch-specific coupon. Redemption
  history is **never** duplicated or reassigned (stays an immutable audit).

## 11. Public Identity (§12) — IMPLEMENTATION

- Added a **nullable, unique `slug`** to `branches`.
- Guarantees: nullable → **no** existing branch receives a slug automatically;
  unique → collisions are the DB's concern later; a separate table → **no
  conflict** with `businesses.slug`; `/business/{slug}` is **unchanged**.
- Slug generation strategy is DEFERRED (defined in Phase 6, not built here).

## 12. Search Implications (§13) — IMPLEMENTATION

- `Branch::toSearchableArray()` now includes an **`identity_kind = 'listing'`**
  discriminator and the `slug`. This is additive and lets a future index
  separate parent vs listing without duplicate unrelated results.
- **No ranking change, no redesign, no Meilisearch config change.**

## 13. Visibility / Status (§14) — DECISION

- `Branch::isPubliclyVisible()` (status `active` AND `hidden_at` null) makes an
  individual listing disable-able without disabling the parent. Existing
  production behaviour is unchanged (the accessor is additive).

## 14. Authorization (§15) — DECISION

- **Unchanged.** Authorization stays `Business`-based (`canBeEditedBy`). A
  Branch has **no independent policy — intentionally**. The Account that owns
  the parent can manage its listings. No Manager/Collaborator is introduced.

## 15. Entitlement Interaction (§16) — DECISION

- **Branches are NOT counted as independent listings.** `max_businesses` is
  unchanged; the `branches` route still uses `plan.limit:branches`. Phase 7 adds
  structure, not quota consumption.

## 16. Professional / Store Compatibility (§17) — DECISION

- **Warning honoured:** Branch is the listing anchor for **Business** listings,
  not a universal entity for every future type.
- A Professional/Store listing type is another **parent** type; it need not have
  a Branch if its domain lacks physical locations. No Business-specific
  assumption is baked into the foundation. **Not implemented.**

## 17. Migration Implications (§18) — DECISION

- The foundation makes the destination **representable**. It does **not** make
  any Business `MIGRATION_READY`: no data has been attributed.
- After Phase 7 a Business may be **POLICY_READY** and **STRUCTURE_READY** while
  still **NOT MIGRATION_READY** (data attribution has not occurred).
- Readiness is **not weakened**: the readiness service is untouched except for
  Phase 6 vocabulary.

## 18. Deferred Work

Populating any `branch_id` · generating branch slugs · new listing routes/URLs ·
counting branches for quota · Branch-specific authorization · Professional/Store ·
per-listing search ranking · any Business → Branch migration.
