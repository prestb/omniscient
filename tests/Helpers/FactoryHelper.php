<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Category;

class FactoryHelper
{
    public static function createUser($role = 'owner', $status = 'active')
    {
        return User::factory()->create([
            'role' => $role,
            'status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    public static function createBusiness($owner = null, $status = 'draft')
    {
        if (!$owner) {
            $owner = self::createUser();
        }

        return Business::factory()->create([
            'owner_id' => $owner->id,
            'status' => $status,
        ]);
    }

    public static function createPlan($name = 'Basic')
    {
        return Plan::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public static function createSubscription($business = null, $plan = null, $status = 'active')
    {
        if (!$business) {
            $business = self::createBusiness();
        }
        
        if (!$plan) {
            $plan = self::createPlan();
        }

        return Subscription::factory()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    }

    public static function createCategory($name = 'Health', $parentId = null)
    {
        return Category::factory()->create([
            'name' => $name,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
    }
}