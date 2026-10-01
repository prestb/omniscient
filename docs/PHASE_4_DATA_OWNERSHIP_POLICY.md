# Omniscient — Phase 4: Data Ownership & Listing Migration Policy

> Status: **Phase 4 complete** — POLICY & ANALYSIS ONLY.
> No migration was executed. No production Business/Branch data was copied,
> duplicated, reassigned, or mutated. No new tables. No URL changes.
>
> This document is the authoritative product/domain rulebook that a future,
> separately-approved migration phase must obey.
>
> Companion docs:
> - `docs/PHASE_1_ARCHITECTURE.md` (account / subscription / entitlement model)
> - `docs/PHASE_2_LISTING_DOMAIN.md` (Listing domain foundation)
> - `docs/PHASE_3_ENTITLEMENTS_MIGRATION.md` (listing-type entitlements + read-only analyzer)

---

## 1. Current Domain Model (verified against the real schema)

```
users (Account)
  └─ businesses (owner_id → users.id, cascadeOnDelete)        ← the Listing today
       ├─ listing_type = 'business'                            (Phase 2)
       ├─ slug (unique)  ← public URL key: /business/{slug}
       ├─ branches (business_id → businesses.id)               ← location child
       │    ├─ business_hours        (branch_id → branches.id)
       │    └─ branch_hour_overrides (branch_id → branches.id)
       ├─ business_categories (business_id, category_id)  [pivot, unique pair]
       ├─ business_services   (business_id → businesses.id)
       ├─ business_contacts   (business_id → businesses.id)
       ├─ business_images     (business_id → businesses.id)
       ├─ business_analytics  (business_id, date) [unique per business+date]
       ├─ reviews             (business_id → businesses.id)   ← business-only
       ├─ leads               (business_id → businesses.id, branch_id NULLABLE)
       ├─ coupons             (business_id, user_id)
       │    └─ coupon_redemptions (coupon_id only)             ← no branch
       └─ favorites           (user_id, business_id) [unique pair]
```

**Verified primary keys / FKs (from migrations):**

| Table | FK columns | Branch-aware? |
|---|---|---|
| `branches` | `business_id` | — |
| `business_hours` | `branch_id` | ✅ |
| `branch_hour_overrides` | `branch_id` | ✅ |
| `business_categories` | `business_id`, `category_id` | ❌ |
| `business_services` | `business_id` | ❌ |
| `business_contacts` | `business_id` | ❌ |
| `business_images` | `business_id` | ❌ |
| `reviews` | `business_id` | ❌ **no branch column** |
| `review_replies` | `review_id`, `user_id` | ❌ |
| `leads` | `business_id` + **nullable** `branch_id` | ⚠️ partial |
| `business_analytics` | `business_id`, `date` | ❌ |
| `coupons` | `business_id`, `user_id` | ❌ |
| `coupon_redemptions` | `coupon_id` (only) | ❌ |
| `coupon_redemption_tokens` | `coupon_id`, `context` JSON (may hold branch) | ⚠️ transient |
| `favorites` | `user_id`, `business_id` | ❌ |
| `subscriptions` | `user_id` (account) **and** `business_id` (legacy link) | ❌ |

**"Verification" is NOT stored domain data.** The blue "Verified" badge in
`BusinessProfile.vue` comes from `feature_flags.verified_badge`, which is a
**plan entitlement** (`ownerCanUseFeature('verified_badge')`). It is therefore
derived from the **account/plan**, not owned by a Business or Branch row.

---

## 2. Target Domain Model (future, not built in Phase 4)

```
ACCOUNT (User)
  └─ LISTINGS (each an independently-managed public presence)
       ├── Listing: SBKRAFT Buea
       │      identity · location · contacts · hours · services
       │      categories · gallery · reviews · analytics · leads
       │      coupons · visibility · management
       └── Listing: SBKRAFT Limbe
              (same independent structure)
```

A future Brand/Organization **may** group Listings — **out of scope** for Phase 4.

---

## 3. Data Ownership Matrix

