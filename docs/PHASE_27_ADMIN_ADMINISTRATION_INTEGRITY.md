# Phase 27 — Admin Administration Integrity & Capability Completion

**Status: DELIVERED.** The Phase 27 audit named two authorization/integrity
defects (P0) and four capability gaps (P1). The P0 defects are fixed and pinned by
tests; the P1 items are delivered except where noted in §10.

---

## 1. Initial commit and repository state

```text
branch          : phase-11-resume
HEAD            : 61bd9fccd5c9a91c7ee13015df9e96004dfdeb20
working tree    : CLEAN
unpushed commits: 25
```

The checkout **matched the audit baseline exactly**, so every audit finding applied
verbatim. Nothing was reset, rebased, switched or overwritten.

## 2. The authorization defect and its fix

`app/Http/Middleware/CheckRole.php` short-circuited **before** the declared-role
check:

```php
if ($user->role === 'super_admin') { return $next($request); }
if ($user->role === 'admin')       { return $next($request); }   // granted EVERYTHING
if (!in_array($user->role, $roles)) { /* … */ }
```

Because `admin` returned early, it also satisfied `role:super_admin`, so the nested
super-admin route group was **not actually protected**.

**Fix.** `super_admin` still has full access (unchanged). `admin` now keeps full
access *except* where a route is restricted to super admins — i.e. it declares
`super_admin` and does **not** also accept `admin`:

```php
if ($user->role === 'admin') {
    $superAdminOnly = in_array('super_admin', $roles, true)
        && ! in_array('admin', $roles, true);

    if ($superAdminOnly) { abort(403, 'Unauthorized access.'); }

    return $next($request);
}
```

This is deliberately narrow. All five `role:` groups were checked:

| Group | Effect after the fix |
|---|---|
| `role:admin,super_admin` (route 605) | admin ✅, super_admin ✅ — unchanged |
| `role:super_admin` (route 611) | super_admin ✅, **admin → 403** — the fix |
| `role:owner` (route 463) | admin ✅ (preserved contract), owner ✅ |
| `role:user,owner` (route 297) | unchanged |
| `role:user,admin,super_admin` (route 277) | unchanged |

Owner and user redirects are untouched.

`SuperDashboardController::index()` also gained a direct
`isSuperAdmin()` → `abort(403)` guard, matching the defensive checks already in
`SuperAdminActionsController` and `ImpersonationController`, so a future middleware
change cannot silently re-expose the platform-wide dashboard. Its payload is
unchanged.

## 3. Subscription deletion-guard correction

`Admin\UserController::destroy` guarded on **Business-scoped billing**:

```php
$user->businesses()->whereHas('subscriptions', fn ($q) => $q->where('status','active'))->exists();
```

That model was removed in PHASE 24-R1, so the guard was both stale and wrong: an
account with an **active subscription of its own but no Business** passed it and
could be deleted, taking its paid entitlement, Listings and Locations with it.

**New guard** — the account's own canonical contract, which already encodes the
entitling statuses (`active` / `expiring_soon` / `grace_period`):

```php
if ($user->active_subscription !== null) {
    return redirect()->back()->with('error', 'Cannot delete an account with an active subscription.');
}
```

The last-super-admin safeguard is untouched, and deletion remains a soft delete. No
`subscriptions.business_id`, Business-scoped subscription or legacy relationship was
reintroduced.

## 4. Business deletion policy and safeguards

`Admin\BusinessController::destroy` was a bare `$business->delete()`. It removed an
organization from public view while its Listings stayed published and reachable at
`/listing/{slug}`, and it applied a **weaker** rule than the owner-side path, which
refuses the deletion while any of the organization's Locations is still used by a
Listing.

**Policy (now identical on both sides):** ordinary deletion is refused while the
organization has a Location that a Listing references —

```php
$locationsInUse = $business->locations()->whereHas('listings')->withCount('listings')->get();
```

— returning the established `error` flash, otherwise the soft delete proceeds.
Intentional **force deletion is untouched** and keeps its transactional cleanup and
Listing synchronization.

A Location is account-owned with only optional organization context, so the check
asks whether Listings *reference* it rather than assuming one Location belongs to
one Business. No new cascading database behaviour was introduced.

## 5. New dashboard metrics

`Admin\DashboardController` gained five KPIs, and `Admin/Dashboard.vue` two cards.
Definitions mirror the canonical visibility rule used by `/search`, the sitemap and
`Admin\ListingController` — `published` alone is **not** public, because `hidden_at`
also removes a Listing from discovery, and soft-deleted rows are excluded by the
global scope:

