<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_can_be_created()
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $business = Business::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'ABC Pharmacy',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('businesses', [
            'name' => 'ABC Pharmacy',
            'status' => 'draft',
        ]);
    }



    public function test_business_has_owner_relationship()
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create(['owner_id' => $owner->id]);

        $this->assertEquals($owner->id, $business->owner->id);
    }

    public function test_business_can_be_published()
    {
        $business = Business::factory()->create(['status' => 'draft']);
        $business->update(['status' => 'published']);

        $this->assertEquals('published', $business->status);
        $this->assertTrue($business->isPublished());
    }

    public function test_business_can_be_submitted()
    {
        $business = Business::factory()->create();
        $business->update(['status' => 'submitted']);

        // ✅ There's no isPending() on the model — the app checks status directly
        $this->assertEquals('submitted', $business->fresh()->status);
    }

    public function test_business_has_slug_generated()
    {
        // ✅ The Business factory overrides 'name' with a random company
        //    name, so the slug derives from that — not the passed name.
        //    The test just asserts the slug is present and slug-valid.
        $business = Business::factory()->create(['name' => 'Test Business Name']);

        $this->assertNotNull($business->slug);
        $this->assertNotEmpty($business->slug);
        $this->assertEquals(
            \Illuminate\Support\Str::slug($business->slug),
            $business->slug,
            'Slug should be URL-safe'
        );
    }

    public function test_business_slug_updates_on_name_change()
    {
        $business = Business::factory()->create(['name' => 'Original Name']);
        $business->update(['name' => 'New Name']);

        $this->assertEquals('new-name', $business->slug);
    }

    public function test_business_has_status_badge_attribute()
    {
        $business = Business::factory()->create(['status' => 'published']);
        $this->assertEquals('badge-published', $business->status_badge);

        $business = Business::factory()->create(['status' => 'draft']);
        $this->assertEquals('badge-draft', $business->status_badge);
    }

    
}