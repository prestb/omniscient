<?php

use App\Models\Business;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-5A — FAVORITES ARE LISTING-OWNED.
 *
 *   User -> Favorite -> Listing
 *
 * user_id + listing_id is the identity of a favorite. A Business is never the
 * favorite target, and no Listing is ever resolved through a Business.
 */

function fan(): User
{
    return User::factory()->owner()->create();
}

test('an authenticated user can favorite a listing', function () {
    $user = fan();
    $listing = Listing::factory()->create();

    $this->actingAs($user)
        ->post("/favorites/{$listing->id}/toggle")
        ->assertOk()
        ->assertJson(['success' => true, 'is_favorited' => true]);

    $favorite = Favorite::firstOrFail();

    expect($favorite->user_id)->toBe($user->id);
    expect($favorite->listing_id)->toBe($listing->id);
});

test('toggling twice creates exactly one favorite and then removes it', function () {
    $user = fan();
    $listing = Listing::factory()->create();

    $this->actingAs($user)->post("/favorites/{$listing->id}/toggle")->assertOk();
    expect(Favorite::where('user_id', $user->id)->where('listing_id', $listing->id)->count())->toBe(1);

    $this->actingAs($user)->post("/favorites/{$listing->id}/toggle")
        ->assertOk()
        ->assertJson(['is_favorited' => false]);

    expect(Favorite::count())->toBe(0);
});

test('the favorite lookup is keyed by user_id and listing_id', function () {
    $a = fan();
    $b = fan();
    $listing = Listing::factory()->create();

    $this->actingAs($a)->post("/favorites/{$listing->id}/toggle")->assertOk();

    // A different user toggling the same Listing creates their OWN favorite.
    $this->actingAs($b)->post("/favorites/{$listing->id}/toggle")->assertOk();

    expect(Favorite::count())->toBe(2);
    expect(Favorite::where('user_id', $a->id)->where('listing_id', $listing->id)->count())->toBe(1);
    expect(Favorite::where('user_id', $b->id)->where('listing_id', $listing->id)->count())->toBe(1);

    // B removing theirs must not touch A's.
    $this->actingAs($b)->delete("/favorites/{$listing->id}")->assertRedirect();

    expect(Favorite::count())->toBe(1);
    expect(Favorite::where('user_id', $a->id)->exists())->toBeTrue();
});

test('two listings of the same business are favorited independently', function () {
    $user = fan();
    $business = Business::factory()->create(['owner_id' => $user->id]);

    $x = Listing::factory()->forBusiness($business)->forOwner($user)->create();
    $y = Listing::factory()->forBusiness($business)->forOwner($user)->create();

    $this->actingAs($user)->post("/favorites/{$x->id}/toggle")->assertOk();
    $this->actingAs($user)->post("/favorites/{$y->id}/toggle")->assertOk();

    // Two favorites of the SAME Business — impossible under the old
    // UNIQUE(user_id, business_id) constraint, correct under the new model.
    expect(Favorite::count())->toBe(2);
    expect(Favorite::where('listing_id', $x->id)->exists())->toBeTrue();
    expect(Favorite::where('listing_id', $y->id)->exists())->toBeTrue();

    // Removing one leaves the sibling favorite intact.
    $this->actingAs($user)->delete("/favorites/{$x->id}")->assertRedirect();

    expect(Favorite::count())->toBe(1);
    expect(Favorite::where('listing_id', $y->id)->exists())->toBeTrue();
});

test('another users favorite is not affected by a toggle', function () {
    $a = fan();
    $b = fan();
    $listing = Listing::factory()->create();

    Favorite::create(['user_id' => $a->id, 'listing_id' => $listing->id]);

    $this->actingAs($b)->post("/favorites/{$listing->id}/toggle")->assertOk();

    // A's record untouched; B now has their own.
    expect(Favorite::where('user_id', $a->id)->count())->toBe(1);
    expect(Favorite::where('user_id', $b->id)->count())->toBe(1);
});

test('the favorites index returns the favorited listings', function () {
    $user = fan();
    $kept = Listing::factory()->create(['name' => 'Kept Listing']);
    $dropped = Listing::factory()->create(['name' => 'Dropped Listing']);

    Favorite::create(['user_id' => $user->id, 'listing_id' => $kept->id]);
    Favorite::create(['user_id' => $user->id, 'listing_id' => $dropped->id]);
    Favorite::where('listing_id', $dropped->id)->delete();

    $props = $this->actingAs($user)->get('/favorites')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['totalCount'])->toBe(1);

    $ids = collect($props['businesses']['data'])->pluck('id')->all();
    expect($ids)->toBe([$kept->id]);
});

test('an unauthenticated toggle returns 401 and writes nothing', function () {
    $listing = Listing::factory()->create();

    $this->postJson("/favorites/{$listing->id}/toggle")->assertStatus(401);

    expect(Favorite::count())->toBe(0);
});

test('no favorite route accepts a business as the favorite target', function () {
    $web = File::get(base_path('routes/web.php'));
    $api = File::get(base_path('routes/api.php'));

    foreach (['favorites/{business}', 'favorites/{business}/toggle'] as $gone) {
        expect(str_contains($web, $gone))->toBeFalse("web.php still routes {$gone}");
    }

    // The canonical routes are Listing-scoped.
    expect(str_contains($web, 'favorites/{listing}/toggle'))->toBeTrue();
    expect(str_contains($web, 'favorites/{listing}'))->toBeTrue();

    // A Business id is not a Listing id: an unknown Listing 404s.
    $business = Business::factory()->create();
    $this->actingAs(fan())
        ->post("/favorites/{$business->id}/toggle")
        ->assertNotFound();

    expect(Favorite::count())->toBe(0);
});

test('no favorite writer leaves listing_id null and no representative resolution exists', function () {
    // Schema level: listing_id is required.
    expect(DB::selectOne(
        "select IS_NULLABLE n from information_schema.columns
         where table_schema = database() and table_name = 'favorites' and column_name = 'listing_id'"
    )->n)->toBe('NO');

    $code = collect(preg_split('/\R/', File::get(app_path('Http/Controllers/Public/FavoriteController.php'))))
        ->reject(function (string $line) {
            $t = ltrim($line);
            return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
        })
        ->implode("\n");

    foreach (['primaryListing', 'listings()->first', 'listings->first', 'business_id'] as $forbidden) {
        expect(str_contains($code, $forbidden))
            ->toBeFalse("FavoriteController must not contain executable: {$forbidden}");
    }

    expect(str_contains($code, 'Listing $listing'))->toBeTrue();
    expect(str_contains($code, 'Business $business'))->toBeFalse();

    // The obsolete Business favorite key is gone from the schema entirely.
    expect(DB::selectOne(
        "select count(*) c from information_schema.columns
         where table_schema = database() and table_name = 'favorites' and column_name = 'business_id'"
    )->c)->toBe(0);
});