Legend for **Class**:
**A** Listing-owned · **B** Branch/location-owned · **C** Account-owned ·
**D** Shared/ambiguous · **E** Derived/system · **F** Legacy/administrative.

| Data | Current owner | Future owner | Class | Migration rule | Ambiguity |
|---|---|---|---|---|---|
| Business identity (`name`,`slug`,`description`,`logo`,`cover_image`) | Business | Listing | A | Becomes the listing identity; slug regenerated per listing | LOW |
| `businesses.listing_type` | Business | Listing | A | Carried as-is (`business`) | LOW |
| Business → `owner_id` | Business | Listing (same account) | C | Owner unchanged; listing stays on same account | LOW |
| Branch row (geo, address, lat/long, phone, whatsapp) | Branch | Listing | B | Moves into the new listing's location | LOW |
| `business_hours` (weekly) | Branch | Listing | B | Moves with `branch_id` | LOW |
| `branch_hour_overrides` (special hours) | Branch | Listing | B | Moves with `branch_id` | LOW |
| `business_categories` (pivot) | Business | Listing | D | Needs decision: brand-level vs per-location discovery | MEDIUM |
| `business_services` | Business | Listing | D | No branch column; cannot infer which branch provides a service | **HIGH** |
| `business_contacts` | Business | Listing | D | A Buea phone must not become Limbe's phone | **HIGH** |
| `business_images` (logo/cover/gallery) | Business | Listing | D | Split brand assets (logo/cover) vs location gallery | MEDIUM |
| `reviews` | Business | Listing | D | **Never copy 1→N**; no branch column exists | **HIGH** |
| `review_replies` | Business | (follows review) | D | Determined only after the review policy | **HIGH** |
| `leads` | Business (+ optional branch) | Listing | D/F | Use `branch_id` when present; else historical | **HIGH** |
| `business_analytics` | Business | Derived | E | **Regenerate**; do not fabricate history | **HIGH** |
| `coupons` | Business | Listing | D | Business-level today; branch applicability unknown | MEDIUM |
| `coupon_redemptions` | Coupon | Legacy/audit | F | Immutable historical audit; never reassigned | LOW |
| `favorites` | Business | Listing | D | Needs identifier-continuity strategy | **HIGH** |
| "Verification" badge | Plan/feature flag | Plan/feature flag | C/E | Not migrated; remains account-plan-derived | LOW |
| `subscriptions` | Account (`user_id`) | Account | C | **Unchanged** — remains account-scoped | LOW |
| `max_branches` | Plan | Plan (transitional) | — | Coexists with listing limit; not yet removed | LOW |

> The illustrative table in the Phase 4 brief is a guideline; **this matrix is
> the one derived from the actual codebase.**

---

## 4. Review Policy

**Hard fact:** `reviews` has only `business_id` (migration
`2026_09_02_043456_create_reviews_table.php`). There is **no** `branch_id`.

**Rule (approved for any future migration):**

1. **NEVER duplicate** a review across resulting listings. One historical
   review must never become N reviews (reputation inflation is forbidden).
2. A review is **not** automatically attributable to a specific branch — no
   evidence exists in the schema.
3. Default disposition: **retain on the legacy Business identity** (which
   becomes the "primary"/parent listing, or a preserved legacy listing).
4. Assignment to a specific listing may occur **only when explicit evidence
   exists** (e.g. a future `branch_id`/location field is added and populated).
   Absent evidence ⇒ **manual review**, never a guess.
5. Review **URLs/IDs** (`business/{business}/reviews`, `admin/reviews/{id}`)
   are resolved by ids that remain stable in a non-destructive migration.

**Unresolved decision:** whether a future `reviews.branch_id` should be added
and backfilled, or whether reviews remain permanently business/parent-level.

---

## 5. Service Policy

`business_services` has only `business_id` — services are **business-wide** today.

**Rule:**
- Do **not** assume every branch provides every service.
- Default disposition: services remain at the **parent/business** level unless
  explicitly marked branch-specific in future.
- Policy buckets for the future:
  - explicitly branch-specific → move to that listing,
  - business-wide & verified → may become listing-level on each listing,
  - ambiguous → **retain / manual review**, never auto-copied.

