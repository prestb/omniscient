<?php

namespace Tests\Unit\Support;

use App\Console\Commands\ConfigureMeilisearch;
use App\Models\Category;
use App\Models\Listing;
use App\Support\SearchIndexName;
use RuntimeException;
use Tests\TestCase;

/**
 * PHASE 23C-R4 — MEILISEARCH TEST-INDEX ISOLATION.
 *
 * Before this phase three places disagreed about which index the application
 * talks to:
 *
 *   1. `Listing::searchableAs()` returned a bare `'listings'`, silently
 *      DISCARDING `scout.prefix` (Scout's default is
 *      `config('scout.prefix').$this->getTable()`);
 *   2. `ConfigureMeilisearch` held `const INDEX = 'listings'` and drove a RAW
 *      Meilisearch client, bypassing Scout entirely;
 *   3. `Category` declared no `searchableAs()` at all.
 *
 * These tests pin the replacement: ONE resolver, and a guard that must run
 * BEFORE a client exists.
 *
 * They deliberately do NOT use `RefreshDatabase` — nothing here touches the
 * database — and they never contact Meilisearch. The "no client call" proof is
 * a spy factory, not a live server.
 */
class SearchIndexNameTest extends TestCase
{
    // ═══ RESOLUTION ═════════════════════════════════════════════════════════

    /** Without a prefix, the development names are used — unchanged behaviour. */
    public function test_names_are_unprefixed_when_no_prefix_is_configured(): void
    {
        config(['scout.prefix' => '']);

        $this->assertSame('listings', SearchIndexName::listing());
        $this->assertSame('categories', SearchIndexName::category());
    }

    /** A test prefix yields the isolated test names. */
    public function test_names_are_prefixed_when_a_test_prefix_is_configured(): void
    {
        config(['scout.prefix' => 'test_']);

        $this->assertSame('test_listings', SearchIndexName::listing());
        $this->assertSame('test_categories', SearchIndexName::category());
    }

    /** The prefix comes from Scout's own config, so the two cannot disagree. */
    public function test_the_resolver_reads_scout_prefix(): void
    {
        config(['scout.prefix' => 'tenant7_']);

        $this->assertSame('tenant7_', SearchIndexName::prefix());
        $this->assertSame('tenant7_listings', SearchIndexName::listing());
        $this->assertSame('tenant7_categories', SearchIndexName::category());
    }

    /** Every managed index is exactly `prefix + base` — no literals anywhere. */
    public function test_managed_indexes_are_derived_from_the_prefix(): void
    {
        config(['scout.prefix' => 'test_']);

        $this->assertSame(
            ['test_listings', 'test_categories'],
            SearchIndexName::managedIndexes()
        );
    }

    // ═══ THE THREE DEFECT SITES NOW AGREE ═══════════════════════════════════

    /**
     * `Listing::searchableAs()` used to return a literal, which is precisely
     * how `scout.prefix` got discarded. It must now follow the prefix.
     */
    public function test_listing_searchable_as_honours_the_prefix(): void
    {
        config(['scout.prefix' => '']);

        $this->assertSame('listings', (new Listing())->searchableAs());

        config(['scout.prefix' => 'test_']);

        $this->assertSame('test_listings', (new Listing())->searchableAs());
        $this->assertSame(SearchIndexName::listing(), (new Listing())->searchableAs());
    }

    /** `Category` now declares its index explicitly instead of relying on a default. */
    public function test_category_searchable_as_honours_the_prefix(): void
    {
        config(['scout.prefix' => '']);

        $this->assertSame('categories', (new Category())->searchableAs());

        config(['scout.prefix' => 'test_']);

        $this->assertSame('test_categories', (new Category())->searchableAs());
        $this->assertSame(SearchIndexName::category(), (new Category())->searchableAs());
    }

    /** The command targets the resolved name, never a hardcoded literal. */
    public function test_the_command_targets_the_resolved_listing_index(): void
    {
        config(['scout.prefix' => '']);
        $this->assertSame('listings', ConfigureMeilisearch::targetIndex());

        config(['scout.prefix' => 'test_']);
        $this->assertSame('test_listings', ConfigureMeilisearch::targetIndex());
        $this->assertNotSame('listings', ConfigureMeilisearch::targetIndex());
    }

    // ═══ THE GUARD — FAIL CLOSED ════════════════════════════════════════════

    /** The development Listing index must never be mutated by a test run. */
    public function test_guard_rejects_the_development_listing_index(): void
    {
        $this->assertGuardRejects('listings');
    }

    /** The development Category index must never be mutated by a test run. */
    public function test_guard_rejects_the_development_category_index(): void
    {
        $this->assertGuardRejects('categories');
    }

