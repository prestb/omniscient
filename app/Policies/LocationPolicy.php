<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * PHASE 22A — LOCATION AUTHORIZATION.
 *
 * The authority is `location.owner_id`. A Location is an account-owned resource,
 * so authorization NEVER derives from Business membership: doing so would leave a
 * Business-less Professional unable to manage its own Location, and would let a
 * Business owner reach Locations they do not own.
 */
class LocationPolicy
{
    /**
     * Any authenticated account may hold Locations. Business ownership is
     * deliberately irrelevant here: a Business-less Professional must reach
     * this surface.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Location $location): bool
    {
        return $this->owns($user, $location);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->owns($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->owns($user, $location);
    }

    private function owns(User $user, Location $location): bool
    {
        return $user->isSuperAdmin() || (int) $location->owner_id === (int) $user->id;
    }
}