**Unresolved decision:** do services ever become per-location, and who decides?

---

## 6. Category Policy

`business_categories` is a `business_id ↔ category_id` pivot (unique pair).

**Rule:**
- Categories are currently business **identity + discovery metadata**.
- Do **not** silently duplicate categories into every resulting listing.
- Default: categories remain at the parent/business identity unless a product
  decision establishes per-location category semantics.

**Unresolved decision:** per-location categories vs brand-level categories.

---

## 7. Media Policy

`business_images` has only `business_id`; `type ∈ {logo, cover, gallery}`.

**Classification:**
- **logo / cover** → brand assets (business/parent identity).
- **gallery** → potentially location-specific.
- **ambiguous** → no location metadata exists in the row.

**Rule:**
- **Do not delete or move physical files in Phase 4.**
- Image URLs are public (`Storage::disk('public')->url(...)`), so the **path
  must remain valid** regardless of DB ownership.
- Gallery images are **retained** and only re-attributed when evidence exists.

**Unresolved decision:** which gallery images belong to which location.

---

## 8. Contact Policy

`business_contacts` has only `business_id` (type + value + is_primary).

**Rule:**
- A contact belongs to the **business/parent** today.
- A Buea phone number **must not** silently become Limbe's phone.
- Branch-level phone/whatsapp **already exist on the `branches` table** and are
  the authoritative per-location contacts.
- Contacts are **retained at parent**; per-location contacts come from the
  Branch record at migration time.

**Unresolved decision:** whether shared social handles are duplicated or kept
parent-level.

---

## 9. Lead Policy

`leads` has `business_id` **and nullable `branch_id`** (`nullOnDelete`).

**Rule:**
- Leads are **historical business activity**; never duplicated.
- When `branch_id` is present → the lead can be attributed to that branch's
  future listing.
- When `branch_id` is NULL → **ambiguous**; retains the parent/business
  attribution, flagged for optional manual assignment.
- Lead analytics/history must stay auditable.

**Unresolved decision:** disposition of NULL-branch historical leads on a
multi-branch business.

---

## 10. Analytics Policy

`business_analytics` is keyed by `(business_id, date)` (unique).

**Rule:**
- **Do NOT fabricate** historical metrics.
- Historical metrics remain attached to the **business/parent** identity as a
  baseline; they are **not** copied to each resulting listing.
- Future per-listing analytics **start fresh** from migration day.
- A migration **baseline** row/date should be recorded once migration exists.

**Unresolved decision:** whether historical views are shown on the parent
listing only, or split (which is impossible to do truthfully).

---

## 11. Favorite Policy

`favorites` is `(user_id, business_id)` unique.

**Rule:**
- Existing favorites must **remain meaningful** — never break a user's bookmark
  merely for architectural cleanliness.
- Because `businesses.id` and `slug` can remain **stable** (non-destructive
  migration keeps the parent row), favorites keep resolving.
- Where a favorite should follow a *specific* location is **ambiguous**;
  default = keep pointing at the parent/legacy identity.

**Unresolved decision:** whether a user who favorited "SBKRAFT" should be
offered its Buea/Limbe listings (require an explicit user choice, no silent
rewrite).

---

## 12. Coupon / Offer Policy

`coupons` → `business_id` + `user_id`; `coupon_redemptions` → `coupon_id` only.

**Rule:**
- Coupons are **business-level** today; branch applicability is unknown.
- Do **not** duplicate active or historical coupons.
- Historical `coupon_redemptions` are an **immutable audit trail** — never
  reassigned or copied.
- The redemption flow's branch choice lives in `coupon_redemption_tokens.context`
  (transient) and is **not** authoritative ownership data.

**Unresolved decision:** whether branch-limited coupons become a real concept.

---

## 13. Hours Policy (clearest migration candidate)

Confirmed: `business_hours` and `branch_hour_overrides` both key on `branch_id`,
and `Branch` exposes `hours()` / `hourOverrides()`.

```
Branch → (location + weekly hours + special hours) → future Listing
```

