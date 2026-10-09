<?php

namespace App\Console\Commands;

use App\Support\SearchIndexName;
use Illuminate\Console\Command;
use Meilisearch\Client;

/**
 * PHASE 11 / WAVE 1D-1 — the canonical public discovery index is `listings`.
 *
 * A Business is an organization (an aggregate of Listings). It is NOT a
 * competing searchable/discoverable entity, so its index is no longer
 * configured or populated.
 *
 * PHASE 23C-R4 — THE INDEX NAME IS RESOLVED, NOT HARDCODED.
 *
 * This command used to hold `const INDEX = 'listings'` and talk to a RAW
 * Meilisearch client, so it bypassed Scout entirely and could not be pointed at
 * a different index. Combined with `Listing::searchableAs()`'s literal, that
 * meant a test run would configure and write THE DEVELOPMENT INDEX.
 *
 * The target now comes from {@see SearchIndexName::listing()}, and every write
 * passes {@see SearchIndexName::assertSafeForMutation()} BEFORE a client is
 * constructed.
 *
 * The attribute vocabulary below is the ONE definition of the index settings:
 * this command and the guarded PHPUnit bootstrap both read it, so the two can
 * never drift apart.
 */
class ConfigureMeilisearch extends Command
{
    protected $signature = 'meilisearch:configure';

    protected $description = 'Configure the Listing discovery index settings';

    /**
     * The index this command targets. Resolved per environment — never a
     * literal, so a prefixed test index can never be confused with the
     * development index.
     */
    public static function targetIndex(): string
    {
        return SearchIndexName::listing();
    }

    /**
     * PHASE 23C-R5 (F1) — is Meilisearch actually in use?
     *
     * Laravel resolves `SCOUT_DRIVER=null` to a genuine NULL driver
     * (`EngineManager::getDefaultDriver()` returns `'null'` → `NullEngine`),
     * NOT to the `meilisearch` default, and `MEILISEARCH_HOST=` to the EMPTY
     * string, NOT to the `http://localhost:7700` default. An environment that
     * sets both — as `.env.ci` does — therefore has no Meilisearch at all.
     *
     * Anything that writes index settings must ask this first. The test
     * bootstrap previously did not, so under CI it tried to reach a host of
     * `''` and died with "URI must include a scheme and host" before a single
     * test ran.
     *
     * A configured-but-unreachable host is deliberately NOT covered: that is a
     * real misconfiguration and must fail loudly rather than be skipped.
     */
    public static function hasUsableEngine(): bool
    {
        $host = config('scout.meilisearch.host');

        return config('scout.driver') === 'meilisearch'
            && is_string($host)
            && trim($host) !== '';
    }

    // ── THE SINGLE DEFINITION OF THE INDEX SETTINGS ─────────────────────────

    /**
     * Filterable attributes.
     *
     * Every filter the public directory exposes must appear here, or the
     * Meilisearch query throws "attribute is not filterable".
     *
     * @return list<string>
     */
    public static function filterableAttributes(): array
    {
        return [
            'status',
            'type',
            'is_featured',
            'hidden',
            'has_active_subscription',
            'is_open_now',
            'business_id',
            'location_id',
            'category_ids',
            'city_id',
            'region_id',
            'country_id',
        ];
    }

    /**
     * Sortable attributes.
     *
     * PHASE 13 — `/search` (Meilisearch) and `/directory` (SQL) must expose the
     * same sort vocabulary. `rating` and `reviews_count` are the LISTING's own
     * approved-review aggregate; a Listing with no reviews indexes 0 / 0.
     *
     * @return list<string>
     */
    public static function sortableAttributes(): array
    {
        return [
            'created_at',
            'published_at',
            'rating',
            'reviews_count',
            'is_featured_rank',
        ];
    }

