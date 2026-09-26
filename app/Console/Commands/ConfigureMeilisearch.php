<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client;

class ConfigureMeilisearch extends Command
{
    protected $signature = 'meilisearch:configure';
    protected $description = 'Configure Meilisearch index settings';

    public function handle()
    {
        $this->info('Configuring Meilisearch index...');

        $client = new Client(
            env('MEILISEARCH_HOST', 'http://localhost:7700'),
            env('MEILISEARCH_KEY')
        );

        $index = $client->index('businesses');

        // Update filterable attributes
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

        $this->info('✅ Filterable attributes updated:');
        $this->line('  - status');
        $this->line('  - is_featured');
        $this->line('  - has_active_subscription');
        $this->line('  - hidden');
        $this->line('  - is_open_now');
        $this->line('  - category_ids');
        $this->line('  - city_id');
        $this->line('  - region_id');
        $this->line('  - country_id');
        $this->line('  - city_ids');
        $this->line('  - region_ids');
        $this->line('  - country_ids');

        // Update sortable attributes
        $index->updateSortableAttributes([
            'created_at',
            'published_at',
            'average_rating',
        ]);

        $this->info('✅ Sortable attributes updated:');
        $this->line('  - created_at');
        $this->line('  - published_at');
        $this->line('  - average_rating');

        // Update searchable attributes
        $index->updateSearchableAttributes([
            'name',
            'description',
            'categories_names',
            'services_names',
            'city',
            'region',
            'address',
            'phone',
            'email',
        ]);

        $this->info('✅ Searchable attributes updated:');

        return Command::SUCCESS;
    }
}