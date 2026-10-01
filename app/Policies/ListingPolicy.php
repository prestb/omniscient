<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

/**
 * PHASE 11 / WAVE 1D-2 — Listing is independently owned and independently
 * addressable.
 *
 * Ownership is `listings.owner_id → users.id` and is authoritative. A Listing
 * is never authorized through the Business it may optionally belong to.
 */
class ListingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user may see their own Listing. Others may see it only when it is
     * publicly visible; the public route enforces that separately, so this is
     * limited to ownership (plus super admins).
     */
    public function view(User $user, Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    public function restore(User $user, Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    public function publish(User $user, Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    private function owns(User $user, Listing $listing): bool
    {
        return $user->isSuperAdmin() || (int) $listing->owner_id === (int) $user->id;
    }
}