| KPI | Definition |
|---|---|
| `total_listings` | `Listing::count()` |
| `published_listings` | `status = published` **and** `hidden_at IS NULL` |
| `non_public_listings` | total − public |
| `total_locations` | `Location::count()` — physical places, not geography reference data |
| `locations_with_listings` | Locations with at least one Listing |

All are aggregate `COUNT` queries; no collections are loaded and no existing metric
or prop changed. Business records are never counted as Listings.

## 6. Physical Location administration

Geography pages manage reference data (Country/Region/City/Area); none of them
administered the `locations` table — the physical places Listings sit at.

Added a **read-only** inspection surface:

* **Route** `GET /admin/locations` → `admin.locations.index`, inside the existing
  `locations` prefix so it sits with its geography siblings.
* **Controller** `Admin\LocationController::index()`: paginated (20), searchable by
  Location name, address or owner name/email; filters by country, city and whether
  a Listing uses the Location; eager-loads owner, business, city, region, country
  and `withCount('listings')`.
* **Page** `resources/js/Pages/Admin/Locations/Index.vue`, using the existing
  `AuthenticatedLayout` + `PageHeader` + `Pagination` conventions, with an empty
  state.

**Ownership is presented correctly:** `owner` is the account that owns the place;
`business` is shown as *context* only and links to `/business/{slug}`. Coordinates,
city/region/country, hidden state and the dependent-Listing count are surfaced. No
moderation action was added, because the existing architecture establishes none for
places, and no private account data beyond the owning account is exposed.

## 7. Added regression coverage

| File | Tests | Proves |
|---|---|---|
| `tests/Feature/Admin/AdminAuthorizationTest.php` | 14 | admin ✅ / super-admin ✅ on `/admin/dashboard`; super-admin ✅ and **admin 403** on `/admin/super-dashboard`; admin forbidden from super-admin actions; owner and user redirected from both; admin and super-admin retain owner-route access; account with an active subscription (even with **zero** Businesses) cannot be deleted; a grace-period subscription still protects; an account with no subscription follows the existing policy; last-super-admin intact |
| `tests/Feature/Admin/AdminOperationsTest.php` | 11 | dashboard KPIs against mixed fixtures (published/hidden/draft/soft-deleted) and Locations; Business deletion refused with a dependent Location; allowed without dependencies; approve → publish → suspend and reject persist; physical Location index renders, filters, and is forbidden to non-admins; geography routes intact |

Assertions are on **persisted state** and **authorization outcomes**, not merely on
a successful response.

## 8. Removed dead routes/pages

| Artifact | Action |
|---|---|
| `admin.categories.tree` route | **removed** — zero references; its Vue page has never existed |
| `CategoryController::tree()` | **removed** — unused once the route was gone |
| `resources/js/Pages/Admin/Payments/Create.vue` | **removed** — unrouted; the manual-payment flow was retired |
| `Subscriptions/Show.vue` "Record Payment" link | **removed** — it pointed at `/admin/payments/create`, a route that does not exist, so it was a **live broken admin link** |

The generic (non-admin) `notifications.index` dead route recorded in Phase 25 was
left alone: it is outside the admin surface.

## 9. Test results, build, incomplete checks

Tests were run **strictly sequentially** — one process at a time.

| Run | Result |
|---|---|
| `tests/Feature/Admin` | **39 passed** (102 assertions) |
| `tests/Feature/Admin/AdminAuthorizationTest` + `RoleTest` | 19 passed (36 assertions) |
| Full suite | recorded in the Phase 27 commit message |

```text
concurrent test processes : NO
SCOUT_DRIVER=null         : NO
phpunit.xml modified      : NO
real Meilisearch          : YES
```

`npm run build` — **client PASS, SSR PASS** (two admin Vue files changed). Generated
`public/build` churn was restored, not committed.

## 10. Remaining limitations and deferred work

1. **Listing moderation does not exist.** Admin Listings remains read-only (index +
   show). That is a product decision, not a defect, and §9 placed it out of scope.
2. **Audit logs, bulk moderation and a platform-health redesign** remain deferred;
   the repository proves none of them exists and nothing currently requires them.
3. The Business deletion safeguard mirrors the owner policy (Locations in use). A
   Business holding Listings with **no** Locations remains deletable by both the
   owner and an admin — a deliberate consistency choice, recorded here.
4. Force deletion of a Business remains a distinct, explicit, transactional action.
5. Physical Location administration is **inspection only**; no moderation lifecycle
   was invented for places.
