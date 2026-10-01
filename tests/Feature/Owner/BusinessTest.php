<?php

namespace Tests\Feature\Owner;

use App\Models\Business;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: create an owner with an active subscription
     * so plan.limit middleware doesn't block them.
     */
    private function createOwnerWithSubscription(): User
    {
        $plan = Plan::factory()->create(['max_listings' => 10]);

        $owner = User::factory()->owner()->create([
            'email_verified_at' => now(),
        ]);

        Subscription::factory()->create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        return $owner;
    }

    public function test_owner_can_create_business()
    {
        $owner = $this->createOwnerWithSubscription();
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($owner);

        $response = $this->post('/owner/businesses', [
            'name' => 'Test Business',
            'description' => 'Test Description',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'name' => 'Test Business',
            'owner_id' => $owner->id,
        ]);
    }

    public function test_owner_can_edit_their_business()
    {
        $owner = $this->createOwnerWithSubscription();

        $business = Business::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Original Name',
        ]);

        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($owner);

        $response = $this->put("/owner/businesses/{$business->id}", [
            'name' => 'Updated Business Name',
            'description' => 'Updated Description',
            'categories' => [$category->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'name' => 'Updated Business Name',
        ]);
    }

    public function test_owner_can_submit_business_for_review()
    {
        $owner = $this->createOwnerWithSubscription();

        $business = Business::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'draft',
        ]);

        $this->actingAs($owner);

        $response = $this->post("/owner/businesses/{$business->id}/submit");

        $response->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'status' => 'submitted',
        ]);
    }

    public function test_owner_can_create_business_with_categories()
    {
        $owner = $this->createOwnerWithSubscription();
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($owner);

        $response = $this->post('/owner/businesses', [
            'name' => 'Test Business',
            'description' => 'Test Description',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'name' => 'Test Business',
            'owner_id' => $owner->id,
        ]);
    }
}