**Rule:** weekly hours and date overrides move **with the branch** into the
resulting listing. This is low-ambiguity and can be automated in a future
migration — but is **not implemented in Phase 4**.

---

## 14. Identifier & URL Continuity Policy

Current public URL: **`/business/{slug}`** resolved by `Business::where('slug', …)`.

**Rule (non-destructive):**
- Keep the **`businesses.id` and `slug` of the parent/legacy row stable**.
- New listings get **new** ids/slugs derived from their branch (e.g.
  `sbkraft-buea`), while the **legacy slug keeps resolving** to the parent (or
  redirects to a chosen canonical listing).
- Provide an **explicit redirect/alias strategy** for:
  - old Business URLs, search-engine indexed URLs, external links,
  - favorites (by stable parent id/slug),
  - reviews (by stable review id),
  - analytics (baseline preserved).
- **No destructive URL migration.** No URL is changed in Phase 4.

**Unresolved decision:** whether `/business/{legacy-slug}` should 301 to the
primary listing or render a multi-location page.

---

## 15. Migration States (conceptual, not implemented)

```
LEGACY → ANALYZED → READY → MIGRATION_IN_PROGRESS → MIGRATED → VERIFIED
                                                   ↘ FAILED (reversible)
```

Defines **"safe to migrate"**: ANALYZED (read-only report exists) + no
blocking ambiguities + invariants pre-checked. **No state machine is built in
Phase 4** because the current architecture does not require one yet.

---

## 16. Migration Invariants (hard requirements for any future migration)

1. **No data loss** — no source record disappears unexpectedly.
2. **No reputation inflation** — one review never becomes N reviews.
3. **No lead duplication** — one lead never becomes N leads.
4. **No analytics fabrication** — historical metrics never invented.
5. **No broken URLs** — public URLs resolve or redirect per explicit policy.
6. **No broken favorites** — existing favorites stay meaningful.
7. **No subscription reassignment** — subscription stays account-scoped.
8. **No unauthorized ownership change** — listings stay on the correct account.
9. **No silent ambiguity resolution** — ambiguous rows are *flagged*, not guessed.
10. **Reversible** — the operation can be rolled back without loss.

---

## 17. Migration Readiness Criteria

A business is **READY** only when:
- the read-only analysis runs without error, AND
- there are **zero blocking ambiguities** (see §18), AND
- all §16 invariants are pre-verified on a staging clone.

Otherwise the report returns **NOT READY** with explicit blocking reasons.

---

## 18. Explicit Unresolved Decisions (do NOT hide these)

| # | Decision needed | Owner |
|---|---|---|
| 1 | Review attribution (parent vs per-location; add `reviews.branch_id`?) | Product |
| 2 | Services: business-wide vs per-location | Product |
| 3 | Categories: identity vs per-location discovery | Product |
| 4 | Media: which gallery images are location-specific | Product |
| 5 | Contacts: shared vs per-location (beyond Branch fields) | Product |
| 6 | NULL-branch historical leads disposition | Product |
| 7 | Analytics: parent-only vs (impossible) split | Product |
| 8 | Favorites: parent-follow vs user-chosen location | Product |
| 9 | Coupons: branch-limited offers as a concept | Product |
| 10 | Legacy slug: redirect vs multi-location page | Product |
| 11 | `max_branches`: retire or fold into listing limit | Product + Eng |

---

## 19. Deferred Implementation Work (intentionally untouched in Phase 4)

Professional/Store UI · product catalogue · e-commerce · Brand/Organization ·
ListingManager/team UI · new homepage · major search UI · new design system ·
full Branch→Listing migration · new public listing routes · production migration ·
pricing/plan-name changes · replacing `max_businesses`.

---

## 20. Quota Compatibility Note (Phase 3 carry-over)

`Business` is the only **concrete** listing today, so `max_businesses`
currently acts as the effective listing quota. This is a **transitional**
relationship, **not** an immutable law: the architecture must remain capable of
`5 listings = 2 Business + 2 Professional + 1 Store` without redesigning
subscriptions. No plan fields were renamed in Phase 4.
