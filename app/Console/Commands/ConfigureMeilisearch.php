<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client;

/**
 * PHASE 11 / WAVE 1D-1 — the canonical public discovery index is `listings`.
 *
 * A Business is an organization (an aggregate of Listings). It is NOT a
 * competing searchable/discoverable entity, so its index is no longer
 * configured or populated.
 */
class ConfigureMeilisearch extends Command
{
    protected $signature = 'meilisearch:configure';

    protected $description = 'Configure the Listing discovery index settings';

    /**
     * The canonical discovery index.
     */
    public const INDEX = 'listings';

    public function handle()
    {
        $this->info('Configuring Meilisearch discovery index: ' . self::INDEX);

        $client = new Client(
            env('MEILISEARCH_HOST', 'http://localhost:7700'),
            env('MEILISEARCH_KEY')
        );

        $index = $client->index(self::INDEX);

        // Update filterable attributes
        $index->updateFilterableAttributes([
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
        ]);

        $this->info('✅ Filterable attributes updated:');
        foreach ([
            'status', 'type', 'is_featured', 'hidden', 'has_active_subscription',
            'is_open_now', 'business_id', 'location_id', 'category_ids',
            'city_id', 'region_id', 'country_id',
        ] as $attribute) {
            $this->line('  - ' . $attribute);
        }

        // Update sortable attributes
        //
        // PHASE 13 — `/search` (Meilisearch) and `/directory` (SQL) must expose
        // the same sort vocabulary. `rating` and `reviews_count` are the OWNING
        // BUSINESS's review aggregate (reviews are Business-owned); a
        // Business-less Listing indexes 0 / 0.
        $index->updateSortableAttributes([
            'created_at',
            'published_at',
            'rating',
            'reviews_count',
            'is_featured_rank',
        ]);

        $this->info('✅ Sortable attributes updated:');
        foreach (['created_at', 'published_at', 'rating', 'reviews_count', 'is_featured_rank'] as $attribute) {
            $this->line('  - ' . $attribute);
        }

        // Ranking rules — PHASE 13.
        //
        // Textual relevance is preserved absolutely: every built-in relevance
        // rule runs BEFORE any custom signal. `rating:desc` then
        // `is_featured_rank:desc` are appended as pure TIE-BREAKERS, so a
        // featured Listing can never outrank a strongly matching non-featured
        // Listing. Promotion is the last signal considered, not the first.
        //
        // Rule names match the installed Meilisearch (1.53.1).
        $index->updateRankingRules([
            'words',
            'typo',
            'proximity',
            'attributeRank',
            'sort',
            'wordPosition',
            'exactness',
            'rating:desc',
            'is_featured_rank:desc',
        ]);

        $this->info('✅ Ranking rules updated: relevance first, quality then promotion as tie-breakers.');

        // Update searchable attributes
        $index->updateSearchableAttributes([
            'name',
            'description',
            'categories_names',
            'services_names',
            'city',
            'region',
            'country',
            'address',
        ]);

        $this->info('✅ Searchable attributes updated:');

        return Command::SUCCESS;
    }
}
