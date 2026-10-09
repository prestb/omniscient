# Phase 22D — Test Environment Isolation

**Status: VERIFIED — one hardening applied.**

This phase was **already implemented** in commit `fc504d8` ("fix: isolate test database
from development"), which is an ancestor of the current HEAD. Per the phase's own
standing rule — *do not implement something that already exists* — this pass did not
rebuild it. It audited what was there, measured it, found one demonstrated hole, and
closed it.

---

## 1. What already existed, and what this pass added

| | Existing (`fc504d8`) | Added by this pass |
|---|---|---|
| Test database pin | `phpunit.xml` pins `DB_CONNECTION=mysql`, `DB_DATABASE=omniscient_testing` | — |
| Fail-closed guard | `tests/TestCase.php::guardUnsafeTestDatabase()` | **hardened** to check *every* environment source |
| Isolation tests | `tests/Unit/TestingDatabaseIsolationTest.php` (4 tests) | **+3 tests**, incl. the live connection and the divergence case |
| Documentation | *missing* | this file |
| `.env.testing` | does not exist (gitignored) | **deliberately still absent** — see §7 |

The pin, the guard and the tests were all sound. The hole was narrower and more
interesting than the phase brief anticipated.

---

## 2. How the database was resolved before Phase 22D

`phpunit.xml` set `APP_ENV=testing` but **no `DB_*` overrides**, and `.env.testing` does
not exist. Laravel therefore loaded `.env`, whose `DB_DATABASE=omniscient`, and
`RefreshDatabase`'s `migrate:fresh` — which drops every table in the connected database —
pointed at the **development** database. `config/database.php` compounds this: the mysql
connection's fallback is `env('DB_DATABASE', 'omniscient')`, so even an *absent* variable
resolves to the development name rather than failing.

---

## 3. Problem found by this pass

> **The pin was not deterministic, and the obvious fix for that is dangerous.**

**3a. The pin could be overridden.** PHPUnit only writes an `<env>` value when it is
absent (`vendor/phpunit/phpunit/src/TextUI/Configuration/PhpHandler.php`):

```php
if ($force || getenv($name) === false) { putenv("{$name}={$value}"); }
if ($force || ! isset($_ENV[$name]))   { $_ENV[$name] = $value; }
```

Measured: with `DB_DATABASE=decoy_testing` exported, `php artisan test` resolved to
**`decoy_testing`**, not the pinned `omniscient_testing`. The suite's own isolation test
caught it — but the *pin* had not won.

**3b. `force="true"` makes it worse, catastrophically.** Adding `force="true"` to
`DB_DATABASE` writes the pinned name into `$_ENV` and `putenv()` while `$_SERVER` keeps
the ambient value. Laravel's immutable Dotenv repository reads `$_SERVER` **before**
`$_ENV`, so:

* the application used the **ambient** value;
* the guard read `$_ENV` first, saw the pinned `_testing` name, and **passed**.

Measured with `DB_DATABASE=omniscient` exported and `force="true"` in place:

```
guard:       did NOT fire
application: resolved to 'omniscient'      <-- the DEVELOPMENT database
```

`RefreshDatabase` would have run `migrate:fresh` against the development database.
**`force="true"` was reverted and is deliberately NOT used for the DB values.**

---

## 4. The isolation mechanism as it now stands

Two layers, one source of truth each, no duplication:

1. **`phpunit.xml`** pins `DB_CONNECTION=mysql` and `DB_DATABASE=omniscient_testing`
   (unforced — see §3b). Host, username and password are deliberately left to the
   environment so CI keeps `ci_user` from `.env.ci`.
2. **`tests/TestCase.php`** fails closed *before* `parent::setUp()` boots the app and
   therefore before any migration can run.

The guard now checks **every** source the application could read, not the first one
found:

```php
$sources = [
    '$_ENV'    => $_ENV['DB_DATABASE'] ?? null,
    '$_SERVER' => $_SERVER['DB_DATABASE'] ?? null,
    'getenv()' => getenv('DB_DATABASE') === false ? null : getenv('DB_DATABASE'),
];
```

If **any** source names a database that does not end in `_testing` — or if none names one
at all — the suite aborts. Because those are the only sources the application resolves
from, this makes it impossible for a test run to reach the development database, whatever
the environment does. Verified by re-running the §3b scenario with `force="true"`
temporarily restored: the guard now fires and all 7 isolation tests fail closed instead of
letting `migrate:fresh` loose on `omniscient`.

---

## 5. Local and CI test databases

```
LOCAL TESTS
    phpunit.xml  (DB_CONNECTION=mysql, DB_DATABASE=omniscient_testing)
        -> tests/TestCase.php guard
        -> omniscient_testing

CI TESTS
    .github/workflows/tests.yml : cp .env.ci .env
    .env.ci                     (omniscient_testing, ci_user / ci_password)
    phpunit.xml                 (same pin as local)
        -> tests/TestCase.php guard
        -> omniscient_testing
```

Both paths converge on the same database name, so there is **no competing CI database
configuration**. CI is unchanged by this phase.

---

## 6. How Laravel / PHPUnit load the configuration

Deterministic order:

1. `php artisan test` launches PHPUnit.
2. PHPUnit reads `phpunit.xml` and applies every `<env>` entry into `putenv()` and
   `$_ENV` — **before** the bootstrap file runs.
3. `tests/bootstrap.php` runs; it boots only `LoadEnvironmentVariables` +
   `LoadConfiguration`.
4. `LoadEnvironmentVariables` loads `.env` through Laravel's **immutable** Dotenv
   repository, which never overwrites a variable that is already present — which is why
   the PHPUnit pin survives `.env`.
5. `config/database.php` evaluates `env('DB_DATABASE', 'omniscient')`.
6. `Tests\TestCase::setUp()` runs the guard **before** `parent::setUp()`.

`env()` resolves through the immutable repository in the order
`$_SERVER` → `$_ENV` → `getenv()`. The guard's old `$_ENV`-first order disagreed with
that, which is the root cause of §3b.

---

## 7. Why `.env.testing` was rejected

`.env.testing` would be a serviceable mechanism, but not in this repository:

* it is **gitignored** (`.gitignore` line 7), so committing it would require weakening
  `.gitignore`, and leaving it untracked makes the test environment depend on a local
  file that CI and other developers do not have;
* Laravel loads `.env.testing` *instead of* `.env` when it exists, so its presence
  silently changes environment resolution for every test run;
* with `phpunit.xml`, `.env.ci` and `.env.testing` all naming a database, the brief's
  own prohibition on competing sources of truth ("phpunit.xml → database A,
  .env.testing → database B, .env.ci → database C") would be violated.

The existing Laravel-native mechanism already satisfies the invariant. Adding
`.env.testing` would add a third source of truth and buy nothing.

---

## 8. Meilisearch

**Unchanged by this phase, and nothing was weakened.**

* `SCOUT_DRIVER` was **not** set to `null` anywhere. `.env` keeps
  `SCOUT_DRIVER=meilisearch` and the full suite runs against the real Meilisearch
  server.
* The suite writes to `test_listings` / `test_categories`, isolated by
  `SCOUT_PREFIX=test_` and `App\Support\SearchIndexName`. See
  `docs/PHASE_23_SEARCH_DISCOVERY_REFINEMENT.md` and `docs/PHASE_23C_R5_AUDIT.md`.
* CI runs a real Meilisearch service container (`getmeili/meilisearch:v1.53.1`), so the
  one test that performs a live search is exercised rather than skipped.
* Search infrastructure was not redesigned here. This phase is about the database.

---

## 9. Tests performed

| Suite | Result |
|---|---|
| `tests/Unit/TestingDatabaseIsolationTest.php` (focused) | **7 passed** (13 assertions) |
| `tests/Feature/Location` | **126 passed** (362 assertions) |
| `tests/Feature/Architecture` | **10 passed** (23 assertions) |
| `php artisan test` (full suite) | see §9a |

### 9a. Full suite

```
php artisan test
  → 1059 passed, 2 failed, 1 incomplete  (3672 assertions, 612.51s)
```

1056 passed before this phase + the 3 tests added here = 1059.

The two failures are `Tests\Feature\Images\ImagePipelineTest` — **pre-existing and
unrelated to Phase 22D** (the base image is stored but its generated variant is not).
They were reproduced at HEAD with this phase's changes stashed, and are documented in
`docs/PHASE_23C_R5_AUDIT.md` §4. The single incomplete test is the pre-existing
`ExampleTest` scaffold. No assertion was weakened and no test was skipped to reach this
result.

### 9b. Manual environment experiments (not part of the suite)

| Experiment | Expected | Observed |
|---|---|---|
| ambient `DB_DATABASE=decoy_testing` | resolved name deviates | resolved `decoy_testing` (pin lost) |
| ambient `DB_DATABASE=omniscient_testing_ambient` | guard rejects | guard rejected — aborted before any test |
| ambient `DB_DATABASE=omniscient` | guard rejects | guard rejected — aborted before any test |
| ambient `DB_DATABASE=omniscient` **+ `force="true"`** (pre-hardening) | guard must reject | **guard passed, app resolved to `omniscient`** — the hole |
| the same, **post-hardening** | guard rejects | guard rejects all 7 tests |

Only `tests/Unit/TestingDatabaseIsolationTest.php` was used for these runs: it extends
`Tests\TestCase` but deliberately does **not** use `RefreshDatabase`, so no migration
could run in any of them.

---

## 10. Evidence the development database was not targeted

A full per-table row-count fingerprint of `omniscient` was taken immediately before and
after the full suite (43 tables, `SELECT COUNT(*)` per table, hashed):

```
development DB fingerprint (before) sha256 = B65FEFC3828788EF2BFE2D5CB84FA1E5F153F4AB5032EBCA4F0081B9861471B3
development DB fingerprint (after)  sha256 = B65FEFC3828788EF2BFE2D5CB84FA1E5F153F4AB5032EBCA4F0081B9861471B3
comparison                                 = IDENTICAL — 43 tables, every row count unchanged
```

Two independent structural facts:

* **`omniscient.migrations` holds 73 rows while 74 migration files exist on disk.** A
  `migrate:fresh` repopulates that table from the files. The development database is one
  migration behind and has therefore **never been refreshed** by a test run.
* `omniscient_testing.migrations` holds 74 — the refreshed state — while
  `omniscient` still holds 73.

The live connection is asserted directly in
`test_the_live_connection_resolves_to_the_test_database()` via
`DB::connection()->getDatabaseName()`, so the guarantee is stated against the connection
the application actually queries through, not merely against a config array.

---

## 11. Remaining environment risks

1. **Tests that do not extend `Tests\TestCase` are not guarded.** Five files
   (`tests/Unit/ExampleTest.php`, and the four `tests/Unit/Support/*` value-object tests)
   extend `PHPUnit\Framework\TestCase` directly. All five have **zero** database
   references, and every Feature test reaches the guard through `tests/Pest.php`'s
   `pest()->extend(TestCase::class)`. The residual risk is that a future DB-touching test
   added to one of those five files would bypass the guard. Not fixed here, because
   booting the application for pure value-object tests is a real cost for no present
   benefit.
2. **The pin remains non-deterministic by design.** An ambient `DB_DATABASE` still wins
   over `phpunit.xml`. This is deliberate: `force="true"` closes the pin but blinds the
   guard (see §3b), which is strictly worse. Safety is enforced by the guard rather than
   by the pin. If a future change needs the pin itself to win, PHPUnit's `<env>` is the
   wrong tool — `$_SERVER` must be written too, and the guard must keep checking every
   source.
3. **The development database is one migration behind** (73 vs 74). It has therefore
   never run the missing migration. Not repaired here: Phase 22D forbids migrating,
   resetting or repairing the development database.
4. **CI could not be executed from this environment.** The workflow, its service
   containers and the `secrets` context are reviewed by reading. The first run on a
   branch the workflow triggers remains the real test.
5. **`config/database.php` defaults to the development name.** `env('DB_DATABASE',
   'omniscient')` means an absent variable resolves to `omniscient` rather than failing.
   The guard covers this — an absent name is rejected — but the default itself is a
   standing trap for any non-test code path.

---

## Appendix — reference classification

| Reference | Where | Class |
|---|---|---|
| `DB_DATABASE=omniscient` | `.env`, `.env.example`, `config/database.php` default | DEVELOPMENT |
| `DB_DATABASE=omniscient_testing` | `phpunit.xml`, `.env.ci`, `tests/TestCase.php` | TEST-ONLY / CI-ONLY |
| `DB_CONNECTION` pin | `phpunit.xml` | TEST-ONLY |
| `RefreshDatabase` | `tests/Pest.php` (Feature) + individual test classes | TEST-ONLY |
| `migrate:fresh` | Laravel's `RefreshDatabase` only; no test calls it directly | TEST-ONLY |
| `omniscient_testing` | `phpunit.xml`, `.env.ci`, `tests/Unit/TestingDatabaseIsolationTest.php`, `tests/TestCase.php`, `tests.yml` | TEST-ONLY / CI-ONLY |
| `SCOUT_DRIVER=meilisearch` | `.env`, `.env.ci` | LEGITIMATE — real Meilisearch kept |
| `SCOUT_DRIVER=database` | `.env.example`, `.env.production` | LEGITIMATE |
| `.env.testing` | absent, gitignored | deliberately NOT USED |
| `DB_PASSWORD` | `.env.production` | not tracked (gitignored) |
| `omniscient_testing_ambient`, `decoy_testing` | experiment only | not in the repository |

No reference remains that could let an unexplained test path resolve to the development
database.
