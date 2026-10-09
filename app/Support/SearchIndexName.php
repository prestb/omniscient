<?php

namespace App\Support;

use RuntimeException;

/**
 * PHASE 23C-R4 — THE SINGLE SOURCE OF TRUTH FOR MEILISEARCH INDEX NAMES.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE DEFECT THIS CLASS EXISTS TO CLOSE
 * ─────────────────────────────────────────────────────────────────────────
 * Scout resolves an index name as `config('scout.prefix') . $model->getTable()`
 * (vendor/laravel/scout/src/Searchable.php). `Listing` overrode `searchableAs()`
 * with a bare literal `'listings'`, which SILENTLY DISCARDED `scout.prefix`:
 * setting `SCOUT_PREFIX` alone changed nothing for the one entity that is
 * actually searchable. `ConfigureMeilisearch` hardcoded the same literal and
 * talked to a RAW Meilisearch client, so it never consulted Scout at all.
 *
 * The result was that a test run and the development application shared the
 * `listings` / `categories` indexes.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE CONTRACT
 * ─────────────────────────────────────────────────────────────────────────
 *   prefix unset   -> listing()  = 'listings'      category() = 'categories'
 *   SCOUT_PREFIX=test_ -> listing() = 'test_listings'  category() = 'test_categories'
 *
 * The prefix is read from `config('scout.prefix')` — the SAME value Scout
 * itself uses — so there is exactly one place that decides which indexes this
 * application talks to. Every index name in `app/` must come from here.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY `assertSafeForMutation()` IS FAIL-CLOSED
 * ─────────────────────────────────────────────────────────────────────────
 * The ordering trap: a test bootstrap may only write index SETTINGS to the
 * test index. If it resolved to `listings` instead, it would RECONFIGURE THE
 * DEVELOPMENT INDEX — the exact failure this phase exists to prevent, and one
 * that is invisible because the write succeeds.
 *
 * A name check such as `str_starts_with($index, $prefix)` is therefore NOT
 * enough: an empty prefix would make `'listings'` look "prefixed". Under
 * `APP_ENV=testing` the guard instead requires the COMPLETE resolved name to
 * be one this application legitimately manages — and explicitly rejects the
 * development names, no matter what the prefix is. If the prefix is missing
 * or wrong, the resolved name *is* a development name and the guard refuses.
 */
final class SearchIndexName
{
    /** The Listing discovery index (base name, before any prefix). */
    public const LISTINGS = 'listings';

    /** The Category index (base name, before any prefix). */
    public const CATEGORIES = 'categories';

    /**
     * Index names owned by the DEVELOPMENT application.
     *
     * A test run must never mutate these. They are rejected by name, not by
     * absence of a prefix, so `'listings'` stays rejected even if someone
     * later "fixes" the prefix to an empty string.
     */
    public const DEVELOPMENT_INDEXES = [self::LISTINGS, self::CATEGORIES];

    /** The index the Listing model is indexed into and searched from. */
    public static function listing(): string
    {
        return self::resolve(self::LISTINGS);
    }

    /** The index the Category model is indexed into and searched from. */
    public static function category(): string
    {
        return self::resolve(self::CATEGORIES);
    }

    /**
     * Apply the configured prefix to a base index name.
     *
     * Deliberately the ONLY place a prefix is applied, so a model cannot
     * discard it again by writing a literal.
     */
    public static function resolve(string $base): string
    {
        return self::prefix() . $base;
    }

    /** The configured Scout prefix. Empty when none is configured. */
    public static function prefix(): string
    {
        return (string) config('scout.prefix', '');
    }

    /**
     * Every index this application may legitimately mutate in the current
     * configuration. In development these ARE the development indexes; the
     * guard below is what makes that safe to state.
     *
     * @return list<string>
     */
    public static function managedIndexes(): array
    {
        return [self::listing(), self::category()];
    }

    /** Whether the application is running in the test environment. */
    public static function isTesting(): bool
    {
        return app()->environment('testing');
    }

    /**
     * Fail closed before any index is mutated.
     *
     * Call this BEFORE a Meilisearch client is constructed: the point is that a
     * rejected name must never reach the network.
     *
     * Outside `APP_ENV=testing` the development indexes are the legitimate
     * target (`php artisan meilisearch:configure`), so only the universal
     * checks apply. Inside `APP_ENV=testing` the guard is strict:
     *
     *   1. an empty name is always refused;
     *   2. a development index name is refused — prefix presence is NOT proof
     *      of safety, the resolved name is;
     *   3. anything that is not a name this configuration manages is refused,
     *      so a typo or a newly added model cannot silently target a stray
     *      index.
     *
     * There is deliberately NO override. An escape hatch would have to admit a
     * name this guard exists to reject, and would therefore be either
     * redundant or dangerous.
     *
     * @throws RuntimeException
     */
    public static function assertSafeForMutation(string $index): void
    {
        if (trim($index) === '') {
            throw new RuntimeException(
                'PHASE 23C-R4: refusing to mutate an empty Meilisearch index name. '
                . 'Resolve it through App\Support\SearchIndexName.'
            );
        }

        if (! self::isTesting()) {
            return;
        }

        if (in_array($index, self::DEVELOPMENT_INDEXES, true)) {
            throw new RuntimeException(
                "PHASE 23C-R4: refusing to mutate the development index '{$index}' while "
                . 'APP_ENV=testing. A test run must never reconfigure or write to the '
                . 'development indexes. Set SCOUT_PREFIX so the RESOLVED index name is a '
                . 'test index — a prefix is what makes the name safe, not an assumption.'
            );
        }

        if (! in_array($index, self::managedIndexes(), true)) {
            throw new RuntimeException(
                "PHASE 23C-R4: refusing to mutate '{$index}' while APP_ENV=testing: it is "
                . 'not one of the resolved test indexes ['
                . implode(', ', self::managedIndexes()) . ']. '
                . 'A name that merely looks prefixed is not proof of safety.'
            );
        }
    }
}
