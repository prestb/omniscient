# Phase 25 — Connection & Interaction Completion

**Status: AUDIT + ONE CONFIRMED DEFECT FIXED.** The connection layer was audited
end to end at `1509ecd`. Almost all of it was already correct and was **left
untouched**. One broken user-facing surface was removed.

---

## 1. The connection model

```text
DISCOVER -> LISTING -> CONNECT
                        |-- Contact  (Listing-owned)
                        |-- Inquiry  (Listing-attributed)
                        |-- Reply    (Listing-attributed, email)
                        |-- Favorite (User + Listing)
                        `-- Review   (Listing-owned)
```

**The Listing is the connection target.** A Business is displayed as context and
never owns a connection action.

## 2. Audit matrix

| Capability | Classification | Evidence |
|---|---|---|
| Favorites | **VERIFIED COMPLETE** | `FavoriteController::toggle` keyed on `user_id` + `listing_id`; `favorites_user_id_listing_id_unique` UNIQUE index; 401 JSON for guests; `FavoriteListingOwnershipTest` |
| Contact analytics | **VERIFIED COMPLETE** | `analytics.listing.track-view` / `track-click/{type}` are Listing-scoped; the four Business-keyed tracking routes are retired; `ConnectionContinuityTest` asserts attribution and that an organization-owned click creates no Listing analytics |
| Inquiries | **VERIFIED COMPLETE** | `POST /listing/{listing:slug}/contact`; `leads.listing_id` authoritative, `business_id` optional context; the former `POST /business/{business:slug}/contact` is RETIRED (a Business owning many Listings could not attribute an inquiry) |
| Replies | **VERIFIED COMPLETE** | `Owner\ListingLeadController::reply` emails `$lead->email` via `NotificationMail` and stamps `replied_at` + `status = replied` **only after the transport accepts**; no email address means no reply is possible and nothing is stamped; a transport failure marks nothing replied |
| Reviews | **VERIFIED COMPLETE** | Listing-owned since PHASE 21C-R1; routes are `listing/{listing:slug}/reviews`; Business reputation derived; approved-only aggregates shared with `/search` (PHASE 23J) |
| Notifications | **VERIFIED COMPLETE** | Inquiry creation notifies the Listing owner through `NotificationHelper`; owner/admin notification centres exist with dropdown/count/unread/read/preferences |
| Analytics | **VERIFIED COMPLETE** | Existing contact events are Listing-attributed; no expansion attempted (Phase 28) |
| Business-less Professional | **VERIFIED COMPLETE** | `InquiryEntitlementTest`, `ListingInquiryOwnerTest`, `ListingInquiryTest`, `PublicConnectionAndQuotaUxTest` all cover `business_id = NULL` |
| Multi-Listing isolation | **VERIFIED COMPLETE** | `ConnectionContinuityTest`, `ListingInquiryOwnerTest`, `ListingMediaEndToEndTest` prove per-Listing attribution |
| Authorization | **VERIFIED COMPLETE** | Owner routes bind `{listing}` and authorize through `Listing.owner_id`; cross-owner and sibling-Listing attempts fail |
| Privacy | **VERIFIED COMPLETE** | Public payloads carry no inquiry sender data, internal notes or owner responses; owner-only fields stay in owner routes |
| Entitlements | **VERIFIED COMPLETE** | `lead_capture` is account-scoped and resolved from the Listing owner; Favorites and Reviews are not paid-gated |
| Routes | **ONE DEFECT (fixed)** | see §3 |
| ~~`User/Reviews/Index`~~ | **NOT IMPLEMENTED** -> removed; see §3 |

## 3. The one confirmed defect

`routes/web.php` exposed:

```php
Route::get('/user/reviews', fn () => Inertia::render('User/Reviews/Index'))
    ->name('reviews.index');
```

and `resources/js/Components/Navbar.vue` rendered a **"My Reviews"** link to
`/user/reviews` for every `role === 'user'` account.

`resources/js/Pages/User/Reviews/Index.vue` **has never existed** — it is absent
from the working tree and from the entire git history (`git log --all` on that
path returns nothing). Any user clicking the link reached a component-resolution
error. The route was therefore executable, user-facing and permanently broken.

**Fix:** the dead route and the link that pointed at it were removed. No
replacement page was invented: the surface was never implemented, and building it
would be a new product feature (what to show, ordering, pagination, empty state)
rather than the completion of existing behaviour.

**Classification: NOT IMPLEMENTED, deferred.** A "My Reviews" surface listing the
reviews an account has written remains a reasonable future feature; it is recorded
here so it is a deliberate decision rather than a silently dead link.

## 4. What was deliberately NOT changed

* Favorite storage, toggle or UI — already correct and Listing-owned.
* Contact analytics — already Listing-attributed.
* Inquiry, reply and review ownership — already Listing-owned.
* Notification architecture — already sufficient; no chat system invented.
* Lead status vocabulary — the real states are `new`, `read`, `replied`,
  `archived`; `status = replied` with `replied_at = NULL` remains permitted when
  an owner changes status manually, which is deliberate and unchanged.
* Location vs Listing contact semantics — unchanged.
* Entitlement gates — unchanged; no Business-based lead entitlement added.

## 5. Relationship between the two processing models

The product implements **one-way inquiry + owner response**, not real-time chat:
an inquiry stores the sender's chosen contact methods, the owner is notified,
the owner replies by email, and `replied_at` records that the transport accepted
the message. Nothing in the repository demonstrates a messaging/thread model, and
none was added.

## 6. Remaining limitations

1. A visitor cannot see the reply in-product; delivery is email only, which is the
   established model.
2. A "My Reviews" page for the reviewing account does not exist (§3).
3. Two further routes render Vue pages that have never existed —
   `Admin/Categories/Tree` (`admin.categories.tree`, **zero references**, admin
   taxonomy) and `Notifications/Index` (generic `notifications.index`, **zero
   references**). Both are unreachable from the UI and lie outside the connection
   layer; they are recorded here and **deferred** rather than changed, because
   Phase 25 must not redesign Admin or taxonomy surfaces.
4. Analytics expansion (inquiry/favorite/review events) belongs to Phase 28.

## 7. Business-scoped leads inbox — classified, NOT changed

`Owner\LeadController` + `owner/businesses/{business}/leads/*` is a
**Business-scoped aggregate inbox** over the leads of that organization's
Listings. It authorizes with `Business.owner_id` and scopes by
`leads.business_id`, and its own docblock states that `listing_id` is display
information only.

It was examined as a possible stale Business-scoped connection path and
**cleared**, because the evidence shows it is deliberate and live:

* `OwnerBusinessContextTest` covers it directly — sibling-business isolation,
  cross-owner 403s on show/status/notes/delete, and "another owner's business
  cannot be accessed for leads";
* `ListingInquiryOwnerTest` asserts the route EXISTS;
* `resources/js/Pages/Owner/Leads/Index.vue` renders it via a `basePath`;
* the canonical Listing-scoped family (`owner/listings/{listing}/leads/*`) exists
  alongside it and is separately tested.

**Classification: LEGACY / COMPATIBILITY — a deliberate organization-level view,
not a defect.** The one observation worth recording is that a business-backed
Listing whose owner differs from the Business owner is reachable through BOTH the
Listing authority and the Business authority; each path is internally authorized
and covered by tests, so this is a dual-view design rather than a hole. Removing
it would break its tests and its UI, and Phase 25 must not redesign the owner
surface.

## 8. Connection sweep

| Pattern | Result |
|---|---|
| `business/{business}/reviews`, `business/{business}/contact` | **0** — retired |
| `primaryListing` | **0** |
| `lead.business`, `favorite.business` | **0** |
| `review.business` | 2 — a **fallback** in `ReviewCardCompact.vue` (`review.listing?.name || review.business?.name || 'Listing'`); inert when absent → COMPATIBILITY |
| `business.reviews_count` | 9 — the organization's **derived** aggregate on Business payloads → LEGITIMATE CONTEXT |
| `Branch` | ~100 — all local variable/parameter names for a `Location`; no Branch entity, no Branch route, no Branch quota → HISTORICAL NAMING |