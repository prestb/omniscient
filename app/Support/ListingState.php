<?php

namespace App\Support;

/**
 * ListingState
 *
 * Canonical lifecycle / visibility states for a listing (and, today, for a
 * Business which is the first concrete listing type).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TWO ORTHOGONAL AXES (Phase 1 design)
 * ─────────────────────────────────────────────────────────────────────────
 * The audit found the current system conflates two independent concerns in
 * the single `businesses.status` enum:
 *
 *   AXIS 1 — MODERATION / LIFECYCLE (admin + owner driven)
 *     draft → submitted → approved → published → (rejected / suspended)
 *     plus owner-driven `inactive` (owner paused it).
 *     These ALREADY exist as Business::STATUS_* constants and are correct.
 *
 *   AXIS 2 — QUOTA / ENTITLEMENT (plan driven)
 *     When an account downgrades below its usage, the surplus listings must
 *     NOT be deleted (see Phase 1 §7, "data safety"). Instead they become
 *     OVER_QUOTA → HIDDEN (via the existing `hidden_at` column) and are
 *     restorable by upgrading again.
 *
 * A listing is therefore described by the COMBINATION:
 *
 *     lifecycle status   (Business::STATUS_*)
 *   × quota state        (this class)
 *
 * Phase 1 does NOT add a column. This class documents the intended target
 * state machine and provides a single place for the semantics, so future
 * work (Phase 2+) can move `hidden_at`-based enforcement to a first-class
 * listing status without guesswork.
 *
 * The `hidden_at` mechanism in production today is a *partial*
 * implementation of QUOTA_STATE = HIDDEN.
 */
final class ListingState
{
    // ─────────────────────────────────────────────────────────────
    // QUOTA STATE axis
    // ─────────────────────────────────────────────────────────────

    /** Within the account's entitlement. Fully usable. */
    public const QUOTA_ACTIVE = 'active';

    /** Account currently has no active entitlement covering this listing. */
    public const QUOTA_INACTIVE = 'inactive';

    /**
     * Count exceeds the plan limit, but the downgrade grace period is still
     * running. Read-only warnings shown; not yet hidden.
     */
    public const QUOTA_OVER = 'over_quota';

    /**
     * Grace period expired; listing is hidden from the public directory
     * (today implemented via `businesses.hidden_at`). Data is preserved and
     * the owner may delete it to fall back under the limit, or upgrade to
     * restore it.
     */
    public const QUOTA_HIDDEN = 'hidden';

    /** Preserved data awaiting reactivation by an upgrade. (Future alias.) */
    public const QUOTA_PENDING_REACTIVATION = 'pending_reactivation';

    /**
     * Human-readable label for UI.
     */
    public static function label(string $quotaState): string
    {
        return match ($quotaState) {
            self::QUOTA_ACTIVE => 'Active',
            self::QUOTA_INACTIVE => 'Inactive',
            self::QUOTA_OVER => 'Over quota',
            self::QUOTA_HIDDEN => 'Hidden',
            self::QUOTA_PENDING_REACTIVATION => 'Pending reactivation',
            default => ucfirst(str_replace('_', ' ', $quotaState)),
        };
    }

    /**
     * Whether the public directory should show this listing.
     *
     * A listing is publicly visible only when BOTH axes allow it:
     *  - lifecycle status is `published` (see Business::isVisibleToPublic())
     *  - quota state is ACTIVE (i.e. not hidden)
     */
    public static function isPubliclyVisible(string $lifecycleStatus, string $quotaState): bool
    {
        return $lifecycleStatus === \App\Models\Business::STATUS_PUBLISHED
            && $quotaState === self::QUOTA_ACTIVE;
    }

    /**
     * Derive the quota state for a single unit of a resource given the
     * account's current count vs. its limit. Pure function — no DB access —
     * so it is trivially testable and reusable by the enforcement layer.
     *
     * @param  int   $index       0-based position of this item in the collection
     * @param  int   $limit       plan limit (-1 / 999 = unlimited)
     * @param  bool  $graceActive whether a downgrade grace window is open
     */
    public static function quotaStateForIndex(int $index, int $limit, bool $graceActive = false): string
    {
        $unlimited = ($limit === -1 || $limit === 999);

        if ($unlimited || $index < $limit) {
            return self::QUOTA_ACTIVE;
        }

        return $graceActive ? self::QUOTA_OVER : self::QUOTA_HIDDEN;
    }
}