    /**
     * Ranking rules — PHASE 13.
     *
     * Textual relevance is preserved absolutely: every built-in relevance rule
     * runs BEFORE any custom signal. `rating:desc` then `is_featured_rank:desc`
     * are appended as pure TIE-BREAKERS, so a featured Listing can never
     * outrank a strongly matching non-featured Listing. Promotion is the last
     * signal considered, not the first.
     *
     * Rule names match the installed Meilisearch (1.53.1).
     *
     * @return list<string>
     */
    public static function rankingRules(): array
    {
        return [
            'words',
            'typo',
            'proximity',
            'attributeRank',
            'sort',
            'wordPosition',
            'exactness',
            'rating:desc',
            'is_featured_rank:desc',
        ];
    }

    /**
     * Searchable attributes.
     *
     * @return list<string>
     */
    public static function searchableAttributes(): array
    {
        return [
            'name',
            'description',
            'categories_names',
            'services_names',
            'city',
            'region',
            'country',
            'address',
        ];
    }

    // ── EXECUTION ───────────────────────────────────────────────────────────

    public function handle(): int
    {
        $index = self::targetIndex();

        $this->info('Configuring Meilisearch discovery index: ' . $index);

        self::applyTo($index);

        $this->info('✅ Filterable attributes updated:');
        foreach (self::filterableAttributes() as $attribute) {
            $this->line('  - ' . $attribute);
        }

        $this->info('✅ Sortable attributes updated:');
        foreach (self::sortableAttributes() as $attribute) {
            $this->line('  - ' . $attribute);
        }

        $this->info('✅ Ranking rules updated: relevance first, quality then promotion as tie-breakers.');

        $this->info('✅ Searchable attributes updated:');

        return Command::SUCCESS;
    }

    /**
     * Apply the canonical settings to one index.
     *
     * PHASE 23C-R4 — ORDER MATTERS: the guard runs FIRST, before any client is
     * constructed. A rejected name must never reach the network, and under
     * `APP_ENV=testing` a development index name is rejected outright.
     *
     * This method produces NO console output, so it is safe to call from the
     * PHPUnit bootstrap where no `OutputInterface` is bound.
     *
     * Meilisearch applies settings ASYNCHRONOUSLY. The command does not need to
     * wait, but the test bootstrap DOES: a test that runs before the settings
     * task completes would fail with "attribute is not filterable" and look
     * like a regression. `$waitForCompletion` blocks on every task and returns
     * the finished task rows so the caller can assert they SUCCEEDED rather
     * than merely completed.
     *
     * @param  null|callable():\Meilisearch\Client  $clientFactory
     *         Test seam only. Lets a test PROVE that a rejected index never
     *         constructs a client, by asserting the factory is never invoked.
     * @param  bool  $waitForCompletion  block until Meilisearch finishes the
     *         enqueued settings tasks
     * @return array<int, array<string, mixed>> enqueued (or finished) task rows
     *
     * @throws \RuntimeException when the index is not safe to mutate
     */
    public static function applyTo(
        string $index,
        ?callable $clientFactory = null,
        bool $waitForCompletion = false
    ): array {
        SearchIndexName::assertSafeForMutation($index);

        $client = $clientFactory !== null
            ? $clientFactory()
            : new Client(
                (string) config('scout.meilisearch.host', 'http://localhost:7700'),
                config('scout.meilisearch.key')
            );

        $tasks = self::writeSettings($client->index($index));

        if (! $waitForCompletion) {
            return $tasks;
        }

        return $client->waitForTasks(array_column($tasks, 'taskUid'));
    }

    /**
     * Write every canonical settings block to an index handle.
     *
     * Untyped on purpose so the guard test can pass a recording double; in
     * production the argument is always `Meilisearch\Endpoints\Indexes`.
     *
     * @param  \Meilisearch\Endpoints\Indexes  $index
     * @return array<int, array<string, mixed>> one enqueued-task row per setting
     */
    public static function writeSettings($index): array
    {
        return [
            $index->updateFilterableAttributes(self::filterableAttributes()),
            $index->updateSortableAttributes(self::sortableAttributes()),
            $index->updateRankingRules(self::rankingRules()),
            $index->updateSearchableAttributes(self::searchableAttributes()),
        ];
    }
}
