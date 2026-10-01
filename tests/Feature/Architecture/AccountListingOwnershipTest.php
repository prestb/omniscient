<?php

namespace Tests\Feature\Architecture;

use App\Models\Business;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 — canonical account / ownership architecture regression coverage.
 *
 * Locks in the canonical state transition:
 *
 *     VISITOR → USER ACCOUNT → LISTING OWNER → ONE OR MORE LISTINGS
 *
 * and guarantees that a normal `user` account can own a listing WITHOUT
 * creating a duplicate account (see Phase 1 §2).
 */
class AccountListingOwnershipTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Canonical account model
    // ─────────────────────────────────────────────────────────────

    public function test_visitor_can_register_as_a_normal_user_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'account_type' => 'user',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'visitor@example.com']);
        // A normal account is created with the `user` role — the canonical
        // entry point of VISITOR → USER ACCOUNT.
        $this->assertSame(
            User::ROLE_USER,
            User::where('email', 'visitor@example.com')->first()->role
        );
    }

    public function test_visitor_can_register_directly_as_a_listing_owner(): void
    {
        // The `owner` account_type is still the SAME account entity — role
        // differs, but there is no separate "owner" table/account type.
        $this->post('/register', [
            'name' => 'Owner Visitor',
            'email' => 'owner-visitor@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'account_type' => 'owner',
            'terms' => '1',
        ])->assertRedirect();

        $this->assertSame(
            User::ROLE_OWNER,
            User::where('email', 'owner-visitor@example.com')->first()->role
        );
    }

    public function test_a_user_account_can_own_a_listing_without_a_second_account(): void
    {
        // A normal `user` (customer) can create a listing. There must be
        // exactly ONE user row afterwards — no parallel "owner" account.
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $plan = Plan::factory()->create(['max_listings' => 3]);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);

        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        $this->post('/owner/businesses', [
            'name' => 'User Owned Listing',
            'category_id' => $category->id,
        ])->assertRedirect();

        $business = Business::where('name', 'User Owned Listing')->first();

        $this->assertNotNull($business);
        $this->assertSame($user->id, $business->owner_id);

        // The canonical account IS the owner — no duplication.
        $this->assertSame(1, User::where('email', $user->email)->count());
    }

    public function test_listing_owner_relationship_resolves_to_account(): void
    {
        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create(['owner_id' => $owner->id]);

        $this->assertSame($owner->id, $business->owner->id);
        $this->assertTrue($business->owner->isOwner());
    }

    public function test_account_can_own_multiple_listings(): void
    {
        $owner = User::factory()->owner()->create();

        $a = Business::factory()->create(['owner_id' => $owner->id]);
        $b = Business::factory()->create(['owner_id' => $owner->id]);

        $this->assertSame(2, $owner->businesses()->count());
        $this->assertTrue($a->owner->is($b->owner));
    }

    // ─────────────────────────────────────────────────────────────
    // Ownership ≠ management (foundation)
    // ─────────────────────────────────────────────────────────────

    public function test_ownership_is_enforced_for_editing(): void
    {
        $owner = User::factory()->owner()->create();
        $stranger = User::factory()->owner()->create();

        $business = Business::factory()->create(['owner_id' => $owner->id]);

        // canBeEditedBy() is the canonical ownership check.
        $this->assertTrue($business->canBeEditedBy($owner));
        $this->assertFalse($business->canBeEditedBy($stranger));
    }

    public function test_admin_can_edit_any_listing(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();

        $business = Business::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($business->canBeEditedBy($admin));
    }
}
