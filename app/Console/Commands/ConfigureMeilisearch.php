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
        $index->updateSortableAttributes([
            'created_at',
            'published_at',
        ]);

        $this->info('✅ Sortable attributes updated:');
        $this->line('  - created_at');
        $this->line('  - published_at');

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