    /** An empty name is refused in every environment. */
    public function test_guard_rejects_an_empty_name(): void
    {
        $this->assertGuardRejects('');
        $this->assertGuardRejects('   ');
    }

    /**
     * THE ORDERING TRAP, PINNED.
     *
     * With no prefix configured, the resolver itself produces a development
     * name. The guard must catch that — "it came from the resolver" is not a
     * licence to write to a shared index.
     */
    public function test_guard_rejects_the_resolved_name_when_no_prefix_is_configured(): void
    {
        config(['scout.prefix' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHASE 23C-R4');

        SearchIndexName::assertSafeForMutation(SearchIndexName::listing());
    }

    /**
     * A prefixed name is not automatically safe. "Prefix presence is proof"
     * would allow any typo to create a stray index; the guard requires the
     * COMPLETE name to be one this configuration manages.
     */
    public function test_guard_rejects_an_unknown_index_even_when_it_is_prefixed(): void
    {
        config(['scout.prefix' => 'test_']);

        $this->assertGuardRejects('test_businesses');
        $this->assertGuardRejects('test_listings_archive');
        $this->assertGuardRejects('listings2');
    }

    /** The resolved test indexes are accepted. */
    public function test_guard_accepts_the_resolved_test_indexes(): void
    {
        config(['scout.prefix' => 'test_']);

        SearchIndexName::assertSafeForMutation(SearchIndexName::listing());
        SearchIndexName::assertSafeForMutation(SearchIndexName::category());

        // Reaching here is the assertion: neither call threw.
        $this->addToAssertionCount(1);
    }

    /**
     * Outside `APP_ENV=testing` the development indexes ARE the legitimate
     * target: `php artisan meilisearch:configure` must keep working locally.
     */
    public function test_guard_is_inert_outside_the_test_environment(): void
    {
        $this->app->instance('env', 'local');

        $this->assertFalse(SearchIndexName::isTesting());

        SearchIndexName::assertSafeForMutation('listings');
        SearchIndexName::assertSafeForMutation('categories');

        $this->addToAssertionCount(1);
    }

    // ═══ THE GUARD RUNS BEFORE THE CLIENT IS CONSTRUCTED ════════════════════

    /**
     * A rejected name must never reach the network. The spy proves it: the
     * client factory is NEVER invoked, and no settings are written.
     */
    public function test_a_rejected_index_never_constructs_a_client(): void
    {
        // The prefix is correct, so the ONLY reason this is refused is the
        // index name itself — the guard is doing the work, not the config.
        config(['scout.prefix' => 'test_']);

        $index = $this->spyIndex();
        $constructions = 0;

        $factory = $this->spyClientFactory($index, $constructions);

        try {
            ConfigureMeilisearch::applyTo('listings', $factory);
            $this->fail('The development index name must be refused while testing.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PHASE 23C-R4', $e->getMessage());
        }

        $this->assertSame(0, $constructions, 'No Meilisearch client may be constructed for a rejected index.');
        $this->assertSame([], $index->updates, 'No index setting may be written for a rejected index.');
    }

    /**
     * The anti-vacuous control: the SAME spy DOES fire and DOES record when the
     * index is safe. Without this, the test above could pass simply because the
     * spy was broken.
     */
    public function test_a_safe_index_constructs_the_client_and_writes_every_setting(): void
    {
        config(['scout.prefix' => 'test_']);

        $index = $this->spyIndex();
        $constructions = 0;

        ConfigureMeilisearch::applyTo('test_listings', $this->spyClientFactory($index, $constructions));

        $this->assertSame(1, $constructions);

        $this->assertSame(
            ConfigureMeilisearch::filterableAttributes(),
            $index->updates['filterableAttributes'] ?? null
        );
        $this->assertSame(
            ConfigureMeilisearch::sortableAttributes(),
            $index->updates['sortableAttributes'] ?? null
        );
        $this->assertSame(
            ConfigureMeilisearch::rankingRules(),
            $index->updates['rankingRules'] ?? null
        );
        $this->assertSame(
            ConfigureMeilisearch::searchableAttributes(),
            $index->updates['searchableAttributes'] ?? null
        );
    }

    /**
     * The command and the test bootstrap must share ONE definition of the
     * settings — the test-bootstrap write can never drift from the command.
     */
    public function test_the_settings_definition_is_shared_and_complete(): void
    {
        $this->assertContains('status', ConfigureMeilisearch::filterableAttributes());
        $this->assertContains('category_ids', ConfigureMeilisearch::filterableAttributes());

        $this->assertSame(
            ['words', 'typo', 'proximity', 'attributeRank', 'sort', 'wordPosition', 'exactness', 'rating:desc', 'is_featured_rank:desc'],
            ConfigureMeilisearch::rankingRules()
        );

        // Featured promotion stays LAST, so it can only break ties.
        $ranking = ConfigureMeilisearch::rankingRules();
        $this->assertSame('is_featured_rank:desc', end($ranking));
    }

    // ═══ IS MEILISEARCH ACTUALLY IN USE? (PHASE 23C-R5, F1) ═════════════════

    /**
     * THE CI CONDITION, PINNED.
     *
     * `.env.ci` sets `SCOUT_DRIVER=null` and `MEILISEARCH_HOST=`. Laravel
     * resolves the former to a genuine NULL driver — NOT the `meilisearch`
     * default — and the latter to the EMPTY string — NOT the
     * `http://localhost:7700` default. Nothing may attempt an index write in
     * that state: the test bootstrap used to, and died with
     * "URI must include a scheme and host" before a single test ran.
     */
    public function test_the_engine_is_unusable_under_the_ci_configuration(): void
    {
        config(['scout.driver' => null, 'scout.meilisearch.host' => '']);

        $this->assertFalse(ConfigureMeilisearch::hasUsableEngine());
    }

    /** A null driver is unusable even when a host happens to be configured. */
    public function test_the_engine_is_unusable_with_a_null_driver(): void
    {
        config(['scout.driver' => null, 'scout.meilisearch.host' => 'http://localhost:7700']);

        $this->assertFalse(ConfigureMeilisearch::hasUsableEngine());
    }

    /** A Meilisearch driver with no host has nothing to reach. */
    public function test_the_engine_is_unusable_without_a_host(): void
    {
        config(['scout.driver' => 'meilisearch', 'scout.meilisearch.host' => '']);

        $this->assertFalse(ConfigureMeilisearch::hasUsableEngine());
    }

    /** A whitespace-only host is not a host. */
    public function test_the_engine_is_unusable_with_a_whitespace_host(): void
    {
        config(['scout.driver' => 'meilisearch', 'scout.meilisearch.host' => '   ']);

        $this->assertFalse(ConfigureMeilisearch::hasUsableEngine());
    }

    /** Every non-Meilisearch driver means Meilisearch settings are irrelevant. */
    public function test_the_engine_is_unusable_for_every_other_driver(): void
    {
        foreach (['database', 'algolia', 'typesense', 'collection', 'null', ''] as $driver) {
            config(['scout.driver' => $driver, 'scout.meilisearch.host' => 'http://localhost:7700']);

            $this->assertFalse(
                ConfigureMeilisearch::hasUsableEngine(),
                "driver '{$driver}' must not be treated as a usable Meilisearch engine"
            );
        }
    }

    /**
     * The real local configuration is usable — which is why the bootstrap still
     * writes settings for a normal developer run.
     */
    public function test_the_engine_is_usable_with_a_meilisearch_driver_and_host(): void
    {
        config(['scout.driver' => 'meilisearch', 'scout.meilisearch.host' => 'http://localhost:7700']);

        $this->assertTrue(ConfigureMeilisearch::hasUsableEngine());
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function assertGuardRejects(string $index): void
    {
        try {
            SearchIndexName::assertSafeForMutation($index);
            $this->fail("The guard must reject '{$index}' while APP_ENV=testing.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PHASE 23C-R4', $e->getMessage());
        }
    }

    /** A double standing in for `Meilisearch\Endpoints\Indexes`. */
    private function spyIndex(): object
    {
        return new class
        {
            /** @var array<string, array<int, string>> */
            public array $updates = [];

            public function updateFilterableAttributes(array $attributes): void
            {
                $this->updates['filterableAttributes'] = $attributes;
            }

            public function updateSortableAttributes(array $attributes): void
            {
                $this->updates['sortableAttributes'] = $attributes;
            }

            public function updateRankingRules(array $rules): void
            {
                $this->updates['rankingRules'] = $rules;
            }

            public function updateSearchableAttributes(array $attributes): void
            {
                $this->updates['searchableAttributes'] = $attributes;
            }
        };
    }

    /**
     * A client factory that RECORDS each construction, and hands back a double
     * whose `index()` method returns the supplied index spy.
     *
     * @param  int  $constructions  incremented by reference on every call
     */
    private function spyClientFactory(object $index, int &$constructions): callable
    {
        return function () use ($index, &$constructions) {
            $constructions++;

            return new class($index)
            {
                public function __construct(private object $handle)
                {
                }

                public function index(string $uid): object
                {
                    return $this->handle;
                }
            };
        };
    }
}
