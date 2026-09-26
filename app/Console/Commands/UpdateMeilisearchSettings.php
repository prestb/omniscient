<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client;

class UpdateMeilisearchSettings extends Command
{
    protected $signature = 'meilisearch:update-settings';
    protected $description = 'Update Meilisearch index settings for filterable attributes';

    public function handle()
    {
        $client = new Client(
            config('scout.meilisearch.host', 'http://localhost:7700'),
            config('scout.meilisearch.key', null)
        );

        $indexName = 'businesses';

        try {
            $index = $client->getIndex($indexName);

            // ✅ Filterable attributes — must match ConfigureMeilisearch exactly.
            //    Both commands write to the same index; whichever runs last wins.
            $index->updateFilterableAttributes([
                'status',
                'is_featured',
                'has_active_subscription',
                'hidden',
                'is_open_now',
                'category_ids',
                'city_id',
                'region_id',
                'country_id',
                // ✅ Multi-branch unions
                'city_ids',
                'region_ids',
                'country_ids',
            ]);

            // Update sortable attributes
            $index->updateSortableAttributes([
                'created_at',
                'published_at',
                'average_rating',
                'name',
            ]);

            $this->info('✅ Meilisearch settings updated successfully!');
            $this->info('Filterable attributes: status, is_featured, has_active_subscription, hidden, is_open_now, category_ids, city_id, region_id, country_id, city_ids, region_ids, country_ids');
            $this->info('Sortable attributes: created_at, published_at, average_rating, name');

        } catch (\Exception $e) {
            $this->error('Failed to update settings: ' . $e->getMessage());
            $this->error('Make sure Meilisearch is running at: ' . config('scout.meilisearch.host', 'http://localhost:7700'));
        }
    }
}