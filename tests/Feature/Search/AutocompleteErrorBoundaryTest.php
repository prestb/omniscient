<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;

uses(RefreshDatabase::class);

/**
 * PHASE 23B — AUTOCOMPLETE ERROR BOUNDARY.
 *
 * The listing search is the ONLY autocomplete workstream that depends on the
 * search engine. Category and intent suggestions are answered by MySQL. Before
 * this phase a single broad `catch` wrapped all three, so a search-engine
 * outage returned empty `category_suggestions` for suggestions the database
 * could still have answered.
 *
 * Helper names are prefixed `ac` (autocomplete); Pest shares one process across
 * every test file in the suite.
 */

function acOwner(): User
{
    return User::factory()->owner()->create();
}

function acCategory(string $name): Category
{
    return Category::factory()->create(['name' => $name, 'is_active' => true]);
}

/** A published, visible Listing attached to the given category. */
function acListing(Category $category): Listing
{
    $owner = acOwner();

    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'location_id' => null,
    ]);

    $listing->categories()->attach($category->id);

    return $listing;
}

/** Replace the Scout engine with one whose search always fails. */
function acBreakSearchEngine(): void
{
    $engine = new class extends Engine
    {
        public function update($models) {}

        public function delete($models) {}

        public function search($builder)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function paginate($builder, $perPage = null, $page = null)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function mapIds($results)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function map($builder, $results, $model) { return collect(); }

        public function lazyMap($builder, $results, $model) { return collect(); }

        public function getTotalCount($results) { return 0; }

        public function flush($model) {}

        public function createIndex($name, array $options = []) {}

        public function deleteIndex($name) {}
    };

    app(EngineManager::class)->extend('failing-search-engine', fn () => $engine);
    config(['scout.driver' => 'failing-search-engine']);
}

// ═══ NORMAL PATH ════════════════════════════════════════════════════════════

test('autocomplete returns category suggestions for a matching query', function () {
    $category = acCategory('Supermarkets');
    acListing($category);

    $response = $this->getJson('/search/autocomplete?q=Super')->assertOk();

    $response->assertJsonStructure(['suggestions', 'category_suggestions', 'search_suggestions']);

    $names = collect($response->json('category_suggestions'))->pluck('name')->all();

    expect($names)->toContain('Supermarkets');
});

test('autocomplete returns an empty shape for a query shorter than two characters', function () {
    $this->getJson('/search/autocomplete?q=S')
        ->assertOk()
        ->assertJson([
            'suggestions' => [],
            'category_suggestions' => [],
            'search_suggestions' => [],
        ]);
});

test('autocomplete does not suggest a category with no visible listings', function () {
    acCategory('EmptyCategory');

    $names = collect(
        $this->getJson('/search/autocomplete?q=EmptyCat')->assertOk()->json('category_suggestions')
    )->pluck('name')->all();

    expect($names)->not->toContain('EmptyCategory');
});

// ═══ THE REGRESSION THIS PHASE FIXES ════════════════════════════════════════

test('category suggestions survive a search-engine failure', function () {
    // The defect: a failing Meilisearch call emptied category_suggestions,
    // even though MySQL answered those suggestions without any search engine.
    $category = acCategory('Supermarkets');
    acListing($category);

    acBreakSearchEngine();

    $response = $this->getJson('/search/autocomplete?q=Super')->assertOk();

    // The listing workstream degrades...
    expect($response->json('suggestions'))->toBe([]);

    // ...and the database-backed workstreams still answer.
    $names = collect($response->json('category_suggestions'))->pluck('name')->all();

    expect($names)->toContain('Supermarkets');
    expect($response->json('search_suggestions'))->not->toBeNull();
});

test('the autocomplete response shape is identical whether or not the engine is up', function () {
    $category = acCategory('Supermarkets');
    acListing($category);

    $healthy = $this->getJson('/search/autocomplete?q=Super')->assertOk()->json();
    $keys = array_keys($healthy);

    acBreakSearchEngine();

    $broken = $this->getJson('/search/autocomplete?q=Super')->assertOk()->json();

    expect(array_keys($broken))->toBe($keys);
});
