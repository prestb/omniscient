# Phase 30 — Security, Accessibility & Mobile Readiness

**Status: VERIFIED for the defects fixed below.** Two confirmed defects were found
and fixed with regression tests. Everything else audited is classified explicitly,
and anything that could not be tested is disclosed rather than claimed.

---

## 1. Baseline

```text
branch          : phase-11-resume
HEAD            : 245c49059c49167da6ed46d1fc4de1c1ea8bd99e
working tree    : CLEAN
unpushed        : 29
```

Stack: Laravel 11, Inertia, Vue 3, Tailwind. Real MariaDB 10.4.32 and real
Meilisearch were used for every test run.

## 2. Security audit findings

### Verified sound (execution paths traced, not assumed)

| Area | Evidence |
|---|---|
| Role boundary | `CheckRole` gates `admin`/`super_admin`; the Phase 27 defect (admin satisfying `role:super_admin`) is fixed and pinned by 14 tests |
| Admin/super-admin | `SuperAdminActionsController` and `ImpersonationController` carry explicit `isSuperAdmin()` → `abort(403)` guards; `SuperDashboardController` does too |
| Route protection | `/admin` sits inside `auth` + `role:admin,super_admin`; `/owner` inside `auth` + `role:owner`; account routes inside `auth` |
| Auth throttling | `throttle:register`, `throttle:auth` (login), `throttle:password-email`, `throttle:password-reset`, plus `throttle:6,1` on verification routes |
| Action throttling | `throttle:search`, `:lead`, `:review`, `:analytics`, `:contact`, `:favorites`, `:coupon-token`, `:redemption-confirm`, `:impersonate`, `:admin-actions` |
| CSRF | `VerifyCsrfToken` present; no exclusions for state-changing web routes |
| Session/cookies | `SESSION_ENCRYPT=false` is the Laravel default; cookies are encrypted by `encryptCookies`; `SESSION_DRIVER=database` |
| Transport | `ForceHttps` middleware registered |
| Impersonation | `BlockImpersonatedAdmins` middleware registered |
| Security headers | `SecurityHeaders` appended globally (`bootstrap/app.php:62`) |
| Mass assignment | **No model uses `$guarded = []`**; every controller used `validated()` rather than `all()` |
| Public exposure | Phase 25 established that public payloads carry no inquiry sender data, notes or replies; no new public surface was added |
| Listing authorization | Owner/admin Listing access resolves through `Listing.owner_id` (Phase 28C pinned it with 12 tests) |

### CONFIRMED DEFECT — sensitive push credentials written to the application log

`Api\PushSubscriptionController::subscribe` logged the entire request payload:

```php
Log::info('Push subscription request received', [
    'user_id' => auth()->id(),
    'data' => $request->all()          // endpoint + p256dh + auth keys
]);
```

The input *was* validated (so this is not mass assignment or injection), but the
payload contains the push **endpoint** and the **`p256dh` / `auth` keys** — the
credentials required to deliver push notifications to that user. Writing them into
`laravel.log` is unnecessary secret exposure: log files are widely readable, are
shipped to backups and are routinely shared when debugging.

**Fix:** log only non-sensitive metadata.

```php
'endpoint_host' => parse_url((string) $request->input('endpoint'), PHP_URL_HOST),
'has_keys'      => $request->filled('keys.p256dh') && $request->filled('keys.auth'),
```

### CONFIRMED DEFECT — internal exception messages returned to the client

Both handlers returned `'Failed to subscribe: ' . $e->getMessage()` (and the same
for unsubscribe). That leaks internal error text — SQL, class names, paths — to an
unauthenticated-adjacent surface.

**Fix:** return a generic message; the detail remains in the server log, where it
already was.

### ACCEPTABLE EXISTING DESIGN (with caveats, not defects)

* **`SecurityHeaders` applies only outside `local`.** Production responses get
  `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
  `Permissions-Policy`, `Strict-Transport-Security` and a CSP. Withholding them in
  local development is a deliberate convenience and is not a production exposure.
* **The CSP allows `'unsafe-inline'` for `script-src` and `style-src`.** This is
  required by the Inertia/Vite inline bootstrap and is a real weakening of CSP, but
  tightening it needs nonce plumbing across the whole app — a larger change than
  this wave allows. Recorded as a deferred hardening item.
* **`X-XSS-Protection`** is deprecated and ignored by modern browsers; harmless,
  left as-is rather than churned.
* **`PushSubscriptionController` catches `Throwable` broadly.** The catch now logs
  the trace and returns a generic message; the `abort`-free flow is intentional.

### THEORETICAL / DEFERRED

* No 2FA, no login-notification email, no password-breach check — none is a
  verified defect and each is a product decision, not a fix.
* `PushSubscription` has no explicit throttle, but the route sits behind `auth` and
  the endpoint is unique per user, so unbounded growth is not demonstrated.

## 3. Accessibility and responsive-usability findings

### CONFIRMED ACCESSIBILITY DEFECT — inputs without an accessible name

WCAG 2.2 AA **4.1.2 Name, Role, Value** (and 3.3.2 Labels or Instructions) requires
every user-facing control to have an accessible name. Five page-level surfaces
rendered visible inputs identified **only by a placeholder**, or with nothing at all:

| File | Control | Before |
|---|---|---|
| `Pages/Admin/Locations/Index.vue` | location search | placeholder only |
| `Pages/Admin/Subscriptions/Index.vue` | subscription search | placeholder only |
| `Pages/Admin/Subscriptions/Index.vue` | `date_from` / `date_to` | **no name at all** |
| `Pages/Owner/Images/Index.vue` | logo / cover file inputs | **no name at all** |
| `Pages/Admin/SuperDashboard.vue` | maintenance bypass URL (readonly) | **no name at all** |
| `Components/Public/SearchAutocomplete.vue` | public search | placeholder only |

Placeholders are not a reliable accessible name: they disappear on input, are not
announced consistently, and fail 4.1.2 for controls whose purpose is not inferable
from surrounding text. The file inputs and date inputs had **no** name in any form.

**Fix:** added explicit `aria-label` to each, and `role="combobox"` plus a bound
`:aria-label` to the public search input (which also needs the role to be announced
as an autocomplete control).

### Verified sound

* **Auth and account forms are correctly labelled.** `Login.vue`, `Register.vue`
  and the password-reset forms pair `<label for="…">` with matching `id`, and set
  `autocomplete` (`email`, `current-password`, …). `autofocus` is used on the first
  field only.
* **Focus visibility exists**: 29 components use `focus-visible`, and 323 `focus:`
  utilities apply ring/outline styling to interactive controls.
* **Responsive coverage is broad**: 775 `sm:`/`md:`/`lg:` utility occurrences, and
  28 `overflow-x-auto` wrappers, so wide tables scroll rather than break the layout
  — including the admin tables added in Phases 27 and 29 and the per-Listing
  analytics table added in Wave 28C.
* `Pages/Owner/Listings/Index.vue` flagged by the scan is a **false positive**: its
  inputs are `type="hidden"` CSRF tokens, not user-facing controls.

### Not tested — disclosed

* **No browser-based accessibility audit was run** (no axe/Lighthouse/Pa11y
  available in this environment), so **no WCAG conformance is claimed**. The
  findings above are source-level, and the fixes are source-level.
* **Colour contrast was not measured.** It needs rendered output; no contrast claim
  is made either way.
* **No device/emulator responsive testing was performed.** Responsive conclusions
  rest on the utility-class audit, not on rendered viewports.
* Keyboard traversal order and screen-reader announcement were not exercised.

## 4. Changes made

```text
app/Http/Controllers/Api/PushSubscriptionController.php   redacted logging; generic error messages
resources/js/Pages/Admin/Locations/Index.vue              aria-label
resources/js/Pages/Admin/Subscriptions/Index.vue          aria-label x3
resources/js/Pages/Owner/Images/Index.vue                 aria-label x2
resources/js/Components/Public/SearchAutocomplete.vue     role=combobox + :aria-label
resources/js/Pages/Admin/SuperDashboard.vue               aria-label
tests/Feature/Security/SecurityAndAccessibilityTest.php   NEW — 5 regression tests
```

No architecture was changed: Listing-first discovery, Business-as-organization,
account-owned subscriptions, Listing-specific category ownership, account and
per-Listing analytics, Meilisearch integration and ranking, Location semantics, and
the connection/review/inquiry/notification/SEO contracts are all untouched. No new
dependency, framework, database or tracking system was introduced.

## 5. Regression tests

`tests/Feature/Security/SecurityAndAccessibilityTest.php` — **5 tests, 9 assertions,
all passing**:

1. The push endpoint **never logs** push credentials (`Log::spy()` +
   `shouldNotHaveReceived`, asserting no log context contains the endpoint, `p256dh`
   or `auth` values).
2. Push handlers do **not** return internal exception detail (and do return the
   generic messages).
3. An unauthenticated push subscription is refused (`401`).
4. **Invariant:** every page-level visible input has an accessible name — this
   enumerates `resources/js/Pages/**/*.vue`, ignores `type="hidden"`, and accepts
   `aria-label`, `aria-labelledby` or a `<label>` element. It fails if any future
   page adds an unlabelled visible control.
5. The public search combobox exposes `role="combobox"` and a bound `:aria-label`.

## 6. Limitations

* Source-level accessibility verification only; **no WCAG conformance is claimed**
  and no audit tool was available.
* Contrast, keyboard order and screen-reader behaviour were not exercised.
* No responsive rendering test; conclusions are class-audit based.
* CSP still permits `'unsafe-inline'` for scripts and styles (deferred).
* Security headers remain production-only by design.
* The pushed-notification *delivery* path was not exercised — only the subscription
  handler that was changed.

## 7. Deferred / remaining risk

| Item | Class |
|---|---|
| CSP `'unsafe-inline'` for scripts/styles | THEORETICAL / deferred hardening |
| No 2FA or breached-password check | THEORETICAL / product decision |
| Accessibility needs a real browser audit (axe/Lighthouse) before any conformance claim | DEFERRED |
| Contrast and keyboard traversal unverified | DEFERRED